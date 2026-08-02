# Continuación — Adaptar el panel/configurador al catálogo real (Retrátate Eventos)

> Estado: **FASE COMPLETADA** (agosto 2026). Los 6 pasos del plan están implementados,
> con 72 tests Pest en verde. Este documento se conserva como referencia de los datos
> del catálogo y de las decisiones tomadas.
> Lee también `docs/ESTADO.md` (estado general del proyecto) y `README.md`.

## Lo que quedó implementado

| Paso | Estado | Dónde |
|---|---|---|
| 1. Migraciones + modelos | ✅ | `2026_08_02_100001..100006`, modelos `Experiencia`/`Complemento`/`Pack`/`Reserva`/`ConcelloZona` |
| 2. Motor de precios | ✅ | `CalculadoraPrecioService::calcular(..., int $horasExtra)`, `DesglosePrecio` |
| 3. Panel Filament | ✅ | campos nuevos + `PackResource\RelationManagers\BasesRelationManager` |
| 4. Configurador | ✅ | `Configurador` + `configurador.blade.php` + `partials/complemento-card.blade.php` |
| 5. Datos reales + branding | ✅ | `CatalogoRetratateSeeder`, `BrandingRetratateSeeder` (demo `CatalogoSeeder`/`PackSeeder` eliminados) |
| 6. Tests | ✅ | 52 → **72** tests Pest |
| Extra: concellos de Galicia | ✅ | `ZonaPorteSeeder` con los 313 concellos por provincia |

### Cosas a confirmar con el cliente

1. **Precios de porte** de las zonas nuevas (Pontevedra 100 €, Lugo 110 €, Ourense 140 €): son una
   propuesta inicial, se editan en Panel → Zonas de porte.
2. **Hora extra**: la tarifa solo indica precio para el Fotomatón Solo (70 €). El resto de máquinas
   no ofrecen horas extra hasta que se les ponga precio en el panel.
3. **Pack Glitter Loco**: la tarifa lo describe como "Glitter Bar 3 h + Hora Loca A", sin nombrar
   máquina. Se ha dejado colgando del Fotomatón Solo (el esquema exige una base) — confirmar si
   incluye fotomatón o no.
4. **Descuentos de pack en juegos** (Futbolín 130 € y Arcade 100 € dentro del Pack Full Fotomatón):
   quedan mencionados en la descripción del pack; el configurador los cobra a precio de tarifa.

## Contexto rápido del proyecto

- App Laravel 12 + Filament 3 + Livewire. Repo público: https://github.com/Rivadesa/troula
- **Producción:** https://troula.xeitoso.com (panel `/admin`), SiteGround, **BD MySQL** `dbvqgakk4knpd2`.
- Local: SQLite. Windows/Herd → arrancar con `php artisan serve --no-reload`. Tests: `./vendor/bin/pest`.
- Despliegue: `git push` → en el servidor `cd ~/www/troula.xeitoso.com/app && bash deploy.sh`.
  Datos SSH y credenciales en `NOTAS-SERVIDOR.md` (local, no versionado).
- Accesos panel: `admin@troula.test` / `empleado@troula.test` (contraseñas ya cambiadas por el cliente).
- Al cerrar la sesión anterior había 49→52 tests en verde; toda la puesta a punto (SMTP configurable,
  privacidad, MySQL, seguridad) está hecha y desplegada.

## Objetivo de esta fase

Reflejar el catálogo real del cliente en el panel (editable por el admin) y en el configurador.
El modelo actual (experiencias + complementos con precio por experiencia + packs cerrados) cubre casi
todo; faltan 4 conceptos + cargar los datos reales.

### Decisiones ya tomadas con el cliente
- **Precios variables / "a consultar"** (mesas dulces, puestos comida, detalles por tramos):
  se muestran en el configurador pero **NO suman** al total (el admin marca `a_consultar`).
- **Packs con base intercambiable (modelo completo):** cada pack admite varios fotomatones base con
  un **suplemento**; el cliente puede cambiar la máquina en el configurador y recalcula.

## Plan aprobado (ejecutar en este orden)

### 1. Migraciones + modelos
- `experiencias`: `+ duracion_horas` (unsignedInteger default 3), `+ precio_hora_extra` (decimal 10,2 nullable).
- `complementos`: `+ a_consultar` (boolean default false).
- Pivote `experiencia_complemento`: `+ grupo` (string nullable) → complementos con el mismo grupo se
  muestran como "elige uno" (radio) en el configurador.
