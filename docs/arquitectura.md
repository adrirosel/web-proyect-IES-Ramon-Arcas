# Arquitectura

## Capas de la API

1. **Front controller** (`api/public/index.php`): carga `.env`, CORS, cabeceras, despacha el router.
2. **Router** (`App\Http\Router`): método + patrón `{param}` + middleware en onion.
3. **Controllers**: validan entrada, aplican reglas de visibilidad/permisos y delegan en PDO.
4. **Support**: paginación, sort con whitelist, scope de publicados, rate limit, logger.
5. **Auth**: sesión cookie, CSRF, hash de contraseñas, matriz RBAC.

No hay ORM: SQL explícito con prepared statements. Las columnas de `ORDER BY` nunca se interpolan desde el cliente sin whitelist.

## Same-origin

Nginx expone la API en el mismo host que el futuro front (`/api/...`). La cookie de sesión viaja sin credenciales cross-site. Si en desarrollo Vite usa otro puerto, está en `CORS_ORIGINS` con `credentials`.

## Separación menú / contenido

- `menus` + `menu_items` (árbol `padre_id`) definen navegación.
- `paginas` + `pagina_bloques` definen contenido editorial (Markdown en bloques `texto`).
- Un ítem de menú puede apuntar a `url` externa o a `pagina_id`.

## Archivos y documentos

- `archivos`: fichero físico + metadatos (alt, descripción).
- `documentos`: metadato editorial (categoría, curso, enseñanza) que apunta a un `archivo_id`.
- Así un PDF institucional puede enlazarse desde varias secciones sin re-subirlo.

## i18n

Campos editoriales clave en `*_es` / `*_en`. El cliente pide idioma; si falta EN se sirve ES. El slug principal es ES; `slug_en` es opcional.

## Relación con la intranet

Ninguna a nivel de datos. Solo `configuracion.intranet_url` (pública) para un botón “Acceso intranet”.
