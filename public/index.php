<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Services\FinanceRepository;
use App\Services\SettlementService;

$config = require dirname(__DIR__) . '/bootstrap.php';
$db = Database::connection();
$auth = new Auth($db, $config);
$auth->startSession();

$repo = new FinanceRepository($db);
$settlements = new SettlementService($db);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/resources/views/' . $view . '.php';
}

function post_value(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function request_value(string $key, string $default = ''): string
{
    return trim((string) ($_REQUEST[$key] ?? $default));
}

try {
    if ($path === '/') {
        redirect('/dashboard');
    }

    if ($path === '/login' && $method === 'GET') {
        if ($auth->user()) {
            redirect('/dashboard');
        }
        render('login', ['config' => $config]);
        return;
    }

    if ($path === '/login' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        if ($auth->attempt(post_value('username'), (string) ($_POST['password'] ?? ''))) {
            redirect('/dashboard');
        }

        flash('error', 'Usuario o contrasena incorrectos.');
        redirect('/login');
    }

    if ($path === '/logout' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $auth->logout();
        redirect('/login');
    }

    $user = $auth->requireUser();

    if ($path === '/expenses' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $date = post_value('date', date('Y-m-d'));
        $cycle = post_value('month_cycle', substr($date, 0, 7));
        $settlementCycle = post_value('cycle', current_cycle());
        if (!valid_cycle($cycle)) {
            throw new InvalidArgumentException('Ciclo de gasto comun invalido.');
        }
        if (valid_cycle($settlementCycle) && $repo->isObligationCycleLocked($settlementCycle)) {
            throw new RuntimeException('Ese mes ya fue cerrado.');
        }
        $repo->addDailyExpense([
            'date' => $date,
            'user_id' => (int) $_POST['user_id'],
            'category_id' => (int) $_POST['category_id'],
            'description' => post_value('description') ?: null,
            'amount_cents' => cents_from_input(post_value('amount')),
            'month_cycle' => $cycle,
            'created_by' => (int) $user['id'],
        ]);
        flash('success', 'Gasto comun registrado.');
        redirect('/dashboard?cycle=' . urlencode(post_value('cycle', current_cycle())));
    }

    if ($path === '/expenses/update' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $repo->updateDailyExpense((int) $_POST['id'], [
            'date' => post_value('date', date('Y-m-d')),
            'user_id' => (int) $_POST['user_id'],
            'category_id' => (int) $_POST['category_id'],
            'description' => post_value('description') ?: null,
            'amount_cents' => cents_from_input(post_value('amount')),
        ]);
        flash('success', 'Gasto comun actualizado.');
        redirect('/dashboard?cycle=' . urlencode(post_value('cycle', current_cycle())));
    }

    if ($path === '/expenses/delete' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $repo->deleteDailyExpense((int) $_POST['id']);
        flash('success', 'Gasto comun borrado.');
        redirect('/dashboard?cycle=' . urlencode(post_value('cycle', current_cycle())));
    }

    if ($path === '/obligations' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $cycle = post_value('month_cycle', current_cycle());
        if (!valid_cycle($cycle)) {
            throw new InvalidArgumentException('Ciclo invalido.');
        }
        $repo->addMonthlyObligation([
            'month_cycle' => $cycle,
            'user_id' => $_POST['user_id'] === '' ? null : (int) $_POST['user_id'],
            'category_id' => (int) $_POST['category_id'],
            'description' => post_value('description'),
            'amount_cents' => cents_from_input(post_value('amount')),
            'created_by' => (int) $user['id'],
        ]);
        flash('success', 'Gasto fijo registrado.');
        redirect('/dashboard?cycle=' . urlencode($cycle));
    }

    if ($path === '/obligations/update' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $cycle = post_value('month_cycle', current_cycle());
        $repo->updateMonthlyObligation((int) $_POST['id'], [
            'user_id' => $_POST['user_id'] === '' ? null : (int) $_POST['user_id'],
            'category_id' => (int) $_POST['category_id'],
            'description' => post_value('description'),
            'amount_cents' => cents_from_input(post_value('amount')),
        ]);
        flash('success', 'Gasto fijo actualizado.');
        redirect('/dashboard?cycle=' . urlencode($cycle));
    }

    if ($path === '/obligations/delete' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $cycle = post_value('month_cycle', current_cycle());
        $repo->deleteMonthlyObligation((int) $_POST['id']);
        flash('success', 'Gasto fijo borrado.');
        redirect('/dashboard?cycle=' . urlencode($cycle));
    }

    if ($path === '/card-drafts' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $cycle = post_value('expected_statement_cycle', current_cycle());
        if (!valid_cycle($cycle)) {
            throw new InvalidArgumentException('Ciclo invalido.');
        }
        $repo->addCardDraft([
            'purchase_date' => post_value('purchase_date', date('Y-m-d')),
            'user_id' => (int) $_POST['user_id'],
            'description' => post_value('description'),
            'amount_cents' => cents_from_input(post_value('amount')),
            'installments' => max(1, (int) $_POST['installments']),
            'current_installment' => max(1, (int) $_POST['current_installment']),
            'expected_statement_cycle' => $cycle,
            'created_by' => (int) $user['id'],
        ]);
        flash('success', 'Consumo de tarjeta guardado como borrador.');
        redirect('/dashboard?cycle=' . urlencode($cycle));
    }

    if ($path === '/card-drafts/update' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $cycle = post_value('expected_statement_cycle', current_cycle());
        $repo->updateCardDraft((int) $_POST['id'], [
            'purchase_date' => post_value('purchase_date', date('Y-m-d')),
            'user_id' => (int) $_POST['user_id'],
            'description' => post_value('description'),
            'amount_cents' => cents_from_input(post_value('amount')),
            'installments' => max(1, (int) $_POST['installments']),
            'current_installment' => max(1, (int) $_POST['current_installment']),
        ]);
        flash('success', 'Tarjeta actualizada.');
        redirect('/dashboard?cycle=' . urlencode($cycle));
    }

    if ($path === '/card-drafts/delete' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $cycle = post_value('expected_statement_cycle', current_cycle());
        $repo->deleteCardDraft((int) $_POST['id']);
        flash('success', 'Tarjeta borrada.');
        redirect('/dashboard?cycle=' . urlencode($cycle));
    }

    if ($path === '/categories/create' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $repo->addCategory(post_value('name'), post_value('type'));
        flash('success', 'Categoria creada.');
        redirect('/dashboard?cycle=' . urlencode(post_value('cycle', current_cycle())));
    }

    if ($path === '/categories/update' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $repo->updateCategory((int) $_POST['id'], post_value('name'));
        flash('success', 'Categoria actualizada.');
        redirect('/dashboard?cycle=' . urlencode(post_value('cycle', current_cycle())));
    }

    if ($path === '/categories/delete' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $repo->deleteCategory((int) $_POST['id']);
        flash('success', 'Categoria desactivada.');
        redirect('/dashboard?cycle=' . urlencode(post_value('cycle', current_cycle())));
    }

    if ($path === '/settlements/close' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $cycle = post_value('month_cycle', current_cycle());
        if (!valid_cycle($cycle)) {
            throw new InvalidArgumentException('Ciclo invalido.');
        }
        $settlements->close($cycle, (int) $user['id']);
        flash('success', 'Mes cerrado. Los datos quedaron congelados.');
        redirect('/dashboard?cycle=' . urlencode($cycle));
    }

    if ($path === '/settlements/reopen' && $method === 'POST') {
        Csrf::verify($_POST['_csrf'] ?? null);
        $cycle = post_value('month_cycle', current_cycle());
        if (!valid_cycle($cycle)) {
            throw new InvalidArgumentException('Ciclo invalido.');
        }
        $settlements->reopen($cycle);
        flash('success', 'Mes reabierto. Ya podes corregir cargas y volver a cerrar.');
        redirect('/dashboard?cycle=' . urlencode($cycle));
    }

    if ($path === '/dashboard' && $method === 'GET') {
        $cycle = request_value('cycle', current_cycle());
        if (!valid_cycle($cycle)) {
            $cycle = current_cycle();
        }

        render('dashboard', [
            'config' => $config,
            'user' => $user,
            'cycle' => $cycle,
            'commonCycle' => previous_cycle($cycle),
            'users' => $repo->users(),
            'commonCategories' => $repo->categories('common_expense'),
            'obligationCategories' => $repo->categories('fixed_expense'),
            'fixedCategories' => array_merge($repo->categories('fixed_expense'), $repo->categories('credit_card')),
            'commonExpenses' => $repo->dailyExpensesForCycle(previous_cycle($cycle)),
            'obligations' => $repo->obligationsForCycle($cycle),
            'cardDrafts' => $repo->cardDraftsForCycle($cycle),
            'preview' => $settlements->preview($cycle),
            'settlement' => $repo->settlementForCycle($cycle),
            'isObligationLocked' => $repo->isObligationCycleLocked($cycle),
            'isCommonLocked' => $repo->isCommonCycleLocked(previous_cycle($cycle)) || $repo->isObligationCycleLocked($cycle),
            'commonCycleDefaultDate' => cycle_default_date(previous_cycle($cycle)),
        ]);
        return;
    }

    http_response_code(404);
    echo 'No encontrado';
} catch (Throwable $exception) {
    if (($path ?? '') === '/dashboard') {
        http_response_code(500);
        echo 'No se pudo cargar el dashboard: ' . e($exception->getMessage());
        exit;
    }

    flash('error', $exception->getMessage());
    redirect('/dashboard');
}
