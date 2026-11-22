# Diseño: flujo de programación de rutas (API + app Logymex Entregas)

Fecha: 2026-11-22 · Repos: `api-logymex` (Laravel 11 + Sanctum + Spatie) y `LogymexEntregas` (Android, Kotlin + Compose + Hilt + Retrofit)

## 1. Intención

Logymex Ambiental recolecta residuos peligrosos. Hoy la operación se programa fuera del sistema. Se quiere que:

- **Director General (DG) y Jefe de Logística (JL)** programen desde la app el servicio completo: capturan la orden de servicio, y arman la **ruta del día** de un operador (unidad compatible + lista ordenada de órdenes/clientes a visitar).
- **El Operador** vea en la app su ruta del día y la vaya ejecutando dentro de sus permisos.

### Vocabulario (definido por el usuario)
- **Orden de servicio**: el documento físico. Tiene **un** cliente, tipo de servicio, residuos, ejecutivo de ventas y operador(es).
- **Ruta**: la programación del día de un operador: fecha + unidad + operador (+ ayudante) + recorrido ordenado de varias órdenes.
- **Parada**: cada orden dentro de una ruta, con su posición (secuencia) y su propio estatus.

## 2. Objetivos, no-objetivos, anti-objetivos

**Objetivos**
1. Una ruta contiene N órdenes ordenadas (paradas).
2. DG/JL crean y editan órdenes, rutas, unidades y clientes desde la app.
3. Al armar una ruta solo se pueden asignar unidades compatibles con el tipo de servicio de cada orden.
4. El operador ve su ruta del día con sus paradas y actualiza estatus de ruta y de cada parada.

**No-objetivos (fases posteriores)**
- Dashboard / calendario del DG (se construirá a partir de rutas).
- Pantallas de incidencias en la app (el endpoint ya existe).
- Rol ventas en la app.
- Corrección de la escalada de privilegios de `jefe_logistica` en `UsuarioController` (ticket aparte, ver §7).
- Refactor de autorización a Policies.

**Anti-objetivos** (sería un fracaso aunque “funcione”)
- Que el operador pueda ver o modificar rutas/órdenes que no son suyas.
- Que se capture la misma información dos veces (por eso se elimina `servicios_programados`).
- Que se pierda el historial cuando una orden se reprograma a otro día.

## 3. Restricciones
- Autorización actual: checks de rol en cada controlador (`hasRole`/`hasAnyRole`). Se conserva el patrón.
- Respuestas JSON con forma `{ mensaje, datos }`. Se conserva.
- La app ya tiene: login, `TokenManager` (DataStore), `AuthInterceptor`, y un `HomeScreen` de operador que hoy trata cada `Ruta` como una parada.
- `BASE_URL` debug: `http://10.0.2.2:8000/api/`.

## 4. Enfoques considerados

| | Enfoque | Pros | Contras |
|---|---|---|---|
| **A (elegido)** | Tabla intermedia `ruta_orden` con `secuencia` y estatus de parada | Una orden puede pasar por varias rutas (reprogramación con historial); estatus por parada separado del de la ruta | Migración nueva + reescritura de `RutaController` |
| B | `ruta_id` en `ordenes_servicio` | Mínimo cambio | Orden en una sola ruta; se pierde historial; mezcla estatus de parada con el de la orden |
| C | Ruta = visita, nueva tabla `jornadas` | No toca lo existente | “Ruta” deja de significar lo que el negocio entiende; más tablas/endpoints |

Elegido por el usuario: **A**.

## 5. Diseño — API

### 5.1 Modelo de datos

**Migración nueva `create_ruta_orden_table`**
```
ruta_orden
  id
  ruta_id            FK rutas, cascadeOnDelete
  orden_servicio_id  FK ordenes_servicio, restrictOnDelete
  secuencia          unsignedSmallInteger   (1..N)
  estatus            string default 'pendiente'   -- pendiente | completada | no_atendida
  observaciones      text nullable
  atendida_en        timestamp nullable
  timestamps
  unique(ruta_id, orden_servicio_id)
  unique(ruta_id, secuencia)
```