- Nueva tabla pivote `pack_experiencia`: `pack_id`, `experiencia_id`, `suplemento` (decimal 10,2 default 0).
  La base por defecto sigue en `packs.experiencia_id` (suplemento 0).
- `reservas`: `+ horas_extra` (unsignedInteger default 0).
- Modelos: `Experiencia`, `Complemento`, `Pack` (relación `basesDisponibles()` belongsToMany con
  pivote `suplemento`), `Reserva`. Actualizar fillable/casts. Añadir `grupo` a los withPivot de
  `Experiencia::complementos()`. Actualizar factories si procede.

### 2. Motor de precios (`app/Services/CalculadoraPrecioService.php` + `DesglosePrecio.php`)
- `calcular(...)` recibe `int $horasExtra = 0`. La `$experiencia` es la máquina elegida.
- Base: pack → `pack.precio + suplemento(pack,experiencia)` (leer de `pack_experiencia`); si no → `precio_base`.
- Horas extra: `+ experiencia.precio_hora_extra * horasExtra` (línea propia del desglose).
- Complementos `a_consultar`: se listan aparte (no suman). El resto igual que ahora (override, etc.).
- `DesglosePrecio` gana: `horasExtra`, `importeHorasExtra`, `lineasAConsultar`.

### 3. Panel Filament (todo editable por admin)
- `ExperienciaResource`: `duracion_horas`, `precio_hora_extra`.
- `ComplementoResource`: toggle `a_consultar` (+ columna); `precio` opcional cuando está activo.
- `ExperienciaResource\RelationManagers\ComplementosRelationManager`: campo `grupo` en attach/edit.
- `PackResource`: **nuevo** `RelationManagers\BasesRelationManager` (máquinas base + `suplemento`),
  patrón idéntico al `ComplementosRelationManager` existente (AttachAction + campo suplemento).

### 4. Configurador (`app/Livewire/Configurador.php` + `resources/views/livewire/configurador.blade.php`)
- Stepper `$horasExtra` (si la experiencia tiene `precio_hora_extra`), recálculo en vivo, "Incluye N horas".
- Grupos "elige uno": complementos con mismo `grupo` como radios; reflejar en `complementosExtras()`.
- `a_consultar`: mostrar "Consultar", marcables, en el resumen como "a presupuestar" sin sumar.
- Pack con base intercambiable: selector de máquina base (`pack->basesDisponibles`), reusa `$experienciaId`.
- `ReservaService::crear` congela `horas_extra` (ya guarda la máquina en `experiencia_id`).

### 5. Datos reales + branding
- Nuevo seeder `Database\Seeders\CatalogoRetratateSeeder` (sustituye demo `CatalogoSeeder`/`PackSeeder`).
  Idempotente por slug. Ver los DATOS DEL CATÁLOGO abajo.
- `DatabaseSeeder` llama al nuevo seeder; `ReservaSeeder` (demo) se adapta a los nuevos slugs o se reduce.
- `Configuracion`: nombre "Retrátate Eventos", web `https://www.retratate.es`, etc.

### 6. Tests + despliegue
- Ampliar Pest: horas extra suman; `a_consultar` no suma; suplemento de base de pack correcto;
  grupo "elige uno" (solo una); pack cambia de base y recalcula; `PanelSmokeTest` con el nuevo relation manager.
- `git push` → `bash deploy.sh` en el servidor. **Carga del catálogo real en producción:** las 6
  reservas actuales son demo → limpiar reservas+catálogo demo y ejecutar el seeder real por SSH
  (base64→php como en la sesión anterior). NO tocar `.env`, usuarios, configuración de empresa/correo/privacidad.

### Fuera de alcance (más adelante)
- Precios por tramos de cantidad (portafoto imán, invitaciones) → por ahora `a_consultar`.

---

## DATOS DEL CATÁLOGO (para el seeder)

Fuente: `C:\Users\Santi\Downloads\RETRATATE EVENTOS CATALOGO 2026.pdf` (extraíble con `pypdf`, ya instalado).
Precios IVA incluido. Estándar incluido en TODAS las máquinas (va en la descripción): álbum de firmas
personalizado, 3h de animación, copias/fotos ilimitadas, boomerangs y GIFs, QR de descarga, pantalla de
inicio personalizable, galería web privada, técnico, seguro RC, montaje/desmontaje, atrezzo/disfraces,
formato tira y postal, personalización de diseño, opción de video-dedicatorias 30s.

