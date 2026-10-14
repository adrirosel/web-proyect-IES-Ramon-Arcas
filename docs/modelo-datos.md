# Modelo de datos

Migración fuente de verdad: `api/database/migrations/001_init_schema.sql`.

## Entidades clave

- **usuarios** — cuentas del backoffice (no alumnado).
- **configuracion** — clave/valor + `publica`.
- **menus / menu_items** — árbol de navegación.
- **paginas / pagina_bloques** — contenido por bloques tipados.
- **noticias, avisos, eventos** — comunicación.
- **ensenanzas / modulos** — oferta educativa.
- **materias_optativas / ofertas_optativas** — catálogo vs oferta por nivel y curso.
- **departamentos / personas / persona_departamento** — organización docente.
- **organos / organo_miembros** — equipo directivo, consejo, claustro, CCP…
- **programas / programa_departamento** — planes del centro.
- **categorias_documentos / documentos / archivos** — descargas.
- **servicios** — biblioteca, etc.
- **contactos** — bandeja de formularios tipados.
- **cursos_academicos, rate_limits, audit_log**.

## Normalización

Objetivo 3FN. Excepciones documentadas:

- `pagina_bloques.contenido_json`: estructura variable por `tipo` (texto/imagen/documentos/enlaces).
- `configuracion.valor`: texto libre tipado por convención de clave.

## Visibilidad en relaciones

Un listado público no debe devolver hijos en borrador (p. ej. documentos de una enseñanza solo si el documento está publicado).
