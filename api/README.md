# API PHP

## Añadir un endpoint

1. Definir ruta en `routes/api.php` con middleware adecuado.
2. Implementar método en el controller (validación + SQL preparado).
3. Si es listado: `Pagination` + `Sort` con whitelist de columnas.
4. Si es público: filtrar con `PublishedScope` o equivalente.
5. Actualizar `../openapi.yaml`.
6. Añadir test de permiso o visibilidad en `tests/`.

## Scripts

```bash
composer install
php bin/migrate.php
php bin/seed.php
vendor/bin/phpunit
```

## Uploads

Ficheros en `storage/uploads/` (fuera de `public/`). Nginx los sirve en `/storage/uploads/` sin ejecutar PHP.
