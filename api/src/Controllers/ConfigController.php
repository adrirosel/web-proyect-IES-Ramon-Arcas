<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;

final class ConfigController
{
    /** Publico: solo claves con publica=1 (nunca secretos). */
    public function publicIndex(Request $request, array $params): Response
    {
        $rows = Database::pdo()->query(
            'SELECT clave, valor FROM configuracion WHERE publica = 1 ORDER BY clave'
        )->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['clave']] = $row['valor'];
        }
        return Response::json(['data' => $map]);
    }

    /** Admin: todas las claves. */
    public function adminIndex(Request $request, array $params): Response
    {
        $rows = Database::pdo()->query(
            'SELECT clave, valor, publica, updated_at FROM configuracion ORDER BY clave'
        )->fetchAll();
        return Response::json(['data' => $rows]);
    }

    public function adminPatch(Request $request, array $params): Response
    {
        $items = $request->body['items'] ?? null;
        if (!is_array($items)) {
            return Response::error('validation_error', 'Se espera items[]', 422);
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO configuracion (clave, valor, publica) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), publica = VALUES(publica)'
        );
        foreach ($items as $item) {
            if (!isset($item['clave'], $item['valor'])) {
                continue;
            }
            $stmt->execute([
                (string) $item['clave'],
                (string) $item['valor'],
                (int) (bool) ($item['publica'] ?? false),
            ]);
        }
        return Response::json(['ok' => true]);
    }
}
