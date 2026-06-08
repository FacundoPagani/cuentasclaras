# CuentasClaras

Sistema web ligero para gestionar gastos del hogar entre dos personas, separando lo ya pagado, lo que falta pagar y la liquidacion final que cada integrante debe transferir al pozo comun.

## 1. Esquema SQLite

El esquema inicial esta en [`database/schema.sql`](database/schema.sql). Incluye:

- `users`: usuarios activos con `password_hash` generado por `password_hash`.
- `categories`: categorias por tipo (`common_expense`, `fixed_expense`, `credit_card`).
- `daily_expenses`: Bloque 1, gastos comunes pagados por adelantado en efectivo/debito.
- `monthly_obligations`: Bloque 2, gastos fijos del ciclo actual.
- `credit_card_drafts`: borradores/resumenes de tarjeta que se suman al Bloque 2.
- `settlements` y `settlement_user_lines`: Bloque 3, cierre historico congelado.

Todos los montos se guardan como centavos enteros (`amount_cents`) para evitar errores de redondeo.

## 2. Estructura y Docker

```text
.
├── app/                         # Bloque Next/shadcn solicitado
├── bin/                         # Migracion y creacion de usuarios
├── components/                  # UI shadcn y bloques
├── config/                      # Configuracion PHP
├── database/schema.sql          # Esquema SQLite
├── docker/nginx/default.conf    # Nginx interno de la imagen
├── docker/php/entrypoint.sh     # Migracion, seed inicial y arranque
├── public/index.php             # Front controller PHP
├── resources/views/             # Login y dashboard PHP
├── src/                         # Core, auth, repositorio y liquidacion
├── Dockerfile
└── docker-compose.yml
```

Levantar la app:

```bash
docker compose up --build
```

Por defecto no publica puertos al host. El contenedor incluye Nginx + PHP-FPM, se conecta a la red Docker externa `web` y queda accesible para el Nginx de `pagani.ar` como `http://cuentasclaras:8080`.

Para desarrollo local sin Nginx se puede usar el servidor embebido de PHP:

```bash
APP_ENV=local APP_URL=http://localhost:8080 SESSION_SECURE=0 DB_PATH=storage/cuentasclaras.sqlite php -S localhost:8080 -t public
```

Abrir `http://localhost:8080`.

El `Dockerfile` instala Nginx, `sqlite` runtime y `sqlite-dev` solo durante la construccion para compilar `pdo_sqlite`; luego elimina las dependencias de build.

Si SQLite devuelve `attempt to write a readonly database`, normalmente el archivo fue creado por `root` durante la migracion inicial. Reparar permisos del volumen:

```bash
sudo docker compose exec app chown -R www-data:www-data /var/www/html/storage
sudo docker compose exec app chmod -R u+rwX,g+rwX /var/www/html/storage
```

Reiniciar todos los datos cargados, conservando usuarios y categorias:

```bash
sudo docker compose exec app php bin/reset-data.php
```

Sincronizar los usuarios activos del hogar con `docker-compose.yml` sin borrar movimientos:

```bash
sudo docker compose exec app php bin/sync-household-users.php
```

Usuarios iniciales:

- `facu` / `132456`
- `judi` / `132456`

Cambiar esas credenciales con variables de entorno antes de desplegar. Para crear o resetear usuarios:

```bash
docker compose exec app php bin/create-user.php facu "NuevaClaveSegura" "Facu"
```

## 2.1 Deploy en `https://cuentasclaras.pagani.ar`

El subdominio permite servir la app desde la raiz `/`, por lo que no hace falta adaptar rutas internas. Las rutas absolutas como `/dashboard`, `/login` y `/assets/app.css` funcionan correctamente en `https://cuentasclaras.pagani.ar`.

Valores recomendados en el VPS:

```bash
cp .env.example .env
# editar .env y cambiar las contrasenas iniciales
docker compose up -d --build
```

El contenedor del proyecto es autocontenido: incluye Nginx, PHP-FPM, el codigo de la app y sirve `public/` internamente. El Nginx de `pagani.ar` solo hace reverse proxy HTTP al contenedor.

Variables relevantes:

```bash
APP_ENV=production
APP_URL=https://cuentasclaras.pagani.ar
SESSION_SECURE=1
PAGANI_NETWORK=web
```

`PAGANI_NETWORK` debe ser la red Docker donde esta conectado el contenedor Nginx de `pagani.ar`. Para verla:

```bash
docker inspect <contenedor-nginx-pagani> --format '{{json .NetworkSettings.Networks}}'
```

