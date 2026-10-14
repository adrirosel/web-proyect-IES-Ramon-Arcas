<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\EditorialCrud;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\PublishedScope;

final class PaginasController
{
    use EditorialCrud;

    protected function table(): string
    {
        return 'paginas';
    }

    protected function writable(): array
    {
        return [
            'slug', 'slug_en', 'titulo_es', 'titulo_en', 'estado', 'publicado_en',
            'autor_id', 'actualizado_por',
        ];
    }

    protected function sortColumns(): array
    {
        return ['id', 'titulo_es', 'updated_at'];
    }

    public function publicShow(Request $request, array $params): Response
    {
        $slug = $params['slug'];
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM paginas WHERE slug = ? AND ' . PublishedScope::sql() . ' LIMIT 1'
        );
        $stmt->execute([$slug]);
        $page = $stmt->fetch();
        if (!$page) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        $bloques = Database::pdo()->prepare(
            'SELECT id, tipo, contenido_json, orden FROM pagina_bloques WHERE pagina_id = ? ORDER BY orden'
        );
        $bloques->execute([(int) $page['id']]);
        $page['bloques'] = array_map(static function (array $b): array {
            $b['contenido'] = json_decode((string) $b['contenido_json'], true);
            unset($b['contenido_json']);
            return $b;
        }, $bloques->fetchAll());

        return Response::json(['data' => $page]);
    }

    public function adminBloquesPut(Request $request, array $params): Response
    {
        $paginaId = (int) $params['id'];
        $bloques = $request->body['bloques'] ?? null;
        if (!is_array($bloques)) {
            return Response::error('validation_error', 'bloques[] requerido', 422);
        }
        $pdo = Database::pdo();
        $exists = $pdo->prepare('SELECT 1 FROM paginas WHERE id = ?');
        $exists->execute([$paginaId]);
        if (!$exists->fetch()) {
            return Response::error('not_found', 'Pagina no encontrada', 404);
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM pagina_bloques WHERE pagina_id = ?')->execute([$paginaId]);
            $ins = $pdo->prepare(
                'INSERT INTO pagina_bloques (pagina_id, tipo, contenido_json, orden) VALUES (?, ?, ?, ?)'
            );
            foreach ($bloques as $i => $b) {
                $tipo = $b['tipo'] ?? '';
                if (!in_array($tipo, ['texto', 'imagen', 'documentos', 'enlaces'], true)) {
                    throw new \InvalidArgumentException('tipo invalido');
                }
                $ins->execute([
                    $paginaId,
                    $tipo,
                    json_encode($b['contenido'] ?? new \stdClass(), JSON_UNESCAPED_UNICODE),
                    (int) ($b['orden'] ?? $i),
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return Response::error('validation_error', 'Bloques no validos', 422);
        }
        return Response::json(['ok' => true]);
    }
}
