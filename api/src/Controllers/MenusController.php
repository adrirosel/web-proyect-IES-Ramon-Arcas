<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\JsonBody;

final class MenusController
{
    /** Arbol con padre_id para navegacion publica. */
    public function publicShow(Request $request, array $params): Response
    {
        $clave = $params['clave'];
        $menu = Database::pdo()->prepare('SELECT id, clave, nombre FROM menus WHERE clave = ?');
        $menu->execute([$clave]);
        $m = $menu->fetch();
        if (!$m) {
            return Response::error('not_found', 'Menu no encontrado', 404);
        }
        $items = Database::pdo()->prepare(
            'SELECT id, padre_id, titulo_es, titulo_en, url, pagina_id, orden
             FROM menu_items WHERE menu_id = ? ORDER BY orden, id'
        );
        $items->execute([(int) $m['id']]);
        $flat = $items->fetchAll();
        $m['items'] = $this->tree($flat);
        return Response::json(['data' => $m]);
    }

    public function adminIndex(Request $request, array $params): Response
    {
        return Response::json(['data' => Database::pdo()->query('SELECT * FROM menus ORDER BY id')->fetchAll()]);
    }

    public function adminStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['clave', 'nombre']);
        if (empty($data['clave']) || empty($data['nombre'])) {
            return Response::error('validation_error', 'clave y nombre requeridos', 422);
        }
        Database::pdo()->prepare('INSERT INTO menus (clave, nombre) VALUES (?, ?)')
            ->execute([$data['clave'], $data['nombre']]);
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminReplaceItems(Request $request, array $params): Response
    {
        $menuId = (int) $params['id'];
        $items = $request->body['items'] ?? null;
        if (!is_array($items)) {
            return Response::error('validation_error', 'items[] requerido', 422);
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM menu_items WHERE menu_id = ?')->execute([$menuId]);
            // Insercion en dos pasadas no es trivial con padre temporal; aceptamos padre_id ya resuelto
            $ins = $pdo->prepare(
                'INSERT INTO menu_items (menu_id, padre_id, titulo_es, titulo_en, url, pagina_id, orden)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $this->insertTree($ins, $menuId, $items, null);
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            return Response::error('validation_error', 'Items de menu no validos', 422);
        }
        return Response::json(['ok' => true]);
    }

    /** @param list<array<string,mixed>> $items */
    private function insertTree(\PDOStatement $ins, int $menuId, array $items, ?int $padreId): void
    {
        foreach ($items as $i => $item) {
            $ins->execute([
                $menuId,
                $padreId,
                $item['titulo_es'] ?? '',
                $item['titulo_en'] ?? null,
                $item['url'] ?? null,
                $item['pagina_id'] ?? null,
                $item['orden'] ?? $i,
            ]);
            $newId = (int) Database::pdo()->lastInsertId();
            if (!empty($item['children']) && is_array($item['children'])) {
                $this->insertTree($ins, $menuId, $item['children'], $newId);
            }
        }
    }

    /** @param list<array<string,mixed>> $flat */
    private function tree(array $flat, ?int $padre = null): array
    {
        $branch = [];
        foreach ($flat as $item) {
            $pid = $item['padre_id'] !== null ? (int) $item['padre_id'] : null;
            if ($pid === $padre) {
                $item['children'] = $this->tree($flat, (int) $item['id']);
                $branch[] = $item;
            }
        }
        return $branch;
    }
}
