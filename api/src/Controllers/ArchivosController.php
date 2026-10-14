<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\SessionAuth;
use App\Config;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\JsonBody;
use App\Support\Logger;
use App\Support\Pagination;
use PDO;

/**
 * Subida segura: finfo (no confiar en Content-Type), whitelist MIME,
 * nombre generado, storage fuera de public/, sin SVG.
 */
final class ArchivosController
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
    ];

    public function index(Request $request, array $params): Response
    {
        $pg = Pagination::fromRequest($request);
        $pdo = Database::pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM archivos')->fetchColumn();
        $stmt = $pdo->prepare('SELECT * FROM archivos ORDER BY id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();
        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    public function store(Request $request, array $params): Response
    {
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            return Response::error('validation_error', 'Campo file requerido', 422);
        }
        $file = $_FILES['file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return Response::error('validation_error', 'Error en la subida', 422);
        }

        $max = Config::int('UPLOAD_MAX_BYTES', 5_242_880);
        if ((int) $file['size'] > $max) {
            return Response::error('validation_error', 'Archivo demasiado grande', 422, [
                'file' => 'Maximo ' . $max . ' bytes',
            ]);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        if (!isset(self::ALLOWED[$mime])) {
            Logger::warning('upload_rejected_mime', ['mime' => $mime]);
            return Response::error('validation_error', 'Tipo MIME no permitido', 422, [
                'file' => 'Solo JPEG, PNG, WebP, GIF o PDF',
            ]);
        }

        $ext = self::ALLOWED[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return Response::error('server_error', 'No se pudo guardar el archivo', 500);
        }

        $user = SessionAuth::user();
        Database::pdo()->prepare(
            'INSERT INTO archivos (ruta, nombre_original, mime, tamano, descripcion_es, texto_alt_es, subido_por)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            'uploads/' . $name,
            (string) $file['name'],
            $mime,
            (int) $file['size'],
            $request->input('descripcion_es'),
            $request->input('texto_alt_es'),
            $user['id'] ?? null,
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        Logger::info('upload_ok', ['archivo_id' => $id, 'mime' => $mime]);
        return Response::json([
            'id' => $id,
            'ruta' => 'uploads/' . $name,
            'url' => '/storage/uploads/' . $name,
        ], 201);
    }

    public function patch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'descripcion_es', 'descripcion_en', 'texto_alt_es', 'texto_alt_en',
        ]);
        $sets = [];
        $vals = [];
        foreach ($data as $k => $v) {
            $sets[] = "$k = ?";
            $vals[] = $v;
        }
        if ($sets === []) {
            return Response::error('validation_error', 'Sin campos', 422);
        }
        $vals[] = (int) $params['id'];
        Database::pdo()->prepare('UPDATE archivos SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }

    public function delete(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        // Rechazar si el archivo esta referenciado (evita roturas)
        $checks = [
            'SELECT 1 FROM noticias WHERE imagen_id = ? LIMIT 1',
            'SELECT 1 FROM documentos WHERE archivo_id = ? LIMIT 1',
            'SELECT 1 FROM programas WHERE documento_id = ? OR imagen_id = ? LIMIT 1',
            'SELECT 1 FROM personas WHERE foto_id = ? LIMIT 1',
        ];
        $pdo = Database::pdo();
        foreach ($checks as $sql) {
            $stmt = $pdo->prepare($sql);
            if (substr_count($sql, '?') === 2) {
                $stmt->execute([$id, $id]);
            } else {
                $stmt->execute([$id]);
            }
            if ($stmt->fetch()) {
                return Response::error('conflict', 'Archivo en uso', 409);
            }
        }

        $row = $pdo->prepare('SELECT ruta FROM archivos WHERE id = ?');
        $row->execute([$id]);
        $ruta = $row->fetchColumn();
        if (!$ruta) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        $pdo->prepare('DELETE FROM archivos WHERE id = ?')->execute([$id]);
        $full = dirname(__DIR__, 2) . '/storage/' . $ruta;
        if (is_file($full)) {
            @unlink($full);
        }
        return Response::noContent();
    }
}
