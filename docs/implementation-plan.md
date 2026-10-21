# Plan de Implementación — Portal de Noticias (CMS)

> Estado: **Fase 1, Fase 2, Fase 3 y Fase 4 completadas.**
> Última actualización: Fase 4.

## 1. Objetivo

Adaptar la aplicación Laravel existente a un **CMS de noticias con panel de administración**,
sin recrear el proyecto ni reemplazar la configuración de base de datos actual.

## 2. Stack detectado (Fase 1)

| Componente | Versión |
| --- | --- |
| PHP | 8.5.10 |
| Laravel | 13.37.0 |
| Composer | 2.10.3 |
| Base de datos (local) | MySQL/MariaDB `news` (MariaDB 12.3.3) |
| Frontend build | Vite 8 + Tailwind CSS 4 (`@tailwindcss/vite`) |
| Alpine.js | 3.x (vía Breeze) |
| Testing | Pest 5.3 + pest-plugin-laravel, PHPUnit 13.4 |
| Formato | Laravel Pint 1.32 |
| Dev tools | Laravel Boost, Pail, Pao, Collision |

## 3. Estado inicial (antes de Fase 1)

- Esqueleto Laravel casi virgen: `routes/web.php` con una sola ruta `/` → `welcome`.
- Modelo `User` + migración por defecto (`users`, `password_reset_tokens`, `sessions`).
- Migraciones `cache` y `jobs`. Factories por defecto.
- Auth configurada a nivel de `config/auth.php` (guard `web`, provider `users`), **sin scaffolding**.
- Sin paquetes de roles/permisos, sin lógica de dominio.
- 2 tests de ejemplo pasando (con sqlite in-memory).

## 4. Decisiones técnicas aprobadas

1. **Alcance**: CMS de noticias con panel admin (posts/noticias + categorías/tags/autoría).
2. **Autenticación**: Laravel Breeze (Blade + Tailwind). Cubre login, registro,
   verificación de email, recuperación y confirmación de contraseña.
3. **Autorización/roles**: columna simple `role` en `users` + enum PHP (`App\Enums\UserRole`)
   `Admin` / `Editor` / `Author`. Sin paquete de permisos por ahora. Gate + Policy nativas.
4. **Base de datos de desarrollo**: MySQL `news` (ya creada y migrada). Se conserva el `.env`.
5. **Base de datos de tests**: sqlite in-memory (config por defecto de Laravel en `phpunit.xml`).

## 5. Cambios aplicados en Fase 1

- **Base `news` creada y migrada** (tablas por defecto: `users`, `cache`, `jobs`,
  `password_reset_tokens`, `sessions`, `migrations`, etc.). Conexión verificada vía Boost.
- **Laravel Breeze instalado** (`laravel/breeze ^2.4`, dev) y scaffolding `blade` publicado:
  controladores en `app/Http/Controllers/Auth`, `ProfileController`, vistas en
  `resources/views/auth`, `layouts`, `profile`, `components`, ruta `routes/auth.php` y
  `dashboard`.
- **Integración Tailwind 4 preservada**: Breeze instaló Tailwind 3
  (`tailwind.config.js`, `postcss.config.js`, `@tailwindcss/forms` en `package.json`).
  Se restauró el setup original del proyecto:
  - `package.json` vuelve a `tailwindcss ^4` + `@tailwindcss/vite` (se mantiene `alpinejs` y
    `@tailwindcss/forms`).
  - `vite.config.js` restauró el plugin `tailwindcss()` y las fuentes Bunny (`Instrument Sans`).
  - `resources/css/app.css` usa `@import 'tailwindcss'` + `@plugin '@tailwindcss/forms'` +
    `@source` + `@theme`.
  - Eliminados `tailwind.config.js` y `postcss.config.js` (Tailwind 3).
- **Layouts ajustados**: se retiró el `<link>` a Figtree CDN de `layouts/app.blade.php` y
  `layouts/guest.blade.php`; ahora usan `--font-sans: 'Instrument Sans'` vía Vite/Bunny.
- **Build verificado**: `npm run build` compila Tailwind 4, Alpine, y las fuentes Bunny.

## 6. Bloqueo de entorno (resuelto en Fase 2)

`pdo_sqlite` no está habilitado en el PHP del sistema (`PDO drivers => mysql, pgsql`).
**Resuelto** cambiando `phpunit.xml` a MySQL (`news_test`), sin tocar el sistema.

---

## 8. Dependencias añadidas y justificación

| Dependencia | Tipo | Justificación |
| --- | --- | --- |
| `laravel/breeze ^2.4` | dev | Scaffolding de autenticación Blade+Tailwind (decisión aprobada). Es solo de desarrollo: publica código al proyecto. |

> Nota: `laravel/breeze` aparece en `composer.json` (bloque `require-dev`). El código
> generado no depende del paquete en runtime; puede retirarse tras el scaffolding si se desea.

## 10. Fase 2 — Autenticación, usuarios y permisos (completada)

### 10.1 Entorno de pruebas
- `phpunit.xml` apunta a **MySQL** (`news_test`) en lugar de sqlite (el driver `pdo_sqlite`
  no está habilitado en el sistema). Base `news_test` creada.
- Suite completa: **45 tests, 104 aserciones, todas en verde**.

### 10.2 Usuarios: campos, enums y helpers
- Migración incremental `add_role_and_status_to_users_table`: `role` (index), `status` (index),
  `suspended_at` (nullable). Reversible.