**Migración `ajustar_rutas_para_paradas`**
1. Por cada ruta existente, insertar una fila en `ruta_orden` (secuencia 1, estatus derivado: `finalizada`→`completada`, otro→`pendiente`).
2. Quitar `rutas.orden_servicio_id` y `rutas.domicilio` (el domicilio sale de `cliente.domicilio`).
3. `fecha` pasa a requerida a nivel validación (columna se deja nullable para no romper datos viejos).

**Migración `eliminar_servicios_programados`**: drop de la tabla. Se borran `ServicioProgramado` (modelo), `ServicioProgramadoController`, `ServicioProgramadoSeeder` y sus rutas en `api.php`. Las incidencias no dependen de ella.

**Modelos**
- `Ruta::ordenes()` → `belongsToMany(OrdenServicio, 'ruta_orden')->withPivot(['id','secuencia','estatus','observaciones','atendida_en'])->orderByPivot('secuencia')->withTimestamps()`.
- `OrdenServicio::rutas()` pasa de `hasMany` a `belongsToMany` inverso.
- Se elimina `Ruta::ordenServicio()`.

### 5.2 Estatus y transiciones

**Ruta** (sin cambios): `pendiente → iniciada → pausada ⇄ iniciada → finalizada`. Solo el operador asignado mueve estos estatus desde la app; DG/JL pueden fijar cualquiera.

**Parada**: `pendiente → completada` o `pendiente → no_atendida`. Solo con la ruta en `iniciada`. Una ruta solo puede pasar a `finalizada` cuando no quedan paradas `pendiente`.

**Sincronización con la orden** (`ordenes_servicio.estatus`):
| Evento | Estatus de la orden |
|---|---|
| Se agrega a una ruta | `asignado` (y `operador_id` = operador de la ruta) |
| La ruta pasa a `iniciada` | `en_ruta` (solo las paradas pendientes) |
| Parada `completada` | `completado` + `completado_en` |
| Parada `no_atendida` | `pendiente` (queda libre para reprogramar en otra ruta) — confirmado por el usuario |
| Se quita de una ruta antes de iniciar | `pendiente`, `operador_id` = null |

### 5.3 Reglas de negocio al armar/editar una ruta (DG/JL)
1. `fecha`, `operador_id` requeridos; `unidad_id` requerido; `ayudante_id`, `hora_inicio`, `hora_fin`, `observaciones` opcionales.
2. `operador_id` y `ayudante_id` deben tener rol `operador`; deben ser distintos.
3. Un operador no puede tener dos rutas en la misma fecha. Una unidad tampoco.
4. Unidad con `estatus` ≠ `disponible` no se puede asignar.
5. Cada orden agregada debe estar en estatus `pendiente` (no asignada a otra ruta activa) y no `cancelado`/`completado`.
6. **Compatibilidad unidad–tipo de servicio**: única regla (confirmada por el usuario): unidad `RPBI` solo acepta órdenes `RPBI`; se aplica a **cada** orden de la ruta. Cualquier otra unidad acepta cualquier tipo.
7. Las paradas solo se pueden editar (agregar/quitar/reordenar) mientras la ruta está en `pendiente`.
8. Todo en una transacción; errores de regla → 422 con `mensaje` claro (y `errores` por campo cuando aplique, formato estándar de Laravel).

### 5.4 Endpoints

| Método | Ruta | Quién | Descripción |
|---|---|---|---|
| GET | `/rutas?fecha=YYYY-MM-DD&operador_id=` | DG, JL; operador (solo suyas, filtro forzado) | Lista con `ordenes.cliente`, `ordenes.residuos`, `unidad`, `operador`, `ayudante`, conteos de paradas |
| GET | `/rutas/hoy` | operador | Atajo: su ruta de la fecha actual (o `datos: null`) |
| POST | `/rutas` | DG, JL | Body: `{fecha, unidad_id, operador_id, ayudante_id?, hora_inicio?, hora_fin?, observaciones?, ordenes: [orden_id,...]}` — el orden del arreglo es la secuencia |
| GET | `/rutas/{ruta}` | DG, JL; operador si es suya | Detalle con paradas |
| PUT/PATCH | `/rutas/{ruta}` | DG, JL (todo); operador (solo `estatus`, `observaciones`, con transiciones) | Si viene `ordenes`, se resincronizan paradas (regla 7) |
| PATCH | `/rutas/{ruta}/paradas/{orden}` | operador asignado; DG, JL | Body: `{estatus: completada\|no_atendida, observaciones?}` |
| DELETE | `/rutas/{ruta}` | DG | Solo si `pendiente`; las órdenes vuelven a `pendiente` |
| GET | `/ordenes-servicio?estatus=pendiente&tipo_servicio=` | DG, JL | Nuevo filtro por query para el selector al armar rutas |
| GET | `/unidades?disponible_para=YYYY-MM-DD&tipo_servicio=` | DG, JL | Nuevo filtro: unidades libres esa fecha y compatibles |
| GET | `/usuarios?rol=operador` | DG, JL | Nuevo filtro por rol para elegir operador/ayudante |

