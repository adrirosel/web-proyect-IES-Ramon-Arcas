<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\Pagination;
use App\Support\RateLimiter;
use PDO;

final class ContactoController
{
    private const TIPOS = ['consulta', 'sugerencia', 'queja', 'rincon_pensar'];

    /** Acuse minimo: no devolver el mensaje ni datos sensibles. */
    public function store(Request $request, array $params): Response
    {
        if (!RateLimiter::hit('contacto:' . $request->ip, 5, 600)) {
            return Response::error('rate_limited', 'Demasiados envios', 429);
        }

        $nombre = trim((string) $request->input('nombre', ''));
        $email = trim((string) $request->input('email', ''));
        $tipo = (string) $request->input('tipo', '');
        $mensaje = trim((string) $request->input('mensaje', ''));
        $consent = (bool) $request->input('consentimiento_privacidad', false);

        $fields = [];
        if ($nombre === '' || mb_strlen($nombre) > 160) {
            $fields['nombre'] = 'Requerido (max 160)';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fields['email'] = 'Email no valido';
        }
        if (!in_array($tipo, self::TIPOS, true)) {
            $fields['tipo'] = 'Tipo no permitido';
        }
        if ($mensaje === '' || mb_strlen($mensaje) > 5000) {
            $fields['mensaje'] = 'Requerido (max 5000)';
        }
        if (!$consent) {
            $fields['consentimiento_privacidad'] = 'Debes aceptar el aviso de privacidad';
        }
        if ($fields !== []) {
            return Response::error('validation_error', 'Datos no validos', 422, $fields);
        }

        Database::pdo()->prepare(
            'INSERT INTO contactos (nombre, email, tipo, mensaje, consentimiento_privacidad, estado)
             VALUES (?, ?, ?, ?, 1, \'pendiente\')'
        )->execute([$nombre, $email, $tipo, $mensaje]);

        $id = (int) Database::pdo()->lastInsertId();
        return Response::json(['id' => $id, 'recibido' => true], 201);
    }

    public function adminIndex(Request $request, array $params): Response
    {
        $pg = Pagination::fromRequest($request);
        $where = '1=1';
        $bind = [];
        if ($e = $request->query('estado')) {
            $where .= ' AND estado = ?';
            $bind[] = $e;
        }
        if ($t = $request->query('tipo')) {
            $where .= ' AND tipo = ?';
            $bind[] = $t;
        }
        $pdo = Database::pdo();
        $c = $pdo->prepare("SELECT COUNT(*) FROM contactos WHERE $where");
        $c->execute($bind);
        $total = (int) $c->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM contactos WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?");
        $i = 1;
        foreach ($bind as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();
        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    /** Solo cambio de estado (patron pendiente → en_curso → finalizado). */
    public function adminPatch(Request $request, array $params): Response
    {
        $estado = (string) $request->input('estado', '');
        if (!in_array($estado, ['pendiente', 'en_curso', 'finalizado'], true)) {
            return Response::error('validation_error', 'Estado no valido', 422, ['estado' => 'Valor no permitido']);
        }
        $stmt = Database::pdo()->prepare('UPDATE contactos SET estado = ? WHERE id = ?');
        $stmt->execute([$estado, (int) $params['id']]);
        if ($stmt->rowCount() === 0) {
            $exists = Database::pdo()->prepare('SELECT 1 FROM contactos WHERE id = ?');
            $exists->execute([(int) $params['id']]);
            if (!$exists->fetch()) {
                return Response::error('not_found', 'No encontrado', 404);
            }
        }
        return Response::json(['ok' => true]);
    }
}