- Enums `App\Enums\UserRole` (`Admin`, `User`) y `App\Enums\AccountStatus`
  (`Active`, `Suspended`, `PendingVerification`), con cast en `User`.
- Helpers: `isAdmin()`, `isActive()`, `isSuspended()`, `isPendingVerification()`.
- **Sin** verificación por email (`User` no implementa `MustVerifyEmail`), según decisión.

### 10.3 Dominio (aislamiento por usuario)
- Modelos + migraciones + factories: `Topic`, `Preference`, `NewsItem`, `Subscription`.
- Relaciones en `User`: `topics`, `preference`, `newsItems`, `subscriptions`,
  `subscribedTopics`. Seeder de ejemplo actualizado.

### 10.4 Autorización server-side
- `Gate::before` en `AppServiceProvider`: los `Admin` reciben todas las habilidades.
- Policies: `UserPolicy`, `TopicPolicy`, `PreferencePolicy`, `NewsItemPolicy`,
  `SubscriptionPolicy`. Un usuario normal solo accede a sus propios recursos.
- Middlewares registrados en `bootstrap/app.php`:
  - `admin` (`EnsureUserIsAdmin`) → grupo de rutas `/admin` (403 para no admins).
  - `readonly` (`BlockSuspendedWrites`) → bloquea métodos de escritura (POST/PUT/PATCH/DELETE)
    para cuentas suspendidas, permitiendo lectura.
- Regla admin→admin: `UserPolicy::suspend` impide que un admin suspenda a otro admin.

### 10.5 Estado de cuenta (suspensión = solo lectura)
- `active`: acceso normal.
- `suspended`: **puede iniciar sesión y leer**, pero cualquier escritura responde 403.
  Datos conservados (`status` + `suspended_at`) para auditoría. Nunca se borran.
- `pending_verification`: reservado (no usado en esta fase, sin verificación por email).

### 10.6 Registro
- Nuevos usuarios: `role = User`, `status = Active`.

### 10.7 Pruebas añadidas
- `AuthorizationTest`: acceso a `/admin` (guest→redirect, user→403, admin→200), Gate admin,
  aislamiento en perfil.
- `UserIsolationTest`: un usuario no ve/edita topic/news/preferences/subscription de otro.
- `SuspendedAccountTest`: suspendido autentica, lee, pero escritura → 403.
- `AccountStatusTest`: registro por defecto, helpers, propiedad del dominio, reglas de suspensión.
- Se conservan los 24 tests de Breeze.

## 11. Fase 3 — Temas de vigilancia por usuario (completada)

### 11.1 Decisiones aprobadas
- Cada usuario define temas de vigilancia **independientes** con sus propias reglas,
  keywords, fuentes, frecuencias y notificaciones.
- `topics` (Fase 2) **se renombra y evoluciona** a `monitored_topics` (Opción A, sin datos
  reales que perder). `NewsItem.topic_id` y `Subscription.topic_id` reapuntados.
- "Ejecutar búsqueda" en esta fase **solo registra la ejecución** (crea `topic_runs`, actualiza
  fechas). La integración con un proveedor real queda como punto de extensión mockeable.
- Límites de intervalo personalizado fijados por el admin vía `config/monitoring.php`
  (min **15 min**, max **30 días**).
- `NewsItem` pasa a representar el resultado de búsqueda asociado a un `monitored_topic`.
- `notify_push` es un flag persistido, sin canal push real.
- `Subscription` se mantiene como "usuario vigila tema".

### 11.2 Modelo de datos
- `monitored_topics`: `user_id`, `name`, `slug` (único **por usuario**), `description`,
  `language` (ISO-639-1), `country` (ISO-3166, nullable), `frequency`, `custom_interval_minutes`,
  `status`, `last_run_at`, `next_run_at`, `notify_email`, `notify_push`,
  `relevance_preferences` (JSON). Índices `[user_id, status]` y `[status, next_run_at]`.
- `topic_keywords`: `monitored_topic_id`, `type` (include/exclude), `value`; único por
  `[topic, type, value]`.
- `topic_sources`: `monitored_topic_id`, `type` (preferred/excluded), `domain`; único por
  `[topic, type, domain]`.
- `topic_runs`: `monitored_topic_id`, `trigger` (scheduled/manual), `status`
  (pending/running/success/failed), `results_count`, `error_message`, `started_at`,
  `finished_at`; índice `[monitored_topic_id, created_at]`.
- `news_items` ampliado: `url`, `source_domain`, `relevance`, `topic_run_id`.

