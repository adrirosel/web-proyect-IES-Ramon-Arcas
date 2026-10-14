# AGENTS.md — Web pública IES Ramón Arcas

Normas breves para agentes y desarrolladores. Detalle en `docs/`.

## Qué es este proyecto

Sitio **público** del centro (familias, alumnado, comunidad). API REST JSON bajo `/api/v1` + panel admin de contenidos.

**No es la intranet.** Repositorio, base de datos, sesión y autenticación propios. Único puente: clave pública `intranet_url` en configuración.

## Stack

- PHP 8.3 sin framework (Composer + PSR-4 + PHPUnit)
- MariaDB 11.4 LTS
- Docker: Nginx (same-origin) + PHP-FPM + MariaDB
- OpenAPI: `openapi.yaml` (solo operaciones implementadas)
- Frontend Vite+TS: previsto al final; no bloquea la API

## Arranque rápido

Ver [README.md](README.md). Resumen: copiar `.env.example` → `.env`, `docker compose up -d --build`, `composer install`, `php bin/migrate.php`, `php bin/seed.php`.

## Arquitectura

- Capas propias: Router, Request/Response, Auth/RBAC, PDO
- Menú ≠ contenido; archivos únicos; documentos reutilizables
- Públicos: solo `publicado` (+ vigencia). Mismo 404 si no existe o no está publicado
- Admin: sesión cookie + CSRF + roles (`admin` | `editor` | `secretaria`)
- i18n: columnas `*_es` / `*_en`; fallback a ES
- Personas + órganos + pertenencias (cargo en el vínculo); optativas = materia + oferta

Documentos: [docs/arquitectura.md](docs/arquitectura.md), [docs/modelo-datos.md](docs/modelo-datos.md), [docs/seguridad.md](docs/seguridad.md), [docs/permisos.md](docs/permisos.md), [docs/fases.md](docs/fases.md).

## Reglas al implementar

1. Autorización siempre en servidor (`App\Auth\Rbac`).
2. `ORDER BY` solo con whitelist (`App\Support\Sort`).
3. Errores: `{ "error": { "code", "message", "fields?" } }`.
4. Actualizar `openapi.yaml` junto al código de cada endpoint.
5. No compartir BD/sesión con la intranet.
6. No añadir rutas “reserved” al OpenAPI; roadmap en `docs/fases.md`.
7. Comentarios: decisiones e invariantes, no narrar lo obvio.
8. Tests de permisos (401/403) y visibilidad pública en cada recurso sensible.
9. Secretos solo en `.env` (nunca en el repo).

## Flujo Git

- Remoto: `https://github.com/adrirosel/web-proyect-IES-Ramon-Arcas.git`
- **Ramas de trabajo:** `Adrian-<nombre-de-la-implementacion>` (ej. `Adrian-scaffold-admin`, `Adrian-tests-visibilidad`).
- Resolver conflictos **en esa rama** antes de integrar.
- Integrar en `main` solo cuando la implementación esté terminada y estable.
- El commit inicial en `main` fue la excepción de arranque del repositorio; a partir de ahora no desarrollar directamente sobre `main`.

## Mapa sección web → recursos API

| Sección | Recursos |
|---|---|
| Inicio / contacto | `configuracion`, `avisos`, `noticias`, `POST /contacto` |
| Organización | `organos`, `personas`, `departamentos`, `paginas`, `documentos` |
| Familias | `documentos`, `enlaces`, `paginas`, `contacto` (tipos) |
| Enseñanzas | `ensenanzas`, `materias-optativas`, `ofertas-optativas` |
| Programas | `programas` (+ N:M departamentos) |
| Secretaría | `documentos` (categoría), `paginas`, `contacto` |
| Noticias / calendario | `noticias`, `eventos`, `cursos-academicos` |
