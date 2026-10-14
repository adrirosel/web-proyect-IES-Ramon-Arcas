<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Password;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\JsonBody;
use App\Support\Pagination;

final class UsuariosController
{
    public function index(Request $request, array $params): Response
    {
        $pg = Pagination::fromRequest($request);
        $pdo = Database::pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        $stmt = $pdo->prepare(
            'SELECT id, email, nombre, rol, activo, created_at, updated_at
             FROM usuarios ORDER BY id ASC LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $pg->limit, \PDO::PARAM_INT);
        $stmt->bindValue(2, $pg->offset, \PDO::PARAM_INT);
        $stmt->execute();
        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    public function store(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['email', 'nombre', 'rol', 'password']);
        $fields = [];
        if (empty($data['email']) || !filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
            $fields['email'] = 'Email no valido';
        }
        if (empty($data['nombre'])) {
            $fields['nombre'] = 'Requerido';
        }
        if (!in_array($data['rol'] ?? '', ['admin', 'editor', 'secretaria'], true)) {
            $fields['rol'] = 'Rol no valido';
        }
        if (empty($data['password']) || strlen((string) $data['password']) < 10) {
            $fields['password'] = 'Minimo 10 caracteres';
        }
        if ($fields !== []) {
            return Response::error('validation_error', 'Datos no validos', 422, $fields);
        }

        try {
            Database::pdo()->prepare(
                'INSERT INTO usuarios (email, nombre, rol, password_hash, activo) VALUES (?, ?, ?, ?, 1)'
            )->execute([
                $data['email'],
                $data['nombre'],
                $data['rol'],
                Password::hash((string) $data['password']),
            ]);
        } catch (\PDOException) {
            return Response::error('conflict', 'Email ya registrado', 409);
        }

        $id = (int) Database::pdo()->lastInsertId();
        return Response::json(['id' => $id], 201);
    }

    public function patch(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $data = JsonBody::only($request->body, ['email', 'nombre', 'rol', 'password', 'activo']);
        if ($data === []) {
            return Response::error('validation_error', 'Sin campos', 422);
        }

        $sets = [];
        $vals = [];
        foreach (['email', 'nombre', 'rol'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = ?";
                $vals[] = $data[$col];
            }
        }
        if (array_key_exists('activo', $data)) {
            $sets[] = 'activo = ?';
            $vals[] = (int) (bool) $data['activo'];
        }
        if (!empty($data['password'])) {
            $sets[] = 'password_hash = ?';
            $vals[] = Password::hash((string) $data['password']);
        }
        $vals[] = $id;

        $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($vals);
        if ($stmt->rowCount() === 0) {
            // Puede ser 0 si valores iguales; comprobar existencia
            $exists = Database::pdo()->prepare('SELECT 1 FROM usuarios WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetch()) {
                return Response::error('not_found', 'Usuario no encontrado', 404);
            }
        }
        return Response::json(['ok' => true]);
    }
}