### 11.3 Enums y configuración
- `App\Enums\`: `TopicStatus`, `SearchFrequency` (con `intervalMinutes()`/`nextRunFrom()`),
  `KeywordType`, `SourceType`, `RunTrigger`, `RunStatus`. Todos TitleCase con backing string.
- `config/monitoring.php`: límites de intervalo, frecuencias permitidas, relevancia por
  defecto y paginación. Cast `App\Support\RelevancePreferences` (Castable) para el JSON.

### 11.4 Aplicación
- `MonitoredTopicController` (resource) + acciones `pause`, `resume`, `run`.
- Form Requests `StoreMonitoredTopicRequest` / `UpdateMonitoredTopicRequest`: validación de
  frecuencia, intervalo personalizado dentro de límites, idioma/país ISO, keywords y dominios
  (texto multilínea parseado), formato de dominio.
- Rutas bajo `['auth', 'readonly']` (suspendido lee, no escribe). `Controller` base usa
  `AuthorizesRequests`.
- `MonitoredTopicPolicy` (view/create/update/delete/pause/resume/run) con ownership;
  el `Gate::before` de admin se mantiene.
- `App\Actions\RunMonitoredTopicSearch`: registra el `topic_run`, recalcula `last_run_at`.
  Comando `topics:run-due` (scope `due()`) programado cada 15 minutos en `routes/console.php`.
- Vistas `topics/index|create|edit|show` (historial + próxima ejecución) con Tailwind 4 y
  componentes Breeze; enlace "Topics" en la navegación.

### 11.5 Pruebas añadidas
- `MonitoredTopicTest`: CRUD + ownership (403), aislamiento en índice, slug único por usuario,
  validación de intervalo personalizado y dominios, pausar/reactivar, run manual (crea
  `TopicRun` + refresca fechas), suspendido readonly, admin gestiona temas ajenos, scope `due()`,
  historial visible.
- Suite completa: **62 tests, 165 aserciones, todas en verde** (MySQL `news_test`).

## 12. Fase 4 — Proveedores de búsqueda web reales (completada)

### 12.1 Objetivo
Sustituir el placeholder del Action de Fase 3 por una integración real con múltiples
proveedores de búsqueda, persistiendo resultados como `NewsItem` vinculados al run y al
tema, con credenciales gestionables desde el panel admin.

### 12.2 Proveedores soportados (6)
| Proveedor | Clave | Capacidades | Estado |
| --- | --- | --- | --- |
| RSS | `rss` | KeywordFilter | Funciona sin credenciales (feeds configurados) |
| Brave | `brave` | DateFilter, LanguageFilter, KeywordFilter | Requiere `BRAVE_SEARCH_API_KEY` |
| Tavily | `tavily` | DateFilter, DomainFilter, KeywordFilter | Requiere `TAVILY_API_KEY` |
| Google CSE | `google_cse` | DateFilter, LanguageFilter, DomainFilter, KeywordFilter | Requiere `GOOGLE_CSE_KEY` + `GOOGLE_CSE_CX` |
| DuckDuckGo | `duckduckgo` | KeywordFilter | Scraping; puede devolver 403 desde servidores (anti-bot) |
| OpenRouter | `openrouter` | LanguageFilter, KeywordFilter | Requiere `OPENROUTER_API_KEY` con saldo; modelo `openrouter/auto:online` con plugin `web` |

### 12.3 Arquitectura
- `app/Contracts/WebSearchProvider.php`: contrato (`key`, `supports`, `isConfigured`, `search`).
- `app/DTO/{WebSearchQuery,SearchResult}.php`: entrada normalizada y resultado.
- `app/Enums/SearchCapability.php`: capacidades filtrables por proveedor.
- `app/Exceptions/SearchProviderException.php`: error de proveedor con prefijo `[key]`.
- `app/Services/Search/Providers/*`: `AbstractWebSearchProvider` + 6 adaptadores. HTTP vía
  `Illuminate\Support\Facades\Http` con timeout/retry; errores de transporte normalizados.
- `app/Services/Search/CredentialRepository.php` + `app/Models/SearchCredential.php`:
  credenciales cifradas en BD (preferidas) sobre `config/services` (env).
- `app/Services/Search/ProviderRegistry.php`: ejecuta los proveedores habilitados, fusiona y
  deduplica por URL (`domain|title` como respaldo), recogiendo errores sin abortar el run.
- `app/Actions/RunMonitoredTopicSearch.php`: construye la query, ejecuta el registry, persiste
  `NewsItem`, actualiza `results_count`/`last_run_at`/`next_run_at`; en fallo marca el run como
  `Failed` con `error_message` saneado (regex `sk-...` → `[redacted]`).

### 12.4 Configuración
- `config/search.php`: proveedores, `max_results`, timeout, retries, backoff, feeds RSS, user agent.
- `config/services.php`: bloques `brave`, `tavily`, `google_cse`, `openrouter`.
- `.env` / `.env.example`: claves de API (solo `.env` local; `.env.example` vacío).
- Panel admin: `admin.search.settings` (GET/PUT), `SearchSettingRequest`, vista
  `admin/search-settings.blade.php`. Los valores nunca se reimprimen (solo indican "configured").

### 12.5 Pruebas añadidas
- `tests/Feature/SearchProviderTest.php`: parseo/errores por adaptador con `Http::fake` (8 tests).
- `tests/Feature/SearchIntegrationTest.php`: registry, dedupe, persistencia, saneado de errores,
  UI admin (cifrado, no exposición, 403) (7 tests).
- `tests/Feature/SearchProviderIntegrationTest.php`: integración real gated `->group('integration')`,
  **excluida** del suite por defecto en `phpunit.xml`; se ejecuta con
  `vendor/bin/pest tests/Feature/SearchProviderIntegrationTest.php --group=integration`. Salta
  limpiamente si falta clave (Brave), si el servidor está bloqueado (DuckDuckGo 403) o si no hay
  saldo (OpenRouter 402).

### 12.6 Notas operativas
- Los adaptadores en tests usan `Http::fake`; ningún test por defecto toca la red.
- `MonitoredTopicTest` "manual run" usa `Http::fake` para aislar la llamada real al registry.
- Limitación real observada: DuckDuckGo responde 403 a peticiones de servidor y la cuenta de
  OpenRouter usada devolvió 402 (sin saldo). Ambas son condiciones externas, no defectos de código.

## 13. Fase 5 — Sistema multi-proveedor de IA (completada)

### 13.1 Objetivo
Permitir cambiar de proveedor/modelo de IA (OpenAI, OpenRouter, Anthropic) sin modificar los
servicios de temas, resultados ni notificaciones. Toda la variabilidad (auth, formato de
petición/respuesta, errores y capacidades) queda encapsulada tras el contrato `AIProvider`.

### 13.2 Decisiones aprobadas
- **Claves**: solo el administrador de la plataforma; gestionadas en `/admin/ai`; cifradas en BD.
- **Ejecución**: post-proceso síncrono al final de `RunMonitoredTopicSearch`.
- **Configuración**: tabla `ai_settings` (una fila activa) con fallback a `config/ai.php`/`.env`.
- **Persistencia IA**: columnas en `news_items` (`ai_summary`, `ai_relevance`, `ai_duplicate_of_id`, `ai_analyzed_at`).
- **Proveedores**: OpenAI, OpenRouter y Anthropic; búsqueda integrada opcional según capacidades.

### 13.3 Arquitectura
- `app/Contracts/AIProvider.php`: contrato (`key`, `supports`, `isConfigured`, `analyze`).
- `app/Enums/{AIOperation,AICapability,AISearchMode}.php`: operaciones, capacidades y método de búsqueda.
- `app/DTO/{AIAnalysisRequest,AIAnalysisResult,ProviderConfig}.php`: entrada, salida y config resuelta.
- `app/Exceptions/AIProviderException.php`: error con prefijo `[provider]`.
- `app/Services/AI/Providers/*`: `AbstractAIProvider` + `OpenAIAnalysisProvider`,
  `OpenRouterAnalysisProvider`, `AnthropicAnalysisProvider`. Cada uno con su auth/formato
  (OpenAI/OpenRouter `chat/completions`; Anthropic `/v1/messages` con `system` separado y
  `max_tokens` obligatorio) y su conjunto de capacidades.
- `app/Services/AI/StructuredOutputValidator.php`: validación de salida JSON contra un schema mínimo.
- `app/Services/AI/AIConfigRepository.php`: resuelve la config activa (BD > config/env); `put()` persiste la fila activa.
- `app/Services/AI/AIProviderRegistry.php`: resuelve/ejecuta el proveedor configurado y aplica
  **fallback** si el principal lanza `AIProviderException`.
- `app/Services/AI/AiAnalysisService.php`: resume, clasifica relevancia, detecta duplicados y
  extrae estructurados; fallos de IA se registran saneados y no rompen el run.

### 13.4 Integración en el pipeline
`RunMonitoredTopicSearch::execute()`: tras persistir los `NewsItem`, si `ai.analyze_after_search`
está activo, invoca `AiAnalysisService::analyzeRun()`. La búsqueda, los temas y las notificaciones
no cambian su API: solo se enriquecen los items. Un fallo de IA deja el run en `Success`.

### 13.5 Modelo de datos
- Migración `2026_10_20_213000_create_ai_settings_table`: proveedor, modelo, método de búsqueda,
  parámetros (json), límites (max_tokens, timeout) y fallback.
- Migración `2026_10_20_213001_add_ai_columns_to_news_items_table`: `ai_summary`, `ai_relevance`
  (index), `ai_duplicate_of_id` (FK self, nullOnDelete), `ai_analyzed_at`.

### 13.6 Configuración y panel admin
- `config/ai.php`: proveedores activos, defaults de provider/modelo/búsqueda, límites, retries,
  `analyze_after_search`, fallback y user agent.
- `config/services.php`: bloques `openai`, `anthropic` (añadidos); `openrouter` reutilizado.
- `.env` / `.env.example`: `AI_*`, `OPENAI_*`, `ANTHROPIC_*`.
- Panel admin `admin.ai.settings*` (`AiSettingController`, `AiSettingRequest`, vista
  `admin/ai-settings.blade.php`): selección de proveedor/modelo/método de búsqueda, límites,
  fallback y claves (password, "configured", nunca reimpresas).

### 13.7 Seguridad
- Claves cifradas con cast `encrypted` (reutiliza `search_credentials`).
- Valores solo tipo password; el controlador únicamente escribe si `filled` y nunca devuelve valores.
- `AbstractAIProvider::sanitize()` redacta `sk-`, `sk-ant-`, `sk-or-` y `Bearer ...` en mensajes de
  error antes de loguearse o guardarse.
- Política documentada: las claves pertenecen al administrador de la plataforma.

### 13.8 Pruebas añadidas
- `tests/Feature/AIProviderTest.php`: parseo/estructurado/errores por adaptador con `Http::fake` (9).
- `tests/Feature/AIProviderRegistryTest.php`: resolución, fallback, errores y config BD>env (5).
- `tests/Feature/AiAnalysisIntegrationTest.php`: enriquecimiento del run, fallo IA no rompe el run,
  detección de duplicados (3).
- `tests/Feature/AdminAiSettingsTest.php`: cifrado, no exposición, 403, validación (4).
- `tests/Feature/AIProviderIntegrationTest.php`: integración real gated, **excluida** en `phpunit.xml`;
  ejecutar con `vendor/bin/pest tests/Feature/AIProviderIntegrationTest.php --group=integration`.

### 13.9 Notas operativas
- La suite por defecto (99 tests) no toca la red: los adaptadores se simulan con `Http::fake`.
- Observado en pruebas: la clave de OpenRouter definida devolvió 401 "User not found" (revocada o
  cuenta eliminada). Rotar la clave antes de usar el proveedor en real.
- Anthropic y OpenAI requieren clave propia; sin ella el proveedor se omite del registry.

## 14. Fase 6 — Análisis de novedades y detección de duplicados (completada)

### 14.1 Objetivo
Transformar los resultados web en novedades útiles y verificables: deduplicación global,
clasificación (nueva/existente/irrelevante), fuentes verificables y estado por usuario.

### 14.2 Decisiones aprobadas
- **Modelo global**: nueva tabla `news_entries` (una fila por noticia global); `news_items`
  pasa a ser el vínculo por tema con `news_entry_id`. La misma noticia puede ligarse a varios
  temas sin duplicar su registro global.
- **Estado de usuario**: `is_read`/`is_saved`/`is_irrelevant` (+ timestamps) en el vínculo.
- **Enum**: `App\Enums\NewsItemStatus` (`New`, `Existing`, `Irrelevant`) con cast; se conserva
  la columna `status` original (ciclo de publicación) y se añade `novedad_status`.
- **Seguridad IA**: `PromptSanitizer` + envoltorio "untrusted"; solo se envía el mínimo
  (título, url, dominio, fecha, snippet).
- **Verificación de fuentes**: petición ligera HEAD/GET con timeout corto → `source_available`
  + `source_checked_at`; un fallo nunca descarta la entrada.
- **Ejecución**: síncrona en el run; `content_hash` = título normalizado + snippet.

### 14.3 Arquitectura
- `app/Support/UrlNormalizer.php`: normaliza URL (host, `www`, path, parámetros de tracking) y hash.
- `app/Support/ContentHasher.php`: huella estable de título+snippet (misma noticia, otra URL).
- `app/Support/PromptSanitizer.php`: neutraliza marcadores de inyección y trunca.
- `app/Services/Novedades/NewsEntryRepository.php`: búsqueda por `url_hash`/`content_hash` y alta.
- `app/Services/Novedades/SourceAvailabilityChecker.php`: HEAD con fallback GET.
- `app/Services/Novedades/NovedadPipeline.php`: orquesta normalización → dedupe → verificación
  → persistencia → IA y devuelve conteos (`new`/`existing`/`irrelevant`/`stored`).
- `AiAnalysisService::assessNovedad()` (`AIOperation::AssessNovedad`): puntuación de relevancia,
  resumen y descarte; con reintento no fatal (fallo IA conserva la entrada).

### 14.4 Integración en el pipeline
`RunMonitoredTopicSearch` reemplaza el `persist()` anterior por `NovedadPipeline::process()`.
`results_count` cuenta solo vínculos nuevos; los ya existentes se registran como tales.

### 14.5 Modelo de datos
- `news_entries`: `title`, `url`, `normalized_url`, `url_hash` (único), `content_hash`,
  `source_name`, `source_domain`, `published_at`, `discovered_at`, `summary`,
  `relevance_score`, `source_available`, `source_checked_at`, `status`.
- `news_items`: `news_entry_id` (FK nullOnDelete), `novedad_status`, `is_read`/`read_at`,
  `is_saved`/`saved_at`, `is_irrelevant`/`irrelevant_at`.
- Migración de datos `2026_10_20_220002`: backfill de `news_entries` desde `news_items`
  existentes y enlace por `url_hash`.

### 14.6 UI
- `NewsItemController` + rutas `news-items.read|save|irrelevant` (grupo `auth`+`readonly`).
- `topics/show.blade.php`: badges de estado, fuente disponible, fecha de publicación vs
  descubrimiento, relevancia, resumen y acciones leída/guardar/irrelevante; filtros
  (`all`/`new`/`saved`/`unread`/`irrelevant`).

### 14.7 Configuración
- `config/novedades.php`: `content_hash`, `source_check` (enabled/method/timeout),
  `min_relevance`, `prompt_max_length`, `per_page`. `NOVEDADES_SOURCE_CHECK=false` en
  `phpunit.xml` para que la suite por defecto no toque la red.

### 14.8 Pruebas añadidas
- `tests/Feature/UrlNormalizerTest.php` (4), `ContentHasherTest.php` (3), `PromptSanitizerTest.php` (3).
- `tests/Feature/NovedadPipelineTest.php` (8): nueva/existente/multi-tema, cambio de URL,
  irrelevante por umbral, fallo IA no fatal, fuente disponible/no disponible.
- `tests/Feature/NewsEntryTest.php` (3): registro global compartido, unicidad de `url_hash`,
  desvinculación al borrar.
- `tests/Feature/NewsItemActionsTest.php` (6): toggles, 403 ajeno, admin, suspendido, invitados.
- `tests/Feature/NovedadIntegrationTest.php` (2): run completo con estados y re-ejecución.
- `tests/Feature/SourceAvailabilityIntegrationTest.php`: integración real gated, **excluida** en
  `phpunit.xml`; ejecutar con `vendor/bin/pest tests/Feature/SourceAvailabilityIntegrationTest.php --group=integration`.

### 14.9 Notas operativas
- Suite por defecto (128 tests) sin red: `NOVEDADES_SOURCE_CHECK=false` y adaptadores IA con mock.

## 15. Fase 7 — Programación automática de búsquedas (completada)

### 15.1 Objetivo
Ejecutar las búsquedas según la frecuencia configurada, de forma continua y desatendida,
delegando la ejecución a **jobs en cola** (no dentro del Scheduler), con exclusión mutua,
límites de concurrencia, reintentos y registro de métricas/errores/resultados/costes.

### 15.2 Decisiones aprobadas
- **Cola `database`** (driver ya configurado; tabla `jobs` del scaffolding). Sin dependencias nuevas.
- **Anti‑concurrencia**: `ShouldBeUnique` (por `topic_id`) + `WithoutOverlapping` + rechequeo de estado.
- **Límites**: locks atómicos por clave usuario + global; el job se **re‑encola** si se supera.
- **Métricas**: columnas nuevas en `topic_runs` (`queued_at`, `duration_ms`, `attempts`,
  `estimated_cost`, `provider_usage`) + estado `Queued`.
- **Manual**: también encola (`RunMonitoredTopicSearchJob` con `RunTrigger::Manual`).
- **Reintentos**: `tries=3`, backoff progresivo `[60, 300, 900]` s, configurables.

### 15.3 Arquitectura
- `routes/console.php`: `topics:run-due` ahora **encola** un job por tema vencido
  (`MonitoredTopic::scopeDue`); el Scheduler solo despacha cada 15 min (`withoutOverlapping`).
- `app/Jobs/RunMonitoredTopicSearchJob.php`: `ShouldQueue` + `ShouldBeUnique`, `uniqueId()` por
  tema, `uniqueFor`, `tries`/`backoff`/`timeout` configurables, `middleware()` (overlap + límites),
  `handle()` reutiliza el run en cola o lo crea, y `failed()` marca el run como `Failed`.
- `app/Jobs/Middleware/LimitConcurrentSearches.php`: semáforo de locks numerados global y por
  usuario; libera el job con retardo cuando no hay slot libre, y libera los slots en `finally`.
- `app/Actions/RunMonitoredTopicSearch.php`: `execute()` acepta un run opcional, transiciona
  `Queued`→`Running`→`Success/Failed`, calcula `duration_ms`, `attempts`, `provider_usage` y
  `estimated_cost`; **solo avanza `last_run_at`/`next_run_at` en éxito** (los fallos se reintentan).
- `app/Support/CostEstimator.php`: coste estimado desde `config('monitoring.costs.*')`.

### 15.4 Estados y consistencia
- `RunStatus` gana `Queued`. Transiciones consistentes y `attempts` persistido por intento.
- Un tema pausado mientras el job está en cola: el run asociado se marca `Failed` ("Topic is paused.").
- Un tema inexistente: el job es un no‑op.

### 15.5 Configuración
`config/monitoring.php` gana:
```
'queue' => ['connection','name'=>'searches','tries'=>3,'backoff'=>[60,300,900],'unique_for','timeout'],
'concurrency' => ['max_global'=>5,'max_per_user'=>2,'release_after'=>30],
'costs' => ['per_result'=>0,'per_1k_tokens_ai'=>0],
```
Variables equivalentes en `.env.example` (`MONITORING_*`).

### 15.6 UI
- Botón "Run search now" → encola y avisa "Search queued." (antes ejecutaba en línea).
- "Run history" muestra estado (con color), duración, intentos y coste estimado.

### 15.7 Infraestructura (requisito del enunciado)
- **Desarrollo**: `php artisan queue:work --queue=searches` y `php artisan schedule:work`
  (o el cron del host para `schedule:run`).
- **Producción**: worker persistente supervisado (supervisord/systemd) + `schedule:run` por cron.
- Los locks de unicidad/concurrencia requieren un almacén de caché con locks atómicos: usar
  `database` o `redis` en producción (el driver `array` de tests no es atómico entre procesos).

### 15.8 Pruebas añadidas
- `tests/Feature/SchedulingTest.php` (6): encolado de vencidos, noop sin vencidos, registro cada
  15 min, manual encola, tema pausado no ejecuta / marca fallo.
- `tests/Feature/RunMonitoredTopicSearchJobTest.php` (6): métricas del run, reutilización del run
  en cola, errores de proveedor, unicidad, reintentos/backoff, topic inexistente.
- `tests/Feature/SearchConcurrencyTest.php` (4): paso con slots libres, release por límite de
  usuario, release por límite global, liberación de slots.

### 15.9 Notas operativas
- Suite por defecto (144 tests) sin red ni workers reales: `QUEUE_CONNECTION=sync` en testing y
  `Queue::fake()` donde se afirma el despacho.

## 16. Fase 8 — Notificaciones por correo electrónico (completada)

### 16.1 Objetivo
Enviar novedades relevantes al correo del usuario con preferencias globales y por tema, alerta
individual o resumen, registro auditable de entregas, anti-duplicados, reintentos con backoff e
integración con la cola.

### 16.2 Decisiones aprobadas
- Interruptor maestro global (`notifications.enabled`) combinado con `Preference.email_notifications`
  y `MonitoredTopic.notify_email`; gana el más restrictivo.
- `Subscription` se mantiene fuera del flujo de notificación (Fase 8 usa `MonitoredTopic` + `Preference`).
- Modo configurable (`NotificationMode`: `digest`, `individual`, `both`, `none`) con default global y
  override por tema. Default recomendado: resumen diario.
- Resumen diario programado por `Schedule`; alerta individual inmediata por cola.
- Anti-duplicados por clave única `(modo, user, entry|periodo, canal)` en `notification_deliveries`.
- Exclusión de usuarios suspendidos y de canal desactivado re-comprobada en el momento del envío.
- Tabla propia `notification_deliveries` (estado `queued/sent/failed/skipped`, `attempts`, error
  sanitizado) en vez de la tabla `notifications` de Laravel. `notify_push` fuera de alcance.
- Plantillas Markdown Blade por defecto, con layout propio de Laravel, respetando `Preference.locale`.

### 16.3 Arquitectura
- `App\Enums\NotificationMode` (`digest`/`individual`/`both`/`none`) y `App\Enums\DeliveryStatus`
  (`queued`/`sent`/`failed`/`skipped`).
- `App\Services\Notifications\NovedadNotifier`: elegibilidad, umbral de relevancia, límite diario,
  reserva anti-duplicados y despacho de la alerta individual.
- `App\Services\Notifications\NovedadDigest`: recopila novedades por usuario y periodo, formatea
  ítems y reserva el resumen diario.
- `App\Jobs\SendNovedadAlertJob` y `App\Jobs\SendNovedadDigestJob`: `ShouldQueue` + `ShouldBeUnique`,
  `WithoutOverlapping`, `tries`/`backoff`/`timeout` desde config, re-chequeo de elegibilidad y
  `failed()` con mensaje sanitizado.
- `App\Support\NotificationKey`: claves de deduplicación individuales y de periodo.
- `App\Support\SecretSanitizer`: redacción de `sk-*`, `Bearer ...` reutilizable en logs y correos.
- `App\Mail\NovedadAlert` / `App\Mail\NovedadDigest` + vistas `resources/views/mail/novedades/*`.

### 16.4 Integración en el pipeline
- `NovedadPipeline::process()` despacha la alerta individual al enlazar una novedad con estado `New`
  (tras `link()`), delegando toda la elegibilidad al `NovedadNotifier`.
- `routes/console.php`: comando `novedades:send-digests` encola resúmenes; programado con
  `Schedule::command('novedades:send-digests')->dailyAt(...)->withoutOverlapping()` según
  `notifications.digest.at`.

### 16.5 Modelo de datos
- `notification_deliveries` (`2026_10_21_010000`): `user_id`, `monitored_topic_id` (nullOnDelete),
  `news_entry_id` (nullOnDelete), `channel`, `mode`, `dedupe_key` (único), `status`, `attempts`,
  `items_count`, `period_start`, `error_message`, timestamps `queued_at`/`sent_at`/`failed_at`.
- `preferences` (`2026_10_21_010001`): `notification_mode`, `digest_frequency`.
- `monitored_topics` (`2026_10_21_010002`): `notification_mode` (nullable, override por tema).
- `MonitoredTopic::effectiveNotificationMode()` combina switch global, preferencia de usuario y override.

### 16.6 UI
- Perfil: parcial `profile.partials.notification-preferences-form` (canal, modo, frecuencia, idioma,
  zona horaria) con ruta `PATCH /preferences` (`preferences.update`, `PreferenceController`).
- Tema: selector "Email notification mode" en `topics/partials/form.blade.php` con opción "usar mi
  preferencia global".

### 16.7 Configuración
- `config/notifications.php`: `enabled`, `default_mode`, `modes`, `digest.*`, `limits.*`, `queue.*`,
  `min_relevance`. Variables `NOTIFICATIONS_*` documentadas en `.env.example`.
- PHPUnit fuerza `MAIL_MAILER=array` y `QUEUE_CONNECTION=sync`; los tests usan `Mail::fake()` y
  `Queue::fake()` donde se afirma el envío/despacho.

### 16.8 Pruebas añadidas
- `tests/Feature/NotificationsTest.php` (15): elegibilidad, deduplicación, switch global y por tema,
  modo digest-only, exclusión por irrelevancia/umbral, usuario suspendido, límite diario, envío y
  marcado de la alerta, skip por inelegibilidad, resumen único por periodo, envío del resumen, skip
  sin novedades, hook de fallo sanitizado y actualización de preferencias por HTTP.

### 16.9 Notas operativas
- Requiere un worker en la cola `notifications` (además de `searches`) y `schedule:run` para los
  resúmenes. En dev: `php artisan queue:work --queue=notifications,searches` + `schedule:work`.
- Suite por defecto (159 tests) sin red ni workers reales.

## 17. Fase 9 — Notificaciones push web (completada)

### 17.1 Objetivo
Enviar alertas al navegador del usuario (incluso con la página cerrada) para novedades relevantes,
con permiso solicitado en una acción explícita, suscripciones por dispositivo, apertura de la novedad
al pulsar, gestión de suscripciones expiradas, preferencias global/por tema y una página de prueba.

### 17.2 Decisiones aprobadas
- Única dependencia nueva: `minishlink/web-push` (`^11.0`).
- Payload **genérico por defecto** con `privacy_mode` (sin título ni resumen en pantalla bloqueada),
  configurable por el usuario (`Preference.push_private`).
- El push usa `MonitoredTopic.notify_push` (por tema) + interruptor global `Preference.push_notifications`,
  el mismo umbral de relevancia que el correo y es independiente del modo de correo.
- Solo alerta individual por push en esta fase; el resumen (digest) sigue por correo.
- Suscripciones por dispositivo en tabla propia `push_subscriptions`, anti-duplicados por hash del endpoint.
- Página de prueba dedicada `/push-test` autenticada, enlazada desde preferencias.
- En tests no hay claves reales: `WebPushSender` se mockea y las claves VAPID son dummy.

### 17.3 Arquitectura
- `App\Services\Notifications\WebPushSender`: envoltorio de `minishlink/web-push`; construye el
  `Subscription`, envía el payload JSON, marca `expired_at` ante respuestas 404/410 y expone
  `isConfigured()`/`publicKey()`.
- `App\Jobs\SendWebPushNotificationJob`: `ShouldQueue` + `ShouldBeUnique` (por suscripción y tag),
  `WithoutOverlapping`, `tries`/`backoff`/`timeout` desde `config('webpush.queue')`, idempotente, con
  `failed()` sanitizado.
- `App\Support\WebPushPayload`: construye el payload para una novedad (privacy mode) o para la prueba.
- `App\Models\PushSubscription` + factory: `endpoint`, `endpoint_hash` (único), `public_key`,
  `auth_token`, `content_encoding`, `device_label`, `user_agent`, `last_seen_at`, `expired_at`;
  scope `active()`, `markSeen()`, `markExpired()`, `hashEndpoint()`.
- `App\Console\Commands\GenerateVapidKeys` (`webpush:vapid`): genera el par VAPID.
- `config/webpush.php` + `VAPID_*`/`WEBPUSH_*` en `.env.example`.

### 17.4 Integración en el pipeline
- `NovedadPipeline::process()` despacha `dispatchPushAlert()` junto a la alerta por correo al enlazar
  una novedad `New`; la elegibilidad (switch global, preferencia, `notify_push`, umbral, suspendido)
  la decide `NovedadNotifier::pushEligible()`.

### 17.5 Modelo de datos
- `push_subscriptions` (`2026_10_21_020000`): `user_id` (cascade), `endpoint`, `endpoint_hash` (único),
  `public_key`/`auth_token` nullable, `content_encoding` (`aesgcm` por defecto), `device_label`,
  `user_agent`, `last_seen_at`, `expired_at`, índice `(user_id, expired_at)`.
- `preferences` (`2026_10_21_020001`): `push_notifications`, `push_private`.
- `User::pushSubscriptions()`.

### 17.6 UI y cliente
- Perfil: bloque "Browser notifications" en `profile.partials.notification-preferences-form` (interruptor
  global, privacidad, enlace a la página de prueba).
- Página `/push-test` (`push.test`) + envío de prueba (`push.test.send`) con tabla de dispositivos.
- Rutas `POST /push/subscriptions` (`push.subscriptions.store`) y
  `DELETE /push/subscriptions/{pushSubscription}` (`push.subscriptions.destroy`), restringidas al dueño.
- `public/sw.js`: Service Worker que muestra la notificación y abre la novedad al pulsar
  (`notificationclick` con foco/ventana).
- `resources/js/push.js` (`window.NewsPush`): registra el SW, pide permiso en acción explícita,
  `pushManager.subscribe()` con `applicationServerKey` y reporta/elimina la suscripción en Laravel.

### 17.7 Configuración
- `config/webpush.php`: `enabled`, `vapid.*`, `privacy_mode`, `generic_title`, `generic_body`,
  `max_title_length`, `max_body_length`, `ttl`, `urgency`, `queue.*`.
- El push reutiliza la cola `notifications`. Sin claves VAPID no se envía nada.

### 17.8 Pruebas añadidas
- `tests/Feature/PushNotificationsTest.php` (13): despacho por dispositivo activo, skip por tema,
  skip por preferencia global, respeto del umbral, payload privado/genérico, payload público,
  alta de suscripción, upsert por endpoint, prohibición de borrar suscripción ajena, usuario
  suspendido, página de prueba, envío de prueba y marcado de suscripción expirada.

### 17.9 Notas operativas
- Generar claves con `php artisan webpush:vapid` y definir `VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY`.
- Requiere servir el sitio por HTTPS (o `localhost`) para que el navegador permita push.
- Suite por defecto (172 tests) sin red ni workers reales.

## 18. Fases pendientes

### Fase 10 — Panel de administración avanzado, portal público y endurecimiento
- Acciones admin: suspender/reactivar usuarios (usando `UserPolicy::suspend`).
- Redirigir `/dashboard` de Breeze a `/admin`.
- Rutas públicas: listado, detalle por slug, filtros por categoría/tag, paginación.
- Revocación de sesiones al suspender (política elegida: solo lectura, evaluar `logoutOtherDevices`).
- Revisión de N+1, caché, tests adicionales.
- Documentación de despliegue (Laravel Cloud) si procede.

## 19. Criterios de aceptación de Fase 1

| Criterio | Estado |
| --- | --- |
| La aplicación inicia correctamente | ✅ Verificado (`/`, `/login`, `/register` → HTTP 200) |
| Accede a MySQL `news` | ✅ Verificado (`SELECT DATABASE()` → `news`) |
| Migraciones aplicadas | ✅ Verificado |
| Frontend compila (Tailwind 4 + Alpine) | ✅ `npm run build` OK |
| Tests existentes siguen pasando | ✅ 172/172 por defecto (MySQL `news_test`); gated de integración con `--group=integration` |
