<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class SettlementService
{
    public function __construct(private PDO $db)
    {
    }

    public function preview(string $monthCycle): array
    {
        $users = $this->activeUsers();

        if (count($users) !== 2) {
            throw new RuntimeException('La liquidacion requiere exactamente dos usuarios activos.');
        }

        $commonCycle = previous_cycle($monthCycle);
        $commonByUser = $this->commonPaidByUser($commonCycle);
        $commonRows = $this->commonRows($commonCycle);
        $obligationRows = $this->obligationRows($monthCycle);
        $obligationsByCategory = $this->obligationsByCategory($monthCycle);
        $obligationsTotal = array_sum(array_column($obligationRows, 'amount_cents'));
        $commonTotal = array_sum($commonByUser);
        $commonShares = $this->splitCents($commonTotal, $users);
        $obligationShares = $this->splitCents($obligationsTotal, $users);

        $lines = [];
        foreach ($users as $user) {
            $userId = (int) $user['id'];
            $commonPaid = $commonByUser[$userId] ?? 0;
            $commonShare = $commonShares[$userId] ?? 0;
            $commonBalance = $commonPaid - $commonShare;
            $obligationShare = $obligationShares[$userId] ?? 0;

            $lines[] = [
                'user_id' => $userId,
                'user_name' => $user['name'],
                'common_paid_cents' => $commonPaid,
                'common_share_cents' => $commonShare,
                'common_balance_cents' => $commonBalance,
                'obligation_share_cents' => $obligationShare,
                'final_transfer_cents' => $obligationShare - $commonBalance,
            ];
        }

        return [
            'month_cycle' => $monthCycle,
            'common_cycle' => $commonCycle,
            'users' => $users,
            'common_total_cents' => $commonTotal,
            'obligations_total_cents' => $obligationsTotal,
            'common_by_user' => $commonByUser,
            'common_rows' => $commonRows,
            'obligation_rows' => $obligationRows,
            'obligations_by_category' => $obligationsByCategory,
            'lines' => $lines,
        ];
    }

    public function close(string $monthCycle, int $closedBy): int
    {
        $existing = $this->db->prepare('SELECT id FROM settlements WHERE month_cycle = :cycle LIMIT 1');
        $existing->execute(['cycle' => $monthCycle]);

        if ($id = $existing->fetchColumn()) {
            return (int) $id;
        }

        $preview = $this->preview($monthCycle);
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO settlements (month_cycle, common_cycle, closed_by, total_common_cents, total_obligations_cents, snapshot_json)
                 VALUES (:month_cycle, :common_cycle, :closed_by, :total_common_cents, :total_obligations_cents, :snapshot_json)'
            );
            $stmt->execute([
                'month_cycle' => $preview['month_cycle'],
                'common_cycle' => $preview['common_cycle'],
                'closed_by' => $closedBy,
                'total_common_cents' => $preview['common_total_cents'],
                'total_obligations_cents' => $preview['obligations_total_cents'],
                'snapshot_json' => json_encode($preview, JSON_THROW_ON_ERROR),
            ]);

            $settlementId = (int) $this->db->lastInsertId();
            $lineStmt = $this->db->prepare(
                'INSERT INTO settlement_user_lines
                 (settlement_id, user_id, common_paid_cents, common_share_cents, common_balance_cents, obligation_share_cents, final_transfer_cents)
                 VALUES (:settlement_id, :user_id, :common_paid_cents, :common_share_cents, :common_balance_cents, :obligation_share_cents, :final_transfer_cents)'
            );

            foreach ($preview['lines'] as $line) {
                $lineStmt->execute([
                    'settlement_id' => $settlementId,
                    'user_id' => $line['user_id'],
                    'common_paid_cents' => $line['common_paid_cents'],
                    'common_share_cents' => $line['common_share_cents'],
                    'common_balance_cents' => $line['common_balance_cents'],
                    'obligation_share_cents' => $line['obligation_share_cents'],
                    'final_transfer_cents' => $line['final_transfer_cents'],
                ]);
            }

            $this->db->commit();
            return $settlementId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function reopen(string $monthCycle): void
    {
        $stmt = $this->db->prepare('DELETE FROM settlements WHERE month_cycle = :cycle');
        $stmt->execute(['cycle' => $monthCycle]);
    }

    private function activeUsers(): array
    {
        return $this->db->query('SELECT id, name FROM users WHERE is_active = 1 ORDER BY id')->fetchAll();
    }

    private function commonPaidByUser(string $cycle): array
    {
        $stmt = $this->db->prepare(
            'SELECT user_id, SUM(amount_cents) AS total
             FROM daily_expenses
             WHERE month_cycle = :cycle
             GROUP BY user_id'
        );
        $stmt->execute(['cycle' => $cycle]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['user_id']] = (int) $row['total'];
        }

        return $result;
    }

    private function commonRows(string $cycle): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.name AS concept, de.user_id, SUM(de.amount_cents) AS amount_cents
             FROM daily_expenses de
             JOIN categories c ON c.id = de.category_id
             WHERE de.month_cycle = :cycle
             GROUP BY c.name, de.user_id
             ORDER BY c.name'
        );
        $stmt->execute(['cycle' => $cycle]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $concept = $row['concept'];
            $userId = (int) $row['user_id'];
            $amount = (int) $row['amount_cents'];

            if (!isset($rows[$concept])) {
                $rows[$concept] = [
                    'concept' => $concept,
                    'by_user' => [],
                    'total_cents' => 0,
                ];
            }

            $rows[$concept]['by_user'][$userId] = $amount;
            $rows[$concept]['total_cents'] += $amount;
        }

        return array_values($rows);
    }

    private function obligationRows(string $cycle): array
    {
        $stmt = $this->db->prepare(
            'SELECT mo.description AS concept, mo.amount_cents, c.type
             FROM monthly_obligations mo
             JOIN categories c ON c.id = mo.category_id
             WHERE mo.month_cycle = :cycle
             ORDER BY mo.id'
        );
        $stmt->execute(['cycle' => $cycle]);

        $rows = array_map(static function (array $row): array {
            return [
                'concept' => $row['concept'],
                'type' => $row['type'],
                'amount_cents' => (int) $row['amount_cents'],
            ];
        }, $stmt->fetchAll());

        $cards = $this->db->prepare(
            'SELECT u.name AS user_name, SUM(cd.amount_cents) AS amount_cents
             FROM credit_card_drafts cd
             JOIN users u ON u.id = cd.user_id
             WHERE cd.expected_statement_cycle = :cycle AND cd.reconciled = 0
             GROUP BY cd.user_id, u.name
             ORDER BY u.id'
        );
        $cards->execute(['cycle' => $cycle]);

        foreach ($cards->fetchAll() as $card) {
            $rows[] = [
                'concept' => 'Tarjeta de Credito ' . $card['user_name'] . ' (Resumen a pagar)',
                'type' => 'credit_card',
                'amount_cents' => (int) $card['amount_cents'],
            ];
        }

        return $rows;
    }

    private function obligationsByCategory(string $cycle): array
    {
        $stmt = $this->db->prepare(
            'SELECT category_name, type, SUM(amount_cents) AS amount_cents
             FROM (
                 SELECT c.name AS category_name, c.type, mo.amount_cents
                 FROM monthly_obligations mo
                 JOIN categories c ON c.id = mo.category_id
                 WHERE mo.month_cycle = ?

                 UNION ALL

                 SELECT "Tarjetas en borrador" AS category_name, "credit_card" AS type, cd.amount_cents
                 FROM credit_card_drafts cd
                 WHERE cd.expected_statement_cycle = ? AND cd.reconciled = 0
             )
             GROUP BY category_name, type
             ORDER BY type, category_name'
        );
        $stmt->execute([$cycle, $cycle]);

        return array_map(static function (array $row): array {
            $row['amount_cents'] = (int) $row['amount_cents'];
            return $row;
        }, $stmt->fetchAll());
    }

    private function splitCents(int $amount, array $users): array
    {
        $count = count($users);
        $base = intdiv($amount, $count);
        $remainder = $amount % $count;
        $shares = [];

        foreach ($users as $index => $user) {
            $shares[(int) $user['id']] = $base + ($index < $remainder ? 1 : 0);
        }

        return $shares;
    }
}
