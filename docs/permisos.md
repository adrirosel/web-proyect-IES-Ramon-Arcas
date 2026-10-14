# Matriz de permisos

Denegación por defecto. Código: `App\Auth\Rbac`.

| Recurso | admin | editor | secretaria |
|---|---|---|---|
| usuarios | Sí | No | No |
| configuracion | Sí | No | No |
| paginas, menus, enlaces | Sí | Sí | No |
| noticias, eventos, cursos_academicos | Sí | Sí | No |
| ensenanzas, materias_optativas, ofertas_optativas | Sí | Sí | No |
| departamentos, personas, organos, programas, servicios | Sí | Sí | No |
| avisos | Sí | Sí | Sí |
| documentos, categorias_documentos, archivos | Sí | Sí | Sí |
| contactos | Sí | No | Sí |

Lectura pública: sin sesión, solo contenido publicado/vigente.
