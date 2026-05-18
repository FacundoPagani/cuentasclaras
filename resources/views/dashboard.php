<?php
use App\Core\Csrf;

$cardTotalsByUser = [];
$cardCountsByUser = [];
foreach ($users as $member) {
    $cardTotalsByUser[(int) $member['id']] = 0;
    $cardCountsByUser[(int) $member['id']] = 0;
}
foreach ($cardDrafts as $item) {
    $ownerId = (int) $item['user_id'];
    $cardTotalsByUser[$ownerId] = ($cardTotalsByUser[$ownerId] ?? 0) + (int) $item['amount_cents'];
    $cardCountsByUser[$ownerId] = ($cardCountsByUser[$ownerId] ?? 0) + 1;
}
$cardSummaryRows = array_sum($cardCountsByUser);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($config['app_name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="min-h-dvh bg-slate-100 text-slate-950 lg:overflow-hidden">
  <header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-[72px] max-w-[1600px] items-center justify-between gap-3 px-4">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">CuentasClaras</p>
        <h1 class="truncate text-xl font-semibold sm:text-2xl">Liquidacion <?= e($cycle) ?></h1>
      </div>
      <form method="post" action="/logout">
        <?= Csrf::field() ?>
        <button class="rounded-md border border-slate-300 bg-white px-3 py-1 text-sm font-medium hover:bg-slate-50">Salir</button>
      </form>
    </div>
  </header>

  <main class="mx-auto flex max-w-[1600px] flex-col gap-3 px-4 py-3 lg:h-[calc(100dvh-72px)] lg:overflow-hidden">
    <?php if ($success = flash('success')): ?>
      <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm text-emerald-800"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error = flash('error')): ?>
      <div class="rounded-md border border-red-200 bg-red-50 px-3 py-1 text-sm text-red-700"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="grid gap-3 lg:grid-cols-[minmax(280px,1.1fr)_repeat(3,minmax(190px,0.7fr))]">
      <article class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
        <form method="get" action="/dashboard" class="grid gap-2 sm:grid-cols-[1fr_auto] sm:items-end">
          <label class="min-w-0">
            <span class="text-xs font-semibold text-slate-700">Ciclo a liquidar</span>
            <input type="month" name="cycle" value="<?= e($cycle) ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-1 text-base">
          </label>
          <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-medium text-white">Ver</button>
        </form>
        <p class="mt-2 text-xs leading-snug text-slate-500">Compensa gastos comunes ya pagados de <?= e($commonCycle) ?> y cubre gastos fijos/tarjetas de <?= e($cycle) ?>.</p>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
        <p class="text-xs font-medium text-slate-500">Bloque 1</p>
        <p class="mt-1 truncate text-2xl font-semibold"><?= money((int) $preview['common_total_cents']) ?></p>
        <p class="text-xs text-slate-500">Gastos comunes <?= e($commonCycle) ?></p>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
        <p class="text-xs font-medium text-slate-500">Bloque 2</p>
        <p class="mt-1 truncate text-2xl font-semibold"><?= money((int) $preview['obligations_total_cents']) ?></p>
        <p class="text-xs text-slate-500">Gastos fijos + Resumen tarjetas <?= e($cycle) ?></p>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-medium text-slate-500">Estado</p>
            <p class="mt-1 text-2xl font-semibold"><?= $settlement ? 'Cerrado' : 'Abierto' ?></p>
            <p class="text-xs text-slate-500"><?= $settlement ? e($settlement['closed_at']) : 'Editable' ?></p>
          </div>
          <?php if ($settlement): ?>
            <form method="post" action="/settlements/reopen">
              <?= Csrf::field() ?>
              <input type="hidden" name="month_cycle" value="<?= e($cycle) ?>">
              <button class="rounded-md border border-slate-300 bg-white px-3 py-1 text-sm font-medium hover:bg-slate-50">Reabrir</button>
            </form>
          <?php else: ?>
            <form method="post" action="/settlements/close">
              <?= Csrf::field() ?>
              <input type="hidden" name="month_cycle" value="<?= e($cycle) ?>">
              <button class="rounded-md bg-slate-950 px-3 py-1 text-sm font-medium text-white">Cerrar</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    </section>

    <section class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
      <button data-modal-open="expense-modal" <?= $isCommonLocked ? 'disabled' : '' ?> class="rounded-md bg-emerald-700 px-3 py-1 text-sm font-medium text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">Gasto comun</button>
      <button data-modal-open="card-modal" <?= $isObligationLocked ? 'disabled' : '' ?> class="rounded-md bg-emerald-700 px-3 py-1 text-sm font-medium text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">Tarjeta</button>
      <button data-modal-open="obligation-modal" <?= $isObligationLocked ? 'disabled' : '' ?> class="rounded-md bg-emerald-700 px-3 py-1 text-sm font-medium text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">Gasto fijo</button>
      <button data-modal-open="catalog-modal" class="rounded-md bg-slate-950 px-3 py-1 text-sm font-medium text-white hover:bg-slate-800">Catalogos</button>
      <span class="ml-auto text-xs text-slate-500 max-sm:ml-0">Carga rapida: <?= e($commonCycle) ?> · Pagos del mes: <?= e($cycle) ?></span>
    </section>

    <section class="grid min-h-0 flex-1 gap-3 lg:grid-cols-12 lg:grid-rows-[minmax(0,1fr)_minmax(0,0.82fr)]">
      <article class="flex min-h-[320px] flex-col rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-7 lg:min-h-0" data-user-filter-scope>
        <div class="flex items-start justify-between gap-3 border-b border-slate-200 p-3">
          <div>
            <h2 class="font-semibold">Bloque 1: Gastos Comunes (ya pagados) (<?= e($commonCycle) ?>)</h2>
            <p class="mt-1 text-xs text-slate-500">No se vuelven a pagar al pozo; solo calculan compensacion.</p>
          </div>
          <div class="flex shrink-0 gap-1" aria-label="Filtrar Bloque 1 por usuario">
            <button data-user-filter="all" class="user-filter-button" data-active="true" title="Ver ambos">👥</button>
            <?php foreach ($users as $index => $member): ?>
              <button data-user-filter="<?= (int) $member['id'] ?>" class="user-filter-button" title="Ver <?= e($member['name']) ?>"><?= $index === 0 ? '👨' : '👩' ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="min-h-0 flex-1 overflow-auto p-3">
          <table class="w-full min-w-[580px] text-[13px] leading-tight">
            <thead class="sticky top-0 z-10 border-b border-slate-200 bg-white text-left text-slate-500">
              <tr>
                <th class="py-1 pr-3 font-medium">Fecha</th>
                <th class="px-3 py-1 font-medium">Concepto</th>
                <th class="px-3 py-1 font-medium">Pago</th>
                <th class="px-3 py-1 text-right font-medium">Monto</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($commonExpenses as $item): ?>
                <tr data-user-row="<?= (int) $item['user_id'] ?>">
                  <td class="py-1 pr-3 whitespace-nowrap"><?= e($item['date']) ?></td>
                  <td class="px-3 py-1"><p class="font-medium leading-tight"><?= e($item['description'] ?: $item['category_name']) ?></p><p class="text-xs leading-tight text-slate-500"><?= e($item['category_name']) ?></p></td>
                  <td class="px-3 py-1"><?= e($item['user_name']) ?></td>
                  <td class="px-3 py-1 text-right font-semibold"><?= money((int) $item['amount_cents']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (count($commonExpenses) === 0): ?>
                <tr><td colspan="4" class="py-6 text-center text-slate-500">Sin gastos cargados para este bloque.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="border-t border-slate-200 bg-slate-50 p-3">
          <div class="grid gap-2 text-sm sm:grid-cols-3">
            <div><p class="text-xs text-slate-500">Total ya pagado</p><p class="font-semibold"><?= money((int) $preview['common_total_cents']) ?></p></div>
            <div><p class="text-xs text-slate-500">50% cada uno</p><p class="font-semibold"><?= money((int) ($preview['lines'][0]['common_share_cents'] ?? 0)) ?></p></div>
            <div class="space-y-1">
              <?php foreach ($preview['lines'] as $line): ?>
                <div class="flex justify-between gap-2"><span class="text-xs text-slate-500"><?= e($line['user_name']) ?></span><strong><?= money((int) $line['common_balance_cents']) ?></strong></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </article>

      <article class="flex min-h-[320px] flex-col rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-5 lg:min-h-0" data-user-filter-scope>
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 p-3">
          <h2 class="font-semibold">Bloque 2: Gastos Fijos y Resumen de Tarjetas (<?= e($cycle) ?>)</h2>
          <div class="flex shrink-0 gap-1" aria-label="Filtrar Bloque 2 por usuario">
            <button data-user-filter="all" class="user-filter-button" data-active="true" title="Ver ambos">👥</button>
            <?php foreach ($users as $index => $member): ?>
              <button data-user-filter="<?= (int) $member['id'] ?>" class="user-filter-button" title="Ver <?= e($member['name']) ?>"><?= $index === 0 ? '👨' : '👩' ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="min-h-0 flex-1 overflow-auto p-3">
          <table class="w-full min-w-[520px] text-[13px] leading-tight">
            <thead class="sticky top-0 z-10 border-b border-slate-200 bg-white text-left text-slate-500">
              <tr>
                <th class="py-1 pr-3 font-medium">Concepto</th>
                <th class="px-3 py-1 font-medium">Tipo</th>
                <th class="px-3 py-1 text-right font-medium">Monto</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($obligations as $item): ?>
                <tr data-user-row="<?= $item['user_id'] === null ? 'shared' : (int) $item['user_id'] ?>">
                  <td class="py-1 pr-3"><p class="font-medium leading-tight"><?= e($item['description']) ?></p><p class="text-xs leading-tight text-slate-500"><?= e($item['user_name']) ?></p></td>
                  <td class="px-3 py-1"><?= e($item['category_name']) ?></td>
                  <td class="px-3 py-1 text-right font-semibold"><?= money((int) $item['amount_cents']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php foreach ($users as $member): ?>
                <?php $memberId = (int) $member['id']; ?>
                <?php if (($cardCountsByUser[$memberId] ?? 0) > 0): ?>
                  <tr data-user-row="<?= $memberId ?>">
                    <td class="py-1 pr-3"><p class="font-medium leading-tight">Resumen tarjeta <?= e($member['name']) ?></p><p class="text-xs leading-tight text-slate-500"><?= (int) $cardCountsByUser[$memberId] ?> consumo<?= (int) $cardCountsByUser[$memberId] === 1 ? '' : 's' ?></p></td>
                    <td class="px-3 py-1">Tarjeta</td>
                    <td class="px-3 py-1 text-right font-semibold"><?= money((int) $cardTotalsByUser[$memberId]) ?></td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if (count($obligations) + $cardSummaryRows === 0): ?>
                <tr><td colspan="3" class="py-6 text-center text-slate-500">Sin gastos fijos ni tarjetas cargadas.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="border-t border-slate-200 bg-slate-50 p-3">
          <div class="grid gap-2 text-sm sm:grid-cols-2">
            <div><p class="text-xs text-slate-500">Total real a pagar</p><p class="font-semibold"><?= money((int) $preview['obligations_total_cents']) ?></p></div>
            <div><p class="text-xs text-slate-500">50% para cada uno</p><p class="font-semibold"><?= money((int) ($preview['lines'][0]['obligation_share_cents'] ?? 0)) ?></p></div>
          </div>
        </div>
      </article>

      <article class="flex min-h-[280px] flex-col rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-7 lg:min-h-0">
        <div class="border-b border-slate-200 p-3">
          <h2 class="font-semibold">Bloque 3: Liquidacion Final</h2>
        </div>
        <div class="grid min-h-0 flex-1 gap-3 overflow-auto p-3 sm:grid-cols-2">
          <?php foreach ($preview['lines'] as $line): ?>
            <section class="rounded-md border border-slate-200 p-4">
              <p class="font-semibold"><?= e($line['user_name']) ?></p>
              <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt>Aporte 50% pago gastos fijos del mes</dt><dd><?= money((int) $line['obligation_share_cents']) ?></dd></div>
                <div class="flex justify-between gap-3"><dt>Compensacion gastos comunes ya pagados</dt><dd><?= money((int) $line['common_balance_cents']) ?></dd></div>
              </dl>
              <div class="mt-4 rounded-md bg-emerald-50 p-3 text-emerald-900">
                <p class="text-xs font-medium uppercase">Debe transferir al pozo</p>
                <p class="text-2xl font-semibold"><?= money((int) $line['final_transfer_cents']) ?></p>
              </div>
            </section>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="flex min-h-[280px] flex-col rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-5 lg:min-h-0" data-tabs="activity-panel" data-user-filter-scope>
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 p-3">
          <h2 class="font-semibold">Actividad y Detalle</h2>
          <div class="flex items-center gap-1">
            <div class="flex gap-1" aria-label="Filtrar actividad por usuario">
              <button data-user-filter="all" class="user-filter-button" data-active="true" title="Ver ambos">👥</button>
              <?php foreach ($users as $index => $member): ?>
                <button data-user-filter="<?= (int) $member['id'] ?>" class="user-filter-button" title="Ver <?= e($member['name']) ?>"><?= $index === 0 ? '👨' : '👩' ?></button>
              <?php endforeach; ?>
            </div>
            <button data-modal-open="activity-zoom-modal" class="icon-button border-slate-300 bg-white hover:bg-slate-50" title="Ampliar panel" aria-label="Ampliar actividad y detalle">⛶</button>
          </div>
        </div>
        <div class="border-b border-slate-200 px-3 py-2">
          <div class="cc-tabs-list max-w-full overflow-x-auto" role="tablist">
            <button data-tab-target="obligations" class="cc-tabs-trigger" data-active="true">Gastos fijos</button>
            <button data-tab-target="cards" class="cc-tabs-trigger">Consumo de Tarjeta</button>
            <button data-tab-target="common" class="cc-tabs-trigger">Gastos Comunes</button>
          </div>
        </div>
        <div class="min-h-0 flex-1 overflow-auto p-3">
          <section data-tab-panel="obligations" class="tab-panel divide-y divide-slate-100">
            <?php foreach ($obligations as $item): ?>
              <div data-user-row="<?= $item['user_id'] === null ? 'shared' : (int) $item['user_id'] ?>" class="flex items-center justify-between gap-3 py-1.5 text-sm">
                <div><p class="font-medium leading-tight"><?= e($item['description']) ?></p><p class="text-xs leading-tight text-slate-500"><?= e($item['category_name']) ?> · <?= e($item['user_name']) ?></p></div>
                <div class="flex shrink-0 items-center gap-2">
                  <p class="font-semibold"><?= money((int) $item['amount_cents']) ?></p>
                  <button
                    data-modal-open="obligation-edit-modal"
                    data-id="<?= (int) $item['id'] ?>"
                    data-user-id="<?= $item['user_id'] === null ? '' : (int) $item['user_id'] ?>"
                    data-category-id="<?= (int) $item['category_id'] ?>"
                    data-description="<?= e($item['description']) ?>"
                    data-amount="<?= e(number_format(((int) $item['amount_cents']) / 100, 2, '.', '')) ?>"
                    class="icon-button border-slate-300 bg-white hover:bg-slate-50"
                    title="Editar"
                    aria-label="Editar gasto fijo"
                  >✏️</button>
                  <form method="post" action="/obligations/delete" data-confirm-message="Borrar este gasto fijo?">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="month_cycle" value="<?= e($cycle) ?>">
                    <button class="icon-button border-red-200 bg-white hover:bg-red-50" title="Borrar" aria-label="Borrar gasto fijo">🗑️</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (count($obligations) === 0): ?><p class="py-3 text-sm text-slate-500">Sin gastos fijos.</p><?php endif; ?>
          </section>
          <section data-tab-panel="cards" class="tab-panel hidden divide-y divide-slate-100">
            <?php foreach ($cardDrafts as $item): ?>
              <div data-user-row="<?= (int) $item['user_id'] ?>" class="flex items-center justify-between gap-3 py-1.5 text-sm">
                <div><p class="font-medium leading-tight"><?= e($item['description']) ?></p><p class="text-xs leading-tight text-slate-500"><?= e($item['user_name']) ?> · cuota <?= (int) $item['current_installment'] ?>/<?= (int) $item['installments'] ?></p></div>
                <div class="flex shrink-0 items-center gap-2">
                  <p class="font-semibold"><?= money((int) $item['amount_cents']) ?></p>
                  <button
                    data-modal-open="card-edit-modal"
                    data-id="<?= (int) $item['id'] ?>"
                    data-user-id="<?= (int) $item['user_id'] ?>"
                    data-description="<?= e($item['description']) ?>"
                    data-amount="<?= e(number_format(((int) $item['amount_cents']) / 100, 2, '.', '')) ?>"
                    data-purchase-date="<?= e($item['purchase_date']) ?>"
                    data-current-installment="<?= (int) $item['current_installment'] ?>"
                    data-installments="<?= (int) $item['installments'] ?>"
                    class="icon-button border-slate-300 bg-white hover:bg-slate-50"
                    title="Editar"
                    aria-label="Editar tarjeta"
                  >✏️</button>
                  <form method="post" action="/card-drafts/delete" data-confirm-message="Borrar esta tarjeta?">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="expected_statement_cycle" value="<?= e($cycle) ?>">
                    <button class="icon-button border-red-200 bg-white hover:bg-red-50" title="Borrar" aria-label="Borrar tarjeta">🗑️</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (count($cardDrafts) === 0): ?><p class="py-3 text-sm text-slate-500">Sin gastos de tarjeta.</p><?php endif; ?>
          </section>
          <section data-tab-panel="common" class="tab-panel hidden divide-y divide-slate-100">
            <?php foreach ($commonExpenses as $item): ?>
              <div data-user-row="<?= (int) $item['user_id'] ?>" class="flex items-center justify-between gap-3 py-1.5 text-sm">
                <div><p class="font-medium leading-tight"><?= e($item['description'] ?: $item['category_name']) ?></p><p class="text-xs leading-tight text-slate-500"><?= e($item['date']) ?> · <?= e($item['user_name']) ?></p></div>
                <div class="flex shrink-0 items-center gap-2">
                  <p class="font-semibold"><?= money((int) $item['amount_cents']) ?></p>
                  <button
                    data-modal-open="expense-edit-modal"
                    data-id="<?= (int) $item['id'] ?>"
                    data-user-id="<?= (int) $item['user_id'] ?>"
                    data-category-id="<?= (int) $item['category_id'] ?>"
                    data-date="<?= e($item['date']) ?>"
                    data-description="<?= e($item['description'] ?: '') ?>"
                    data-amount="<?= e(number_format(((int) $item['amount_cents']) / 100, 2, '.', '')) ?>"
                    class="icon-button border-slate-300 bg-white hover:bg-slate-50"
                    title="Editar"
                    aria-label="Editar gasto comun"
                  >✏️</button>
                  <form method="post" action="/expenses/delete" data-confirm-message="Borrar este gasto comun?">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
                    <button class="icon-button border-red-200 bg-white hover:bg-red-50" title="Borrar" aria-label="Borrar gasto comun">🗑️</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (count($commonExpenses) === 0): ?><p class="py-3 text-sm text-slate-500">Sin gastos comunes para <?= e($commonCycle) ?>.</p><?php endif; ?>
          </section>
        </div>
      </article>
    </section>
  </main>

  <div id="expense-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
    <section class="w-full max-w-lg rounded-lg bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <div><h2 class="font-semibold">Gasto común</h2><p class="text-xs text-slate-500">Bloque 1 · <?= e($commonCycle) ?></p></div>
        <button data-modal-close class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">Cerrar</button>
      </div>
      <form method="post" action="/expenses" class="grid gap-3 p-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
        <input type="hidden" name="month_cycle" value="<?= e($commonCycle) ?>">
        <div class="grid grid-cols-2 gap-2">
          <label><span class="text-xs font-medium text-slate-600">Quien pago</span><select name="user_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-1"><?php foreach ($users as $member): ?><option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?></option><?php endforeach; ?></select></label>
          <label><span class="text-xs font-medium text-slate-600">Categoria</span><select name="category_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-1"><?php foreach ($commonCategories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <label><span class="text-xs font-medium text-slate-600">Monto</span><input name="amount" inputmode="decimal" required placeholder="12500" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-1 text-lg"></label>
          <label><span class="text-xs font-medium text-slate-600">Fecha</span><input name="date" type="date" value="<?= e($commonCycleDefaultDate) ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-1"></label>
        </div>
        <input name="description" placeholder="Descripcion opcional" class="rounded-md border border-slate-300 px-3 py-1">
        <button class="rounded-md bg-emerald-700 px-4 py-3 font-medium text-white">Guardar gasto común</button>
      </form>
    </section>
  </div>

  <div id="card-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
    <section class="w-full max-w-lg rounded-lg bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <div><h2 class="font-semibold">Consumo de tarjeta</h2><p class="text-xs text-slate-500">Bloque 2 · <?= e($cycle) ?></p></div>
        <button data-modal-close class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">Cerrar</button>
      </div>
      <form method="post" action="/card-drafts" class="grid gap-3 p-4">
        <?= Csrf::field() ?>
        <div class="grid grid-cols-2 gap-2">
          <select name="user_id" required class="rounded-md border border-slate-300 px-3 py-1"><?php foreach ($users as $member): ?><option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?></option><?php endforeach; ?></select>
          <input type="month" name="expected_statement_cycle" value="<?= e($cycle) ?>" class="rounded-md border border-slate-300 px-3 py-1">
        </div>
        <input name="description" required placeholder="Compra / comercio" class="rounded-md border border-slate-300 px-3 py-1">
        <div class="grid grid-cols-4 gap-2">
          <input name="amount" required inputmode="decimal" placeholder="Monto" class="col-span-2 rounded-md border border-slate-300 px-3 py-1">
          <input name="current_installment" type="number" min="1" value="1" class="rounded-md border border-slate-300 px-3 py-1">
          <input name="installments" type="number" min="1" value="1" class="rounded-md border border-slate-300 px-3 py-1">
        </div>
        <input name="purchase_date" type="date" value="<?= e(date('Y-m-d')) ?>" class="rounded-md border border-slate-300 px-3 py-1">
        <button class="rounded-md bg-emerald-700 px-4 py-3 font-medium text-white">Guardar Consumo de tarjeta</button>
      </form>
    </section>
  </div>

  <div id="obligation-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
    <section class="w-full max-w-lg rounded-lg bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <div><h2 class="font-semibold">Gasto fijo</h2><p class="text-xs text-slate-500">Bloque 2 · <?= e($cycle) ?></p></div>
        <button data-modal-close class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">Cerrar</button>
      </div>
      <form method="post" action="/obligations" class="grid gap-3 p-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="month_cycle" value="<?= e($cycle) ?>">
        <div class="grid grid-cols-2 gap-2">
          <select name="category_id" required class="rounded-md border border-slate-300 px-3 py-1"><?php foreach ($fixedCategories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select>
          <select name="user_id" class="rounded-md border border-slate-300 px-3 py-1"><option value="">Pozo comun</option><?php foreach ($users as $member): ?><option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?></option><?php endforeach; ?></select>
        </div>
        <input name="description" required placeholder="Servicio, cuota o resumen" class="rounded-md border border-slate-300 px-3 py-1">
        <input name="amount" required inputmode="decimal" placeholder="Monto" class="rounded-md border border-slate-300 px-3 py-1">
        <button class="rounded-md bg-emerald-700 px-4 py-3 font-medium text-white">Guardar gasto fijo</button>
      </form>
    </section>
  </div>

  <div id="expense-edit-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
    <section class="w-full max-w-lg rounded-lg bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <div><h2 class="font-semibold">Editar gasto común</h2><p class="text-xs text-slate-500">Bloque 1 · <?= e($commonCycle) ?></p></div>
        <button data-modal-close class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">Cerrar</button>
      </div>
      <form method="post" action="/expenses/update" class="grid gap-3 p-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="id">
        <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
        <div class="grid grid-cols-2 gap-2">
          <label><span class="text-xs font-medium text-slate-600">Quien pago</span><select name="user_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-1"><?php foreach ($users as $member): ?><option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?></option><?php endforeach; ?></select></label>
          <label><span class="text-xs font-medium text-slate-600">Categoria</span><select name="category_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-1"><?php foreach ($commonCategories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <label><span class="text-xs font-medium text-slate-600">Monto</span><input name="amount" inputmode="decimal" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-1 text-lg"></label>
          <label><span class="text-xs font-medium text-slate-600">Fecha</span><input name="date" type="date" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-1"></label>
        </div>
        <input name="description" placeholder="Descripcion opcional" class="rounded-md border border-slate-300 px-3 py-1">
        <button class="rounded-md bg-emerald-700 px-4 py-3 font-medium text-white">Actualizar gasto común</button>
      </form>
    </section>
  </div>

  <div id="card-edit-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
    <section class="w-full max-w-lg rounded-lg bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <div><h2 class="font-semibold">Editar consumo de tarjeta</h2><p class="text-xs text-slate-500">Bloque 2 · <?= e($cycle) ?></p></div>
        <button data-modal-close class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">Cerrar</button>
      </div>
      <form method="post" action="/card-drafts/update" class="grid gap-3 p-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="id">
        <input type="hidden" name="expected_statement_cycle" value="<?= e($cycle) ?>">
        <div class="grid grid-cols-2 gap-2">
          <select name="user_id" required class="rounded-md border border-slate-300 px-3 py-1"><?php foreach ($users as $member): ?><option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?></option><?php endforeach; ?></select>
          <input name="purchase_date" type="date" required class="rounded-md border border-slate-300 px-3 py-1">
        </div>
        <input name="description" required class="rounded-md border border-slate-300 px-3 py-1">
        <div class="grid grid-cols-4 gap-2">
          <input name="amount" required inputmode="decimal" class="col-span-2 rounded-md border border-slate-300 px-3 py-1">
          <input name="current_installment" type="number" min="1" class="rounded-md border border-slate-300 px-3 py-1">
          <input name="installments" type="number" min="1" class="rounded-md border border-slate-300 px-3 py-1">
        </div>
        <button class="rounded-md bg-emerald-700 px-4 py-3 font-medium text-white">Actualizar consumo de tarjeta</button> 
      </form>
    </section>
  </div>

  <div id="obligation-edit-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
    <section class="w-full max-w-lg rounded-lg bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <div><h2 class="font-semibold">Editar gasto fijo</h2><p class="text-xs text-slate-500">Bloque 2 · <?= e($cycle) ?></p></div>
        <button data-modal-close class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">Cerrar</button>
      </div>
      <form method="post" action="/obligations/update" class="grid gap-3 p-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="id">
        <input type="hidden" name="month_cycle" value="<?= e($cycle) ?>">
        <div class="grid grid-cols-2 gap-2">
          <select name="category_id" required class="rounded-md border border-slate-300 px-3 py-1"><?php foreach ($fixedCategories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select>
          <select name="user_id" class="rounded-md border border-slate-300 px-3 py-1"><option value="">Pozo comun</option><?php foreach ($users as $member): ?><option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?></option><?php endforeach; ?></select>
        </div>
        <input name="description" required class="rounded-md border border-slate-300 px-3 py-1">
        <input name="amount" required inputmode="decimal" class="rounded-md border border-slate-300 px-3 py-1">
        <button class="rounded-md bg-emerald-700 px-4 py-3 font-medium text-white">Actualizar gasto fijo</button>
      </form>
    </section>
  </div>

  <div id="catalog-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
    <section class="flex max-h-[90dvh] w-full max-w-4xl flex-col rounded-lg bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <div><h2 class="font-semibold">Catalogos</h2><p class="text-xs text-slate-500">ABM de items de gasto comun y gastos fijos</p></div>
        <button data-modal-close class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">Cerrar</button>
      </div>
      <div class="min-h-0 overflow-auto p-4">
        <div class="grid gap-4 md:grid-cols-2">
          <section>
            <h3 class="mb-2 font-semibold">Gastos comunes</h3>
            <form method="post" action="/categories/create" class="mb-2 flex gap-2">
              <?= Csrf::field() ?>
              <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
              <input type="hidden" name="type" value="common_expense">
              <input name="name" required placeholder="Nuevo item" class="compact-input min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-1">
              <button class="compact-button rounded-md bg-emerald-700 px-3 py-1 text-sm font-medium text-white">Agregar</button>
            </form>
            <div class="divide-y divide-slate-100 rounded-md border border-slate-200">
              <?php foreach ($commonCategories as $category): ?>
                <div class="flex gap-2 p-1.5">
                  <form method="post" action="/categories/update" class="flex min-w-0 flex-1 gap-2">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
                    <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                    <input name="name" value="<?= e($category['name']) ?>" class="compact-input min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-1">
                    <button class="icon-button border-slate-300 bg-white hover:bg-slate-50" title="Guardar" aria-label="Guardar categoria">💾</button>
                  </form>
                  <form method="post" action="/categories/delete" data-confirm-message="Desactivar esta categoria?">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
                    <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                    <button class="icon-button border-red-200 bg-white hover:bg-red-50" title="Borrar" aria-label="Borrar categoria">🗑️</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <section>
            <h3 class="mb-2 font-semibold">Gastos fijos</h3>
            <form method="post" action="/categories/create" class="mb-2 flex gap-2">
              <?= Csrf::field() ?>
              <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
              <input type="hidden" name="type" value="fixed_expense">
              <input name="name" required placeholder="Nuevo item" class="compact-input min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-1">
              <button class="compact-button rounded-md bg-emerald-700 px-3 py-1 text-sm font-medium text-white">Agregar</button>
            </form>
            <div class="divide-y divide-slate-100 rounded-md border border-slate-200">
              <?php foreach ($obligationCategories as $category): ?>
                <div class="flex gap-2 p-1.5">
                  <form method="post" action="/categories/update" class="flex min-w-0 flex-1 gap-2">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
                    <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                    <input name="name" value="<?= e($category['name']) ?>" class="compact-input min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-1">
                    <button class="icon-button border-slate-300 bg-white hover:bg-slate-50" title="Guardar" aria-label="Guardar categoria">💾</button>
                  </form>
                  <form method="post" action="/categories/delete" data-confirm-message="Desactivar esta categoria?">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
                    <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                    <button class="icon-button border-red-200 bg-white hover:bg-red-50" title="Borrar" aria-label="Borrar categoria">🗑️</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        </div>
      </div>
    </section>
  </div>

  <div id="activity-zoom-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
    <section class="flex h-[90dvh] w-full max-w-6xl flex-col rounded-lg bg-white shadow-xl" data-tabs="activity-zoom" data-user-filter-scope>
      <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
        <div>
          <h2 class="text-lg font-semibold">Actividad y Detalle</h2>
          <p class="text-xs text-slate-500">Vista ampliada de gastos fijos, gastos de tarjeta y gastos comunes.</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="flex gap-1" aria-label="Filtrar actividad ampliada por usuario">
            <button data-user-filter="all" class="user-filter-button" data-active="true" title="Ver ambos">👥</button>
            <?php foreach ($users as $index => $member): ?>
              <button data-user-filter="<?= (int) $member['id'] ?>" class="user-filter-button" title="Ver <?= e($member['name']) ?>"><?= $index === 0 ? '👨' : '👩' ?></button>
            <?php endforeach; ?>
          </div>
          <button data-modal-close class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">Cerrar</button>
        </div>
      </div>
      <div class="border-b border-slate-200 px-4 py-2">
        <div class="cc-tabs-list max-w-full overflow-x-auto" role="tablist">
          <button data-tab-target="obligations" class="cc-tabs-trigger" data-active="true">Gastos fijos</button>
          <button data-tab-target="cards" class="cc-tabs-trigger">Consumo de Tarjeta</button>
          <button data-tab-target="common" class="cc-tabs-trigger">Gastos Comunes</button>
        </div>
      </div>
      <div class="min-h-0 flex-1 overflow-auto p-4">
        <section data-tab-panel="obligations" class="tab-panel divide-y divide-slate-100">
          <?php foreach ($obligations as $item): ?>
            <div data-user-row="<?= $item['user_id'] === null ? 'shared' : (int) $item['user_id'] ?>" class="grid gap-3 py-3 text-base sm:grid-cols-[1fr_auto] sm:items-center">
              <div><p class="font-semibold"><?= e($item['description']) ?></p><p class="text-sm text-slate-500"><?= e($item['category_name']) ?> · <?= e($item['user_name']) ?></p></div>
              <div class="flex items-center justify-end gap-2">
                <p class="text-right text-lg font-semibold"><?= money((int) $item['amount_cents']) ?></p>
                <button
                  data-modal-open="obligation-edit-modal"
                  data-id="<?= (int) $item['id'] ?>"
                  data-user-id="<?= $item['user_id'] === null ? '' : (int) $item['user_id'] ?>"
                  data-category-id="<?= (int) $item['category_id'] ?>"
                  data-description="<?= e($item['description']) ?>"
                  data-amount="<?= e(number_format(((int) $item['amount_cents']) / 100, 2, '.', '')) ?>"
                  class="icon-button border-slate-300 bg-white hover:bg-slate-50"
                  title="Editar"
                  aria-label="Editar gasto fijo"
                >✏️</button>
                <form method="post" action="/obligations/delete" data-confirm-message="Borrar este gasto fijo?">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                  <input type="hidden" name="month_cycle" value="<?= e($cycle) ?>">
                  <button class="icon-button border-red-200 bg-white hover:bg-red-50" title="Borrar" aria-label="Borrar gasto fijo">🗑️</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (count($obligations) === 0): ?><p class="py-6 text-center text-slate-500">Sin gastos fijos.</p><?php endif; ?>
        </section>
        <section data-tab-panel="cards" class="tab-panel hidden divide-y divide-slate-100">
          <?php foreach ($cardDrafts as $item): ?>
            <div data-user-row="<?= (int) $item['user_id'] ?>" class="grid gap-3 py-3 text-base sm:grid-cols-[1fr_auto] sm:items-center">
              <div><p class="font-semibold"><?= e($item['description']) ?></p><p class="text-sm text-slate-500"><?= e($item['user_name']) ?> · cuota <?= (int) $item['current_installment'] ?>/<?= (int) $item['installments'] ?></p></div>
              <div class="flex items-center justify-end gap-2">
                <p class="text-right text-lg font-semibold"><?= money((int) $item['amount_cents']) ?></p>
                <button
                  data-modal-open="card-edit-modal"
                  data-id="<?= (int) $item['id'] ?>"
                  data-user-id="<?= (int) $item['user_id'] ?>"
                  data-description="<?= e($item['description']) ?>"
                  data-amount="<?= e(number_format(((int) $item['amount_cents']) / 100, 2, '.', '')) ?>"
                  data-purchase-date="<?= e($item['purchase_date']) ?>"
                  data-current-installment="<?= (int) $item['current_installment'] ?>"
                  data-installments="<?= (int) $item['installments'] ?>"
                  class="icon-button border-slate-300 bg-white hover:bg-slate-50"
                  title="Editar"
                  aria-label="Editar consumo de tarjeta"
                >✏️</button>
                <form method="post" action="/card-drafts/delete" data-confirm-message="Borrar este consumo de tarjeta?">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                  <input type="hidden" name="expected_statement_cycle" value="<?= e($cycle) ?>">
                  <button class="icon-button border-red-200 bg-white hover:bg-red-50" title="Borrar" aria-label="Borrar consumo de tarjeta">🗑️</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (count($cardDrafts) === 0): ?><p class="py-6 text-center text-slate-500">Sin consumos de tarjeta.</p><?php endif; ?>
        </section>
        <section data-tab-panel="common" class="tab-panel hidden divide-y divide-slate-100">
          <?php foreach ($commonExpenses as $item): ?>
            <div data-user-row="<?= (int) $item['user_id'] ?>" class="grid gap-3 py-3 text-base sm:grid-cols-[1fr_auto] sm:items-center">
              <div><p class="font-semibold"><?= e($item['description'] ?: $item['category_name']) ?></p><p class="text-sm text-slate-500"><?= e($item['date']) ?> · <?= e($item['user_name']) ?> · <?= e($item['category_name']) ?></p></div>
              <div class="flex items-center justify-end gap-2">
                <p class="text-right text-lg font-semibold"><?= money((int) $item['amount_cents']) ?></p>
                <button
                  data-modal-open="expense-edit-modal"
                  data-id="<?= (int) $item['id'] ?>"
                  data-user-id="<?= (int) $item['user_id'] ?>"
                  data-category-id="<?= (int) $item['category_id'] ?>"
                  data-date="<?= e($item['date']) ?>"
                  data-description="<?= e($item['description'] ?: '') ?>"
                  data-amount="<?= e(number_format(((int) $item['amount_cents']) / 100, 2, '.', '')) ?>"
                  class="icon-button border-slate-300 bg-white hover:bg-slate-50"
                  title="Editar"
                  aria-label="Editar gasto comun"
                >✏️</button>
                <form method="post" action="/expenses/delete" data-confirm-message="Borrar este gasto comun?">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                  <input type="hidden" name="cycle" value="<?= e($cycle) ?>">
                  <button class="icon-button border-red-200 bg-white hover:bg-red-50" title="Borrar" aria-label="Borrar gasto comun">🗑️</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (count($commonExpenses) === 0): ?><p class="py-6 text-center text-slate-500">Sin gastos comunes para <?= e($commonCycle) ?>.</p><?php endif; ?>
        </section>
      </div>
    </section>
  </div>

  <div id="confirm-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
    <section class="w-full max-w-sm rounded-lg border border-slate-200 bg-white shadow-xl">
      <div class="border-b border-slate-200 px-4 py-3">
        <h2 id="confirm-modal-title" class="font-semibold">Confirmar accion</h2>
        <p id="confirm-modal-message" class="mt-1 text-sm text-slate-500">Esta accion no se puede deshacer.</p>
      </div>
      <div class="flex justify-end gap-2 px-4 py-3">
        <button type="button" data-modal-close class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium hover:bg-slate-50">Cancelar</button>
        <button type="button" id="confirm-modal-submit" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700">Borrar</button>
      </div>
    </section>
  </div>

  <script>
    document.querySelectorAll('[data-modal-open]').forEach(button => {
      button.addEventListener('click', () => {
        const modal = document.getElementById(button.dataset.modalOpen)
        if (!modal) return
        const form = modal.querySelector('form')
        if (form) {
          setField(form, 'id', button.dataset.id)
          setField(form, 'user_id', button.dataset.userId)
          setField(form, 'category_id', button.dataset.categoryId)
          setField(form, 'date', button.dataset.date)
          setField(form, 'description', button.dataset.description)
          setField(form, 'amount', button.dataset.amount)
          setField(form, 'purchase_date', button.dataset.purchaseDate)
          setField(form, 'current_installment', button.dataset.currentInstallment)
          setField(form, 'installments', button.dataset.installments)
        }
        modal.classList.remove('hidden')
        modal.classList.add('flex')
        modal.querySelector('input, select, button')?.focus()
      })
    })

    function setField(form, name, value) {
      if (value === undefined) return
      const field = form.elements.namedItem(name)
      if (field) field.value = value
    }

    let pendingConfirmForm = null
    const confirmModal = document.getElementById('confirm-modal')
    const confirmMessage = document.getElementById('confirm-modal-message')
    const confirmSubmit = document.getElementById('confirm-modal-submit')

    document.querySelectorAll('form[data-confirm-message]').forEach(form => {
      form.addEventListener('submit', event => {
        event.preventDefault()
        pendingConfirmForm = form
        if (confirmMessage) confirmMessage.textContent = form.dataset.confirmMessage || 'Confirmar esta accion?'
        if (confirmModal) {
          confirmModal.classList.remove('hidden')
          confirmModal.classList.add('flex')
          confirmSubmit?.focus()
        }
      })
    })

    confirmSubmit?.addEventListener('click', () => {
      if (!pendingConfirmForm) return
      const form = pendingConfirmForm
      pendingConfirmForm = null
      closeModal(confirmModal)
      form.submit()
    })

    document.querySelectorAll('[data-tabs]').forEach(tabs => {
      tabs.querySelectorAll('[data-tab-target]').forEach(button => {
        button.addEventListener('click', () => {
          const target = button.dataset.tabTarget

          tabs.querySelectorAll('[data-tab-target]').forEach(tabButton => {
            tabButton.dataset.active = tabButton === button ? 'true' : 'false'
          })

          tabs.querySelectorAll('[data-tab-panel]').forEach(panel => {
            panel.classList.toggle('hidden', panel.dataset.tabPanel !== target)
          })
        })
      })
    })

    document.querySelectorAll('[data-user-filter-scope]').forEach(scope => {
      scope.querySelectorAll('[data-user-filter]').forEach(button => {
        button.addEventListener('click', () => {
          const selected = button.dataset.userFilter

          scope.querySelectorAll('[data-user-filter]').forEach(filterButton => {
            filterButton.dataset.active = filterButton === button ? 'true' : 'false'
          })

          scope.querySelectorAll('[data-user-row]').forEach(row => {
            const owner = row.dataset.userRow
            row.classList.toggle('hidden', selected !== 'all' && owner !== selected && owner !== 'shared')
          })
        })
      })
    })

    function closeModal(modal) {
      modal.classList.add('hidden')
      modal.classList.remove('flex')
    }

    document.querySelectorAll('[role="dialog"]').forEach(modal => {
      modal.addEventListener('click', event => {
        if (event.target === modal || event.target.closest?.('[data-modal-close]')) {
          closeModal(modal)
        }
      })
    })

    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        document.querySelectorAll('[role="dialog"]:not(.hidden)').forEach(closeModal)
      }
    })
  </script>
</body>
</html>
