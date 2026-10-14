# Fases y roadmap

## Hitos de implementación

| Hito | Contenido |
|---|---|
| H1 | Docker, migraciones, router, health, OpenAPI base, docs, tests health |
| H2 | Auth, CSRF, RBAC, usuarios, rate limit |
| H3 | Archivos, documentos, páginas, menús, noticias (recorrido completo) |
| H4 | Enseñanzas, optativas, departamentos, personas/órganos, programas |
| H5 | Eventos, avisos, servicios, contactos, OpenAPI completo, scaffold front |

El código actual concentra H1–H5 de API para tener **todos los endpoints definidos y funcionales**; los tests se amplían por recurso.

## Fuera de v1 (no van en `openapi.yaml` paths)

- Preinscripciones / admisión tramitada
- Reservas de cita previa estructuradas
- Búsqueda global
- Galerías dedicadas
- Export iCalendar
- Auditoría avanzada consultable
- FAQ como recurso propio

Cuando se necesiten, se documentan aquí y luego se añaden al OpenAPI **al implementarlos**.
