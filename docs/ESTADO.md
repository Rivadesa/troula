# Estado del proyecto — Troula Eventos

Documento de continuidad: dónde está el proyecto, qué está hecho, qué queda y cómo retomarlo.

- **Repositorio:** https://github.com/Rivadesa/troula (rama `main`)
- **Producción:** https://troula.xeitoso.com — panel en `/admin` (alojado en SiteGround)
- **Estado:** Fase 1 completa y **en producción**.

---

## Funcionalidades implementadas

- [x] Configurador frontend (wizard de 5 pasos) con precio en vivo.
- [x] Selección de experiencia en lista vertical; packs y complementos por categoría.
- [x] Disponibilidad con turnos (mañana/tarde) + datepicker que la respeta.
- [x] Motor de precios: base, temporada, packs, complementos con override, porte/montaje por zona.
- [x] Reserva como *lead* + email de aviso al administrador.
- [x] Panel Filament: catálogo (experiencias, categorías, complementos, packs), temporadas,
      zonas de porte, concellos.
- [x] Imágenes en todas las entidades (experiencias, complementos, packs, categorías, logo empresa).
- [x] Configuración de empresa (nombre, logo, contacto, dirección, redes, URL política privacidad).
- [x] Roles: **admin** (acceso total) y **empleado** (solo reservas en lectura + calendario).
- [x] Gestión de usuarios (UserResource).
- [x] Clientes (ClienteResource) con consentimiento **LOPD** obligatorio en el configurador.
- [x] Calendario mensual de reservas.
- [x] Cambio de contraseña (página de perfil de Filament).
- [x] Tests Pest (servicios, reservas, panel, configurador, LOPD, roles) — todos en verde.

## Decisiones clave (`// DECISIÓN` en el código)

- **PHP `^8.2`** en `composer.json` (local y producción corren en 8.4).
- **Base de datos:** **MySQL en producción** (`dbvqgakk4knpd2` en SiteGround); SQLite en local y
  tests. Migraciones portables. Comando `db:copiar-a-mysql` para migrar datos sin pérdida.
- **Tailwind + flatpickr por CDN** en el frontend → sin paso de build (npm) para desplegar.
- **Enums PHP** (`App\Enums\*`) en columnas `string`.
- **Roles** con columna `users.rol` + trait `App\Filament\Concerns\SoloAdministradores`.
- **Cliente** se crea/enlaza solo tras aceptar la LOPD (`ReservaService::registrarCliente`).
- **Mail `log` + cola `sync`** en producción (de momento, sin SMTP ni cron).

## Mapa rápido del código

- `app/Services/` — `CalculadoraPrecioService`, `DisponibilidadService`, `ReservaService`, `DesglosePrecio`.
- `app/Livewire/Configurador.php` + `resources/views/livewire/configurador.blade.php` — el wizard.
- `app/Filament/Resources/` — recursos del panel. `app/Filament/Pages/` — Empresa, Calendario, perfil.
- `app/Enums/` — `Turno`, `EstadoReserva`, `TipoAjuste`, `TipoPago`, `EstadoPago`, `Rol`.
- `app/Contracts/PasarelaPago.php` — punto de extensión de Fase 2 (Redsys).
- `database/migrations/` — modelo de datos. `database/seeders/` — datos de demo.
- `tests/` — Pest (Feature/Services, Feature/Filament, ConfiguradorTest…).

## Infraestructura

- **Hosting:** SiteGround. Subdominio `troula.xeitoso.com`.
- **Acceso SSH y rutas del servidor:** ver `NOTAS-SERVIDOR.md` (local, no versionado).
- **Despliegue de cambios:** `git push` → en el servidor `cd app && bash deploy.sh`.

### Accesos de demo (cambiar en producción)

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | `admin@troula.test` | `password` |
| Empleado | `empleado@troula.test` | `password` |

---

## Pendientes / próximos pasos sugeridos

1. **Seguridad:** cambiar las contraseñas de `admin@` y `empleado@` desde el perfil.
2. **Correo real:** configurar SMTP de SiteGround (`MAIL_*`) y, si se quiere encolar,
   `QUEUE_CONNECTION=database` + cron de `schedule:run`.
3. **Datos de empresa:** rellenar Configuración → Empresa (logo, contacto, redes, URL privacidad).
4. **Página de política de privacidad** real a la que enlace el checkbox LOPD.
5. ~~Pasar a MySQL~~ ✅ Hecho (producción en MySQL).
6. **Fase 2:** integración de pagos con Redsys sobre `App\Contracts\PasarelaPago`
   (señal al reservar + saldo antes del evento) y activación de la máquina de estados.

## Cómo trabajar

```bash
# Local
composer install
php artisan migrate --seed
php artisan serve --no-reload        # Windows/Herd: el flag evita el fallo de puerto

# Tests
./vendor/bin/pest

# Desplegar a producción
git push origin main
# (en el servidor) cd ~/www/troula.xeitoso.com/app && bash deploy.sh
```