Respuesta de parada dentro de la ruta (JSON):
```json
{ "id": 12, "folio": "OS-0045", "tipo_servicio": "RPBI", "cliente": {...}, "residuos": [...],
  "pivot": { "secuencia": 2, "estatus": "pendiente", "observaciones": null, "atendida_en": null } }
```

### 5.5 Pruebas (API)
Feature tests con `RefreshDatabase` para: crear ruta con 3 órdenes; rechazo por unidad RPBI con orden no RPBI; rechazo de operador con dos rutas el mismo día; operador no ve ruta ajena (403); transiciones de parada y bloqueo de `finalizada` con paradas pendientes; sincronización de estatus de la orden; migración de datos viejos (ruta existente → 1 parada).

## 6. Diseño — App Android

### 6.1 Navegación por rol
Tras login se lee `usuario.roles[0].name` (ya llega en `/login` y `/perfil`), se guarda en `TokenManager` y se navega:
- `operador` → `OperadorGraph`
- `director_general` / `jefe_logistica` → `GestionGraph`
- otro rol → pantalla “Tu rol aún no tiene acceso a la app” + cerrar sesión.

Se agrega `POST /logout` al cerrar sesión (hoy solo borra el token local).

### 6.2 Vista del Operador (`OperadorGraph`)
Reutiliza el diseño actual de `HomeScreen`, adaptado a ruta → paradas:
```
┌ Ruta de hoy · 22 nov ───────────────┐
│ Unidad ECO-07 · RPBI   Ayudante: Luis│
│ [ Iniciar ruta ]   2/5 completadas  │
├──────────────────────────────────────┤
│ 1 Hospital San José     ✅ completada│
│ 2 Clínica Norte         ⏳ pendiente │
│   Av. Juárez 120 · [Navegar] [Llamar]│
│   RPBI: 30 kg punzocortantes         │
│   [Completar]  [No atendida]         │
│ 3 Lab. Central          ⏳ pendiente │
└──────────────────────────────────────┘
```
- Botón de ruta según transición válida: Iniciar / Pausar / Reanudar / Finalizar (Finalizar deshabilitado si hay paradas pendientes).
- Botones de parada solo con ruta `iniciada`. “No atendida” pide observaciones (obligatorio).
- Detalle de parada: folio, cliente (domicilio, teléfono, contacto), tipo de servicio, residuos con cantidades, observaciones de la orden.
- Sin ruta hoy: estado vacío. Pestaña secundaria “Próximas”: rutas con fecha futura, solo lectura.
- `HomeScreen.kt` (~34 KB) se divide en archivos por componente dentro de `ui/operador/`.

### 6.3 Vista de gestión DG/JL (`GestionGraph`)
Barra inferior: **Rutas · Órdenes · Unidades · Clientes**.

- **Rutas**: lista por fecha (selector de día), tarjeta con operador, unidad, avance de paradas, estatus. FAB “Programar ruta”.
- **Programar ruta** (asistente de 4 pasos, misma pantalla para editar mientras esté `pendiente`):
  1. Fecha + operador + ayudante (solo usuarios rol operador; operadores ya ocupados ese día aparecen deshabilitados).
  2. Unidad (solo disponibles ese día; muestra tipo de servicio).
  3. Órdenes: lista de órdenes `pendiente`; las incompatibles con la unidad aparecen deshabilitadas con el motivo. Selección múltiple.
  4. Orden del recorrido: lista reordenable (arrastrar o flechas ↑↓) + resumen → Guardar.
  El servidor vuelve a validar todo; los 422 se muestran en el paso correspondiente.