Agregar al `default.conf` del Nginx de `pagani.ar`, que ya tiene el certificado para `pagani.ar`/`*.pagani.ar`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name cuentasclaras.pagani.ar;
    return 301 https://cuentasclaras.pagani.ar$request_uri;
}
```

Y el vhost HTTPS:

```nginx
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name cuentasclaras.pagani.ar;

    ssl_certificate /etc/nginx/certs/origin.crt;
    ssl_certificate_key /etc/nginx/certs/origin.key;

    client_max_body_size 5m;

    location / {
        proxy_pass http://cuentasclaras:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Con este modelo no hace falta montar `public/` en el contenedor `pagani.ar`, copiar certificados dentro del stack ni definir `TLS_CERT_PATH`/`TLS_KEY_PATH`. Cloudflare puede quedar en `Full` o `Full (strict)` contra el Nginx existente de `pagani.ar`.

## 3. Base de datos y autenticacion

La conexion PDO esta en [`src/Core/Database.php`](src/Core/Database.php). Usa SQLite con `foreign_keys`, `WAL` y `busy_timeout`.

La autenticacion esta en [`src/Core/Auth.php`](src/Core/Auth.php):

- cookies `HttpOnly`, `SameSite=Lax` y `Secure` configurable con `SESSION_SECURE`;
- regeneracion de ID de sesion al iniciar sesion;
- timeout por inactividad (`SESSION_LIFETIME`);
- verificacion de contrasenas con `password_verify`;
- rehash automatico si PHP cambia el algoritmo recomendado.

Los formularios protegidos usan token CSRF desde [`src/Core/Csrf.php`](src/Core/Csrf.php).

## 4. Motor de liquidacion

La logica central esta en [`src/Services/SettlementService.php`](src/Services/SettlementService.php).

Para cerrar un ciclo `YYYY-MM`:

1. Toma los gastos comunes ya pagados en efectivo/debito del ciclo anterior (`common_cycle`).
2. Suma los gastos fijos y borradores de tarjeta del ciclo actual (`month_cycle`).
3. Divide comunes y gastos fijos al 50%.
4. Calcula el saldo compensatorio: `pagado_en_comunes - mitad_de_comunes`.
5. Calcula transferencia final: `mitad_de_gastos_fijos - saldo_compensatorio`.
6. Guarda un snapshot JSON y lineas por usuario en `settlements`.

Ejemplo conceptual: la liquidacion de mayo compensa los gastos comunes ya pagados en abril, pero el efectivo que entra al pozo corresponde a los gastos fijos y tarjetas a pagar en mayo. El Bloque 1 modifica cuanto aporta cada usuario; no duplica dinero ya gastado.

Cuando existe un cierre, el ciclo queda congelado: no se pueden seguir agregando gastos fijos de ese mes ni gastos comunes del ciclo usado por ese cierre.

## 5. Interfaz

La app PHP principal tiene:

- pantalla de login en [`resources/views/login.php`](resources/views/login.php);
- dashboard mobile-first en [`resources/views/dashboard.php`](resources/views/dashboard.php);
- modales para cargar gasto comun, tarjeta y gasto fijo sin ocupar la pantalla principal;
- tablas con scroll interno para ver el tablero completo en desktop;
- edicion y borrado de items cargados en Bloque 1 y Bloque 2 mientras el mes este abierto;
- ABM de catalogos para items de gasto comun y gastos fijos;
- panel de actividad con tabs para gastos fijos, gastos de tarjeta y gastos comunes, con vista ampliada en modal;
- filtros por usuario en Bloque 1, Bloque 2 y Actividad, sin alterar los resumenes de cierre;
- vista de liquidacion de los tres bloques y boton para cerrar/reabrir mes.

Tambien se agrego el bloque shadcn solicitado en [`app/dashboard-shell-01/page.tsx`](app/dashboard-shell-01/page.tsx), con estructura React 19, TypeScript 5 y Tailwind CSS 4. Componentes y hooks:

- `components/shadcn-studio/blocks/*`
- `components/ui/*`
- `hooks/use-pagination.ts`
- `components.json`

Comandos del bloque React:

```bash
npm install
npm run build
npm run dev
```

La CLI de shadcn fue ejecutada con:

```bash
npx shadcn@latest add avatar breadcrumb button card separator sidebar dropdown-menu badge pagination table chart progress --yes
```

En este entorno la CLI llego hasta el prompt de sobrescritura de archivos existentes, por lo que se conservaron los componentes locales ya creados.

## Verificacion realizada

- `npm install`: correcto.
- `npx shadcn@latest add ...`: ejecuto e instalo dependencias; no sobrescribio archivos existentes.
- `npm run build`: correcto. El primer intento fallo por una dependencia opcional faltante de Tailwind; se resolvio instalando `@tailwindcss/oxide-linux-x64-gnu`.
- `docker compose config`: correcto.

No pude ejecutar `php -l` ni `docker compose build` desde este entorno porque no hay binario PHP local y el usuario actual no tiene permiso sobre `/var/run/docker.sock`. Los archivos Docker quedan listos para correr en un entorno con permisos de Docker.
