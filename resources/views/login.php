<?php use App\Core\Csrf; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingresar - <?= e($config['app_name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <main class="grid min-h-screen place-items-center px-4">
    <section class="w-full max-w-sm rounded-xl border border-white/10 bg-white p-6 text-slate-950 shadow-2xl">
      <div class="mb-6">
        <p class="text-sm font-medium text-emerald-700">CuentasClaras</p>
        <h1 class="text-2xl font-semibold">Ingresar</h1>
        <p class="mt-1 text-sm text-slate-500">Tus gastos del hogar quedan protegidos con sesion privada.</p>
      </div>
      <?php if ($error = flash('error')): ?>
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post" action="/login" class="space-y-4">
        <?= Csrf::field() ?>
        <label class="block">
          <span class="text-sm font-medium">Usuario</span>
          <input name="username" required autocomplete="username" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-base outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
        </label>
        <label class="block">
          <span class="text-sm font-medium">Contrasena</span>
          <input name="password" type="password" required autocomplete="current-password" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-base outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
        </label>
        <button class="w-full rounded-md bg-emerald-700 px-4 py-2.5 font-medium text-white hover:bg-emerald-800">Entrar</button>
      </form>
    </section>
  </main>
</body>
</html>
