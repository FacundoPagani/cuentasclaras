<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class FinanceRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function users(): array
    {
        return $this->db->query('SELECT id, username, name FROM users WHERE is_active = 1 ORDER BY id')->fetchAll();
    }

    public function categories(string $type): array
    {
        $stmt = $this->db->prepare('SELECT id, name FROM categories WHERE type = :type AND is_active = 1 ORDER BY name');
        $stmt->execute(['type' => $type]);

        return $stmt->fetchAll();
    }

    public function addCategory(string $name, string $type): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('El nombre de la categoria es obligatorio.');
        }

        if (!in_array($type, ['common_expense', 'fixed_expense', 'credit_card'], true)) {
            throw new RuntimeException('Tipo de categoria invalido.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, type, is_active)
             VALUES (:name, :type, 1)
             ON CONFLICT(name, type) DO UPDATE SET is_active = 1'
        );
        $stmt->execute([
            'name' => $name,
            'type' => $type,
        ]);
    }

    public function updateCategory(int $id, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('El nombre de la categoria es obligatorio.');
        }

        $stmt = $this->db->prepare('UPDATE categories SET name = :name WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $name,
        ]);
    }

    public function deleteCategory(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE categories SET is_active = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function addDailyExpense(array $data): void
    {
        if ($this->isCommonCycleLocked($data['month_cycle'])) {
            throw new RuntimeException('Ese ciclo de gastos comunes ya fue cerrado.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO daily_expenses (date, user_id, category_id, description, amount_cents, month_cycle, created_by)
             VALUES (:date, :user_id, :category_id, :description, :amount_cents, :month_cycle, :created_by)'
        );
        $stmt->execute($data);
    }

    public function dailyExpensesForCycle(string $cycle): array
    {
        $stmt = $this->db->prepare(
            'SELECT de.*, u.name AS user_name, c.name AS category_name
             FROM daily_expenses de
             JOIN users u ON u.id = de.user_id
             JOIN categories c ON c.id = de.category_id
             WHERE de.month_cycle = :cycle
             ORDER BY de.date DESC, de.id DESC'
        );
        $stmt->execute(['cycle' => $cycle]);

        return $stmt->fetchAll();
    }

    public function updateDailyExpense(int $id, array $data): void
    {
        $cycle = $this->cycleFor('daily_expenses', 'month_cycle', $id);
        if ($cycle === null || $this->isCommonCycleLocked($cycle)) {
            throw new RuntimeException('Ese gasto no se puede modificar porque el ciclo esta cerrado.');
        }

        $stmt = $this->db->prepare(
            'UPDATE daily_expenses
             SET date = :date, user_id = :user_id, category_id = :category_id, description = :description, amount_cents = :amount_cents
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'date' => $data['date'],
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'amount_cents' => $data['amount_cents'],
        ]);
    }

    public function deleteDailyExpense(int $id): void
    {
        $cycle = $this->cycleFor('daily_expenses', 'month_cycle', $id);
        if ($cycle === null || $this->isCommonCycleLocked($cycle)) {
            throw new RuntimeException('Ese gasto no se puede borrar porque el ciclo esta cerrado.');
        }

        $stmt = $this->db->prepare('DELETE FROM daily_expenses WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function addMonthlyObligation(array $data): void
    {
        if ($this->isObligationCycleLocked($data['month_cycle'])) {
            throw new RuntimeException('Ese ciclo de gastos fijos ya fue cerrado.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO monthly_obligations (month_cycle, user_id, category_id, description, amount_cents, created_by)
             VALUES (:month_cycle, :user_id, :category_id, :description, :amount_cents, :created_by)'
        );
        $stmt->execute($data);
    }

    public function updateMonthlyObligation(int $id, array $data): void
    {
        $cycle = $this->cycleFor('monthly_obligations', 'month_cycle', $id);
        if ($cycle === null || $this->isObligationCycleLocked($cycle)) {
            throw new RuntimeException('Ese gasto fijo no se puede modificar porque el ciclo esta cerrado.');
        }

        $stmt = $this->db->prepare(
            'UPDATE monthly_obligations
             SET user_id = :user_id, category_id = :category_id, description = :description, amount_cents = :amount_cents
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'amount_cents' => $data['amount_cents'],
        ]);
    }

    public function deleteMonthlyObligation(int $id): void
    {
        $cycle = $this->cycleFor('monthly_obligations', 'month_cycle', $id);
        if ($cycle === null || $this->isObligationCycleLocked($cycle)) {
            throw new RuntimeException('Ese gasto fijo no se puede borrar porque el ciclo esta cerrado.');
        }

        $stmt = $this->db->prepare('DELETE FROM monthly_obligations WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function addCardDraft(array $data): void
    {
        if ($this->isObligationCycleLocked($data['expected_statement_cycle'])) {
            throw new RuntimeException('Ese ciclo de tarjetas ya fue cerrado.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO credit_card_drafts (purchase_date, user_id, description, amount_cents, installments, current_installment, expected_statement_cycle, created_by)
             VALUES (:purchase_date, :user_id, :description, :amount_cents, :installments, :current_installment, :expected_statement_cycle, :created_by)'
        );
        $stmt->execute($data);
    }

    public function updateCardDraft(int $id, array $data): void
    {
        $cycle = $this->cycleFor('credit_card_drafts', 'expected_statement_cycle', $id);
        if ($cycle === null || $this->isObligationCycleLocked($cycle)) {
            throw new RuntimeException('Esa tarjeta no se puede modificar porque el ciclo esta cerrado.');
        }

        $stmt = $this->db->prepare(
            'UPDATE credit_card_drafts
             SET purchase_date = :purchase_date, user_id = :user_id, description = :description, amount_cents = :amount_cents,
                 installments = :installments, current_installment = :current_installment
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'purchase_date' => $data['purchase_date'],
            'user_id' => $data['user_id'],
            'description' => $data['description'],
            'amount_cents' => $data['amount_cents'],
            'installments' => $data['installments'],
            'current_installment' => $data['current_installment'],
        ]);
    }

    public function deleteCardDraft(int $id): void
    {
        $cycle = $this->cycleFor('credit_card_drafts', 'expected_statement_cycle', $id);
        if ($cycle === null || $this->isObligationCycleLocked($cycle)) {
            throw new RuntimeException('Esa tarjeta no se puede borrar porque el ciclo esta cerrado.');
        }

        $stmt = $this->db->prepare('DELETE FROM credit_card_drafts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function recentDailyExpenses(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            'SELECT de.*, u.name AS user_name, c.name AS category_name
             FROM daily_expenses de
             JOIN users u ON u.id = de.user_id
             JOIN categories c ON c.id = de.category_id
             ORDER BY de.date DESC, de.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obligationsForCycle(string $cycle): array
    {
        $stmt = $this->db->prepare(
            'SELECT mo.*, COALESCE(u.name, "Pozo comun") AS user_name, c.name AS category_name
             FROM monthly_obligations mo
             LEFT JOIN users u ON u.id = mo.user_id
             JOIN categories c ON c.id = mo.category_id
             WHERE mo.month_cycle = :cycle
             ORDER BY mo.created_at DESC, mo.id DESC'
        );
        $stmt->execute(['cycle' => $cycle]);

        return $stmt->fetchAll();
    }

    public function cardDraftsForCycle(string $cycle): array
    {
        $stmt = $this->db->prepare(
            'SELECT cd.*, u.name AS user_name
             FROM credit_card_drafts cd
             JOIN users u ON u.id = cd.user_id
             WHERE cd.expected_statement_cycle = :cycle
             ORDER BY cd.purchase_date DESC, cd.id DESC'
        );
        $stmt->execute(['cycle' => $cycle]);

        return $stmt->fetchAll();
    }

    public function settlementForCycle(string $cycle): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM settlements WHERE month_cycle = :cycle LIMIT 1');
        $stmt->execute(['cycle' => $cycle]);
        $settlement = $stmt->fetch();

        if (!$settlement) {
            return null;
        }

        $lines = $this->db->prepare(
            'SELECT sul.*, u.name AS user_name
             FROM settlement_user_lines sul
             JOIN users u ON u.id = sul.user_id
             WHERE sul.settlement_id = :id
             ORDER BY u.id'
        );
        $lines->execute(['id' => (int) $settlement['id']]);
        $settlement['lines'] = $lines->fetchAll();
        $settlement['snapshot'] = json_decode($settlement['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);

        return $settlement;
    }

    public function isObligationCycleLocked(string $cycle): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM settlements WHERE month_cycle = :cycle LIMIT 1');
        $stmt->execute(['cycle' => $cycle]);

        return (bool) $stmt->fetchColumn();
    }

    public function isCommonCycleLocked(string $cycle): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM settlements WHERE common_cycle = :cycle LIMIT 1');
        $stmt->execute(['cycle' => $cycle]);

        return (bool) $stmt->fetchColumn();
    }

    private function cycleFor(string $table, string $column, int $id): ?string
    {
        $allowed = [
            'daily_expenses' => 'month_cycle',
            'monthly_obligations' => 'month_cycle',
            'credit_card_drafts' => 'expected_statement_cycle',
        ];

        if (($allowed[$table] ?? null) !== $column) {
            throw new RuntimeException('Consulta de ciclo invalida.');
        }

        $stmt = $this->db->prepare("SELECT {$column} FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $cycle = $stmt->fetchColumn();

        return $cycle === false ? null : (string) $cycle;
    }
}
