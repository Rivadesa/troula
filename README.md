# Troula Eventos — Plataforma de alquiler de fotomatones y equipamiento

Plataforma para configurar packs de eventos (bodas, banquetes): el cliente final elige
una **experiencia** (fotomatón, espejo mágico, photocall, cabina 360…) y sus
**complementos**, con cálculo de precio en vivo y comprobación de disponibilidad por
fecha. Incluye un panel de administración para gestionar catálogo, disponibilidad,
precios y reservas.

> **Fase 1 (esta entrega):** configurador completo + disponibilidad con turnos + motor de
> precios (temporada, packs, zonas) + panel Filament + reserva como *lead* por email.
> Todo el dinero se calcula y se muestra, pero **no se cobra online**. La tabla `pagos` y
> la máquina de estados quedan creadas pero inertes (ver [Fase 2](#fase-2-puntos-de-extensión)).

---

## Stack

| Pieza | Versión / elección |
|-------|--------------------|
| PHP | **8.2+** (desarrollado en 8.4; `composer.json` exige `^8.2` para compatibilidad con Dinahosting) |
| Laravel | 12.x |
| Panel admin | **Filament 3** |
| Frontend | **Livewire 3 + Alpine.js** sobre Blade (sin SPA) |
| Base de datos | **MySQL / MariaDB** en producción · SQLite en local y en tests |
| Cola | driver `database` (sin worker permanente) |
| Tests | **Pest** |

### Decisiones de diseño (`// DECISIÓN`)

- **PHP `^8.2`** en `composer.json` aunque el entorno local sea 8.4, para que el proyecto
  funcione en la versión que se seleccione en el panel de Dinahosting.
- **Base de datos en local = SQLite** (sin servicio externo); **producción = MySQL/MariaDB**.
  Las migraciones son portables. Ver `.env` (local) y `.env.example` (plantilla de producción).
- **Tailwind y flatpickr vía CDN** en el frontend público: el configurador es autónomo y se
  despliega en hosting compartido **sin paso de build** (npm). Livewire ya incluye Alpine.
- **Enums modelados con columnas `string` + Enums PHP** (`App\Enums\*`): portable entre
  SQLite/MySQL y fácil de ampliar en Fase 2.
- **Zonas de porte por concello** (no por km vía API); el modelo admite evolucionar a
  cálculo por distancia más adelante sin romper datos.

---

## Modelo de datos (resumen)

- **Catálogo:** `experiencias`, `categorias_complemento`, `complementos`, pivote
  `experiencia_complemento` (con `precio_override`, `obligatorio`, `cantidad_maxima`, `orden`).
- **Packs:** `packs` (precio cerrado) + pivote `pack_complemento`.
- **Precios por temporada:** `temporadas` (rangos que se repiten cada año, ajuste % o fijo).
- **Porte/montaje:** `zonas_porte` + `concello_zona`.
- **Reservas:** `reservas` (importes **congelados**), `reserva_complemento` (precio congelado), `pagos`.

### Lógica de negocio (`app/Services`)

- **`DisponibilidadService`** — solapamiento de turnos (`completo` solapa con `manana` y
  `tarde`), respeta `unidades` y excluye reservas `cancelada`. Expone las fechas/turnos no
  disponibles para el datepicker.
- **`CalculadoraPrecioService`** — base (experiencia o pack) → ajuste de temporada →
  complementos (con `precio_override`) → porte/montaje. Devuelve un **desglose completo**
  (`DesglosePrecio`), nunca solo el total.
- **`ReservaService`** — valida disponibilidad, calcula y **congela** importes y líneas de
  complementos al crear la reserva. Lo reutilizan el configurador y los seeders.

El frontend y el backend usan **el mismo** `CalculadoraPrecioService`; no hay lógica de
precios duplicada en JS.

---

## Puesta en marcha en local

Requisitos: PHP 8.2+ con extensiones `pdo_sqlite`, `mbstring`, `gd`, `intl`, `zip`,
`bcmath`, `fileinfo`, `openssl`; y Composer.

```bash
composer install
cp .env.example .env        # en local puedes dejar DB_CONNECTION=sqlite
php artisan key:generate
php artisan migrate --seed  # crea las tablas y carga datos de demo
php artisan serve
```

- **Configurador (frontend):** http://localhost:8000/
- **Panel de administración:** http://localhost:8000/admin
  - Usuario: `admin@troula.test` · Contraseña: `password` (creado por el seeder; **cámbialos en producción**).

> El `.env` del repo ya viene con `DB_CONNECTION=sqlite` para arrancar sin MySQL.
> El fichero `database/database.sqlite` se crea solo en la instalación.

### Tests

```bash
./vendor/bin/pest
```

Los tests corren sobre SQLite en memoria (ver `phpunit.xml`). Cubren los dos servicios
(turnos, temporada, packs, portes), la creación de reservas, el panel Filament y el
configurador Livewire de extremo a extremo.

---

## Despliegue en Dinahosting (hosting compartido)

Dinahosting **no** ofrece WebSockets, Docker ni un worker de cola permanente. El proyecto
está diseñado en consecuencia.

### 1. Versión de PHP

Selecciona **PHP 8.2 o superior** desde el panel de Dinahosting (sección de versiones de PHP
del dominio). Asegúrate de que estén activas las extensiones `pdo_mysql`, `mbstring`,
`gd`, `intl`, `zip`, `bcmath`, `fileinfo`, `openssl`.

### 2. Base de datos

Crea una base de datos **MySQL/MariaDB** y un usuario desde el panel, y rellena en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tu_basedatos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 3. Despliegue de la aplicación

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env        # y edítalo con los datos reales
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force # opcional: solo si quieres los datos de demo
php artisan storage:link    # para servir imágenes subidas (experiencias/complementos)
php artisan config:cache
php artisan route:cache
```

> **No se necesita `npm`/`build` para el frontend público** (Tailwind y flatpickr van por CDN).
> Los assets del panel Filament ya están publicados en `public/`. Si actualizas Filament,
> ejecuta `php artisan filament:upgrade`.

### 4. Document root

Apunta el dominio a la carpeta **`public/`** del proyecto (es el _document root_ de toda app
Laravel). Si el panel solo permite servir desde `public_html`, sube el proyecto fuera de la
web y enlaza/ajusta `public_html` al `public/` de Laravel.

### 5. Cola y cron (importante)

La cola usa el driver `database` y se procesa **desde el cron**, no con un worker. Añade en el
planificador de tareas de Dinahosting un cron **cada minuto**:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Ese `schedule:run` dispara `queue:work --stop-when-empty` (definido en `routes/console.php`),
que envía los emails de aviso de nueva reserva y cualquier otro trabajo encolado.

> Si el plan no permite cron por minuto, usa la frecuencia más alta disponible (los avisos se
> enviarán con ese retardo). Como alternativa puntual, `QUEUE_CONNECTION=sync` envía el email
> en el mismo request (sin cola), a costa de ralentizar la confirmación de la reserva.

### 6. Correo

Configura el SMTP de Dinahosting y la dirección que recibe los *leads*:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-dominio.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="reservas@tu-dominio.com"
MAIL_ADMIN_ADDRESS="info@tu-dominio.com"
```

### 7. OPcache

No se manipula OPcache en runtime, así que `opcache.restrict_api` puede quedar activo sin
problema. Tras un despliegue, si el panel lo permite, reinicia PHP para refrescar OPcache.

---

## Fase 2 (puntos de extensión)

No implementada todavía. El código deja preparados los _hooks_:

- **`App\Contracts\PasarelaPago`** — interfaz para la integración con **Redsys** (señal al
  reservar + cobro del saldo antes del evento). Documenta el flujo previsto.
- **Tabla `pagos`** y enums `TipoPago` (`senal`/`saldo`) y `EstadoPago`
  (`pendiente`/`pagado`/`fallido`) ya existen e inertes.
- **Máquina de estados de la reserva** (`App\Enums\EstadoReserva`): los estados
  `confirmada`/`pagada`/`realizada` están definidos; en Fase 1 se gestionan a mano desde el
  panel. En Fase 2 los transicionará la pasarela al confirmar pagos.
- El `ReservaService` es el punto natural para encadenar la creación de la señal tras crear
  la reserva.
