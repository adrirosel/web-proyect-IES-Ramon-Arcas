# Fases y roadmap

## Hitos de implementación

| Hito | Contenido | Estado |
|---|---|---|
| H1 | Docker, migraciones, router, health, OpenAPI base, docs | Hecho |
| H2 | Auth, CSRF, RBAC, usuarios, rate limit | Hecho |
| H3 | Archivos, documentos, páginas, menús, noticias (recorrido API) | Hecho |
| H4 | Enseñanzas, optativas, departamentos, personas/órganos, programas | Hecho |
| H5 API | Eventos, avisos, servicios, contactos, OpenAPI completo | Hecho |
| H5 front | Scaffold Vite+TypeScript (sitio público + panel admin) | Pendiente |

La API de H1–H5 está **definida y funcional** (`openapi.yaml` + controladores). El scaffold front de H5 y los aspectos SEO/sitemap/WCAG dependen de ese front y quedan para una fase posterior. Los tests HTTP de permisos/visibilidad se amplían por recurso.

## Fuera de v1 (no van en `openapi.yaml` paths)

- Preinscripciones / admisión tramitada
- Reservas de cita previa estructuradas
- Búsqueda global
- Galerías dedicadas
- Export iCalendar
- Auditoría avanzada consultable
- FAQ como recurso propio
- `sitemap.xml` y auditoría WCAG (con el front)

Cuando se necesiten, se documentan aquí y luego se añaden al OpenAPI **al implementarlos**.
