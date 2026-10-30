# Modelo de datos

Migración fuente de verdad: `api/database/migrations/001_init_schema.sql`.

## Entidades clave

- **usuarios** — cuentas del backoffice (no alumnado).
- **configuracion** — clave/valor + `publica` (equivalente a `configuracion_centro` de la memoria orientativa).
- **menus / menu_items** — árbol de navegación (`menu_items.padre_id`).
- **paginas / pagina_bloques** — contenido por bloques tipados (sin jerarquía padre-hijo).
- **noticias, avisos, eventos** — comunicación.
- **ensenanzas / modulos** — oferta educativa.
- **materias_optativas / ofertas_optativas** — catálogo vs oferta por nivel y curso.
- **departamentos / personas / persona_departamento** — organización docente.
- **organos / organo_miembros** — equipo directivo, consejo, claustro, CCP…
- **programas / programa_departamento** — planes del centro.
- **categorias_documentos / documentos / archivos** — descargas reutilizables.
- **servicios** — biblioteca, etc.
- **contactos** — bandeja de formularios tipados (equivalente funcional a `solicitudes_secretaria`).
- **cursos_academicos, rate_limits, audit_log**.

## Correspondencia con la memoria orientativa (§6.3)

La memoria del proyecto intermodular propone entidades orientativas; el diseño final las cubre así:

| Memoria (orientativo) | Implementación | Motivo |
|---|---|---|
| `paginas` con `padre_id` | Jerarquía en `menu_items.padre_id`; `paginas` es contenido plano + bloques | Cumple «menú ≠ contenido»: la IA de navegación no se mezcla con el cuerpo editorial |
| `documentos` reutilizables | `archivos` (fichero único) + `documentos` (metadatos/categoría/vínculos) | Una subida sirve en varias secciones |
| `departamentos` N:M `programas` | `programa_departamento` | Relación explícita |
| `noticias` | `noticias` | Directo |
| `personas` (por tipo) | `personas` + `organos` / `organo_miembros` (cargo en el vínculo) | Una persona puede pertenecer a varios órganos sin duplicar ficha |
| `ensenanzas` / `materias_optativas` | `ensenanzas`, `materias_optativas`, `ofertas_optativas` | Materia de catálogo ≠ oferta por nivel/curso |
| `configuracion_centro` | `configuracion` (clave/valor, `publica`) | Incluye `intranet_url`, contacto, localización |
| `solicitudes_secretaria` | `contactos` (`tipo` + `estado` pendiente/en_curso/finalizado) | Misma bandeja para consulta, sugerencia, queja y rincón de pensar |

### Texto sugerido para la memoria de módulo

> El modelo de datos respeta los principios de la memoria orientativa (3FN, menú separado del contenido, documentos reutilizables, N:M programas–departamentos). La jerarquía de navegación vive en `menu_items.padre_id`, no en `paginas`, para no acoplar el árbol de menú al contenido editorial. La configuración del centro es una tabla clave/valor (`configuracion`) con clave pública `intranet_url` como único puente hacia la intranet. Las solicitudes de secretaría y formularios tipados se modelan como `contactos` con flujo de estado pendiente → en_curso → finalizado.

## Normalización

Objetivo 3FN. Excepciones documentadas:

- `pagina_bloques.contenido_json`: estructura variable por `tipo` (texto/imagen/documentos/enlaces).
- `configuracion.valor`: texto libre tipado por convención de clave.

## Visibilidad en relaciones

Un listado público no debe devolver hijos en borrador (p. ej. documentos de una enseñanza solo si el documento está publicado).
