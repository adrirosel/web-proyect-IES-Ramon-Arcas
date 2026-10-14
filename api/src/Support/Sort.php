<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Request;
use App\Http\Response;
use RuntimeException;

/**
 * ORDER BY no admite placeholders PDO: solo columnas de una whitelist.
 * Si el cliente pide un sort desconocido, respondemos 422 (no lo ignoramos en silencio).
 */
final class Sort
{
    /**
     * @param list<string> $allowedColumns
     * @return array{column:string,direction:string}
     */
    public static function fromRequest(Request $request, array $allowedColumns, string $defaultColumn, string $defaultDir = 'desc'): array
    {
        $column = (string) $request->query('sort', $defaultColumn);
        $direction = strtolower((string) $request->query('direction', $defaultDir));

        if (!in_array($column, $allowedColumns, true)) {
            throw new InvalidSortException('Parametro sort no permitido');
        }
        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidSortException('Parametro direction debe ser asc o desc');
        }

        return ['column' => $column, 'direction' => $direction];
    }

    public static function toResponse(InvalidSortException $e): Response
    {
        return Response::error('validation_error', $e->getMessage(), 422, [
            'sort' => 'Valor no permitido',
        ]);
    }
}

final class InvalidSortException extends RuntimeException
{
}