- **Detalle de ruta**: paradas y su estatus en vivo (pull-to-refresh), editar (si `pendiente`), eliminar (solo DG, si `pendiente`).
- **Órdenes**: lista con filtro por estatus; formulario: folio, cliente (buscador), tipo de servicio, ejecutivo de ventas (usuarios rol ventas), fecha/hora programada, descripción, observaciones, residuos (agregar filas: residuo, cantidad, unidad de medida).
- **Unidades**: lista + formulario (placas, número económico, marca, modelo, tipo de servicio, estatus, operador habitual).
- **Clientes**: lista con búsqueda + formulario (nombre, domicilio, teléfono, correo, contacto, preferente, estatus).
- Eliminar en cualquier catálogo: solo visible para DG.

### 6.4 Capas (se sigue el patrón actual MVVM + Hilt)
- `network/ApiService`: endpoints de §5.4 y CRUD de órdenes, unidades, clientes, residuos (catálogo), usuarios (filtro por rol).
- `data/`: un repositorio por recurso (`RutaRepository`, `OrdenRepository`, `UnidadRepository`, `ClienteRepository`, `CatalogoRepository`). Resultados como `Result<T>` con el `mensaje` de la API en errores.
- `ui/operador/`, `ui/gestion/rutas/`, `ui/gestion/ordenes/`, `ui/gestion/unidades/`, `ui/gestion/clientes/`, cada pantalla con su ViewModel y `UiState`.
- Modo offline: ver §6.5.

### 6.5 Modo offline y sincronización (requisito del usuario)
**Alcance confirmado por el usuario: offline solo para el operador.**
- **Operador: offline completo.** Al abrir la app con red se descargan su ruta de hoy y las próximas a Room (SQLite). Sin red, la app lee de Room.
- Cada acción del operador (iniciar/pausar/finalizar ruta, completar/no atendida en parada) se aplica de inmediato en Room y se guarda en una **cola de salida** (`acciones_pendientes`: id UUID, tipo, payload, `ocurrido_en` del dispositivo).
- **WorkManager** envía la cola en orden cuando hay red (restricción `CONNECTED`, reintento exponencial). Indicador en la barra: “3 cambios sin enviar”.
- API: los endpoints del operador aceptan `ocurrido_en` (se guarda como `atendida_en` / hora real) y un encabezado `Idempotency-Key` (UUID) para que un reenvío no duplique. Nueva tabla `acciones_procesadas(key, respuesta, created_at)`.
- Conflictos: las paradas solo se editan con la ruta `pendiente` y el operador solo actúa con la ruta `iniciada`, así que DG/JL y operador no editan lo mismo a la vez. Si el servidor rechaza una acción (422/403), se marca en la cola como fallida y se muestra al operador; Room se refresca desde el servidor.
- Validación local: las mismas transiciones de §5.2 se aplican en el dispositivo para que el operador no pueda generar acciones inválidas sin red.
- **Gestión (DG/JL): solo en línea** (confirmado): lectura en caché para consultar, pero crear/editar requiere red, porque las reglas de §5.3 (operador/unidad ocupados, órdenes libres) dependen del estado global del servidor.

## 7. Riesgos y notas
- **Escalada de privilegios** (fuera de alcance, recomendado como siguiente ticket): `jefe_logistica` puede cambiar email/password de cualquier usuario, incluido el DG.
- Borrar archivos en la carpeta de la API desde el asistente requiere permiso de borrado; si no se concede, se mueven a `_to_delete/`.
- Hay cambios sin commit en la API (`git status`): conviene hacer commit antes de empezar.

## 8. Preguntas abiertas (para el usuario)
- ~~P1~~ Parada no atendida → la orden vuelve a `pendiente`. (Respondida)
- ~~P2~~ Solo la regla RPBI. (Respondida)
- ~~P3~~ La app debe funcionar offline con sincronización. (Respondida → §6.5)
- ~~P4~~ Offline solo para el operador; gestión en línea. (Respondida)

## 9. Decisiones tomadas por el asistente (revisables)
Estatus de parada (`pendiente/completada/no_atendida`); sincronización de estatus de la orden (§5.2); un operador y una unidad por día; ayudante debe tener rol operador; editar paradas solo con ruta `pendiente`; eliminar `servicios_programados`; asistente de 4 pasos; usuarios (CRUD) fuera de esta fase salvo el filtro por rol; logout contra la API.