### Experiencias (precio = 3 horas salvo nota)
| Experiencia | Precio | Notas |
|---|---|---|
| Fotomatón Solo | 450€ (3h) / 400€ (2h) | hora extra 70€, sin decoración |
| Fotomatón con Photocall Tela | 480€ | fondo tela 2x2 / 2,2x2,2 |
| Fotomatón Photocall Tela Lentejuelas | 500€ | 2,4x2,4 |
| Fotomatón Estructura + Neón | 600€ (sin sofá) / 700€ (con sofá) | elige estructura + neón (+ sofá opcional) |
| Fotomatón Beauty Glam | 480€ | |
| Fotomatón Beauty Glam Premium | 600€ | |
| Espejo Mágico | 530€ | |
| Plataforma 360º | 500€ | |
| Aéreo 360º | 800€ | hasta 15 personas |
| Cabina Hinchable LED XXL | 530€ | |
| Cabaña Rústica | 800€ | |

### Complementos por categoría
**Decoración:** Estructura 100€ · Fondo Jardín 150€ · Shimmer Wall 150€ · Neón 80€ · Sofá 100€ · Alfombra 25€.
  (Para "Estructura + Neón": estructuras y neones como grupos "elige uno".)
**Recuerdos:** Audiolibro de firmas 90€ · Audiolibro + Vídeo 200€ · Letras madera Iniciales 270€ ·
  Letras madera LOVE 270€ · Pack Letras (Iniciales+LOVE) 370€.
**Animación:** Glitter Corner 3h 1 maquilladora 380€ (2 maq 530€ / hora extra 50€ por maq) ·
  Hora Loca Pack A 900€ / Pack B 1100€ / Pack C 1350€ (grupo elige-uno). Extras hora loca:
  100 jeringas shot 50€, inflable temático 40€, animador extra 150€, 2 pistolas LED + CO2 400€.
**Dulces y comida:** Mesa Dulce 200€ (solo golosinas) / 280€ (con 40 donuts) · Kiosko Moita Troula 400€ ·
  Puestos de comida (palomitero, algodonero, hot-dog, gofres, crepes, helados, burguers) → **a_consultar**.
**Juegos:** Futbolín 140€/día · Máquina Arcade 110€/día.
**Detalles:** Portafoto imán (4,40/4,00/3,60 por tramos) → **a_consultar** · Invitaciones madera
  (5,20/4,80/4,40 por tramos) → **a_consultar**.

### Packs
Base por defecto = Fotomatón (3h). **Suplementos por cambiar la máquina base** (para `pack_experiencia`):
Photocall Tela +30€ · Cabina Hinchable LED XXL +80€ · Espejo Mágico +80€ · Estructura+Neón +150€ ·
Neón+Estructura+Sofá +250€ · Plataforma 360º +50€ · Cabaña Rústica +300€.

Packs Ahorro:
- **Bronce 700€** = Fotomatón 3h + Letras iniciales.
- **Glitter 790€** = Fotomatón 3h + Glitter Corner 3h 1 maq.
- **Oro 970€** = Fotomatón 3h + Letras iniciales + Glitter Corner 3h 1 maq.
- **Full Fotomatón 900€** = Fotomatón 3h + Plataforma 360 3h. (extras: Futbolín +130€, Arcade +100€)

Packs Locos (Hora Loca Pack A incluida; upgrade Pack B +150€ / Pack C +250€):
- **Glitter Loco 1200€** = Glitter Bar 3h + Hora Loca A.
- **360 Loco 1350€** = Plataforma 360 + Hora Loca A.
- **Fotomatón Loco 1300€** = Fotomatón 3h + Hora Loca A.
- **Fiesta Loca 1650€** = Fotomatón o 360 + Glitter Bar + Hora Loca A.
- **Locura Total 2100€** = Fotomatón + 360 + Glitter Bar + Hora Loca A.

## Tareas creadas en la sesión anterior (retomar)
1. Migraciones y modelos del catálogo real
2. Motor de precios: horas extra, a consultar, suplemento de pack
3. Panel Filament: campos y gestores nuevos
4. Configurador: horas extra, elige-uno, a consultar, base de pack
(faltan crear: 5. Seeder catálogo real + branding, 6. Tests, 7. Deploy + carga en producción)
