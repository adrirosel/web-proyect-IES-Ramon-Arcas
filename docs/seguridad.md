# Seguridad

## Controles

| Control | Implementación |
|---|---|
| AuthN | Sesión PHP cookie `HttpOnly`, `SameSite=Lax`, `Secure` configurable |
| Session fixation | `session_regenerate_id(true)` en login |
| Usuario desactivado | Relectura de `activo` en cada `SessionAuth::user()` |
| CSRF | `GET /admin/csrf` + header `X-CSRF-Token` en escrituras |
| AuthZ | `Rbac::can` por recurso; denegación por defecto |
| Contraseñas | Argon2id/bcrypt; mensajes de login genéricos |
| SQLi | PDO prepared; sort/direction con whitelist |
| Uploads | `finfo`, whitelist MIME (sin SVG), tamaño máx., nombre aleatorio, storage fuera de `public/`, Nginx no ejecuta PHP ahí |
| Rate limit | Tabla `rate_limits` en login y contacto |
| XSS contenido | Markdown en bloques; no se acepta HTML libre de page-builder |
| Config | Flag `publica`; endpoint público no lista claves privadas |
| Visibilidad | Públicos filtran estado/vigencia; 404 uniforme |
| Logs | `storage/logs/app.log` sin passwords ni cuerpos de contacto |
| Errores API | Sin stack trace salvo `APP_DEBUG` |

## Checklist de revisión

- [ ] Ningún endpoint admin accesible sin sesión
- [ ] Rol insuficiente → 403
- [ ] Sort desconocido → 422
- [ ] Archivo en uso → 409 al borrar
- [ ] Contacto sin consentimiento → 422
- [ ] `GET /configuracion` sin secretos
