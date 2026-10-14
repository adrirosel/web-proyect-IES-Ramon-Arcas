<?php

declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Auth\SessionAuth;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\InvalidSortException;
use App\Support\JsonBody;
use App\Support\Pagination;
use App\Support\PublishedScope;
use App\Support\Sort;
use PDO;

/**
 * CRUD generico para recursos editoriales con estado/slug.
 * Cada controlador define tabla, columnas writeables y sort permitido.
 */
trait EditorialCrud
{
    abstract protected function table(): string;

    /** @return list<string> */
    abstract protected function writable(): array;

    /** @return list<string> */
    abstract protected function sortColumns(): array;

    protected function idColumn(): string
    {
        return 'id';
    }

    protected function publicSlugColumn(): string
    {
        return 'slug';
    }

    protected function usesPublishedScope(): bool
    {
        return true;
    }

    protected function defaultSort(): string
    {
        return 'id';
    }

    public function adminIndex(Request $request, array $params): Response
    {
        try {
            $sort = Sort::fromRequest($request, $this->sortColumns(), $this->defaultSort(), 'desc');
        } catch (InvalidSortException $e) {
            return Sort::toResponse($e);
        }

        $pg = Pagination::fromRequest($request);
        $pdo = Database::pdo();
        $where = '1=1';
        $bind = [];
        $estado = $request->query('estado');
        if (is_string($estado) && $estado !== '') {
            $where .= ' AND estado = ?';
            $bind[] = $estado;
        }
        $q = $request->query('q');
        if (is_string($q) && $q !== '') {
            $where .= ' AND (titulo_es LIKE ? OR slug LIKE ?)';
            $bind[] = '%' . $q . '%';
            $bind[] = '%' . $q . '%';
        }

        $count = $pdo->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE $where");
        $count->execute($bind);
        $total = (int) $count->fetchColumn();

        $sql = "SELECT * FROM {$this->table()} WHERE $where ORDER BY {$sort['column']} {$sort['direction']} LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($bind as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();

        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    public function adminShow(Request $request, array $params): Response
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM {$this->table()} WHERE id = ?");
        $stmt->execute([(int) $params['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        return Response::json(['data' => $row]);
    }

    public function adminStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, $this->writable());
        $user = SessionAuth::user();
        if ($user && in_array('autor_id', $this->writable(), true) && !isset($data['autor_id'])) {
            $data['autor_id'] = $user['id'];
        }
        if ($user && in_array('actualizado_por', $this->writable(), true)) {
            $data['actualizado_por'] = $user['id'];
        }
        if ($data === []) {
            return Response::error('validation_error', 'Sin campos', 422);
        }

        $cols = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table(),
            implode(',', $cols),
            $placeholders
        );
        try {
            Database::pdo()->prepare($sql)->execute(array_values($data));
        } catch (\PDOException $e) {
            return Response::error('conflict', 'No se pudo crear (conflicto o FK)', 409);
        }
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminPatch(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $data = JsonBody::only($request->body, $this->writable());
        $user = SessionAuth::user();
        if ($user && in_array('actualizado_por', $this->writable(), true)) {
            $data['actualizado_por'] = $user['id'];
        }
        if ($data === []) {
            return Response::error('validation_error', 'Sin campos', 422);
        }
        $sets = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $sets[] = "$col = ?";
            $vals[] = $val;
        }
        $vals[] = $id;
        $stmt = Database::pdo()->prepare(
            'UPDATE ' . $this->table() . ' SET ' . implode(', ', $sets) . ' WHERE id = ?'
        );
        $stmt->execute($vals);
        $exists = Database::pdo()->prepare('SELECT 1 FROM ' . $this->table() . ' WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetch()) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        return Response::json(['ok' => true]);
    }

    public function adminDelete(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        // Preferimos archivar si tiene columna estado
        if (in_array('estado', $this->writable(), true)) {
            $stmt = Database::pdo()->prepare(
                "UPDATE {$this->table()} SET estado = 'archivado' WHERE id = ?"
            );
            $stmt->execute([$id]);
        } else {
            $stmt = Database::pdo()->prepare("DELETE FROM {$this->table()} WHERE id = ?");
            $stmt->execute([$id]);
        }
        if ($stmt->rowCount() === 0) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        return Response::noContent();
    }

    public function publicIndex(Request $request, array $params): Response
    {
        try {
            $sort = Sort::fromRequest($request, $this->sortColumns(), $this->defaultSort(), 'desc');
        } catch (InvalidSortException $e) {
            return Sort::toResponse($e);
        }

        $pg = Pagination::fromRequest($request);
        $pdo = Database::pdo();
        $where = $this->usesPublishedScope() ? PublishedScope::sql() : '1=1';
        $bind = [];

        foreach ($this->publicFilters($request) as $clause => $value) {
            $where .= ' AND ' . $clause;
            $bind[] = $value;
        }

        $count = $pdo->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE $where");
        $count->execute($bind);
        $total = (int) $count->fetchColumn();

        $sql = "SELECT * FROM {$this->table()} WHERE $where ORDER BY {$sort['column']} {$sort['direction']} LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($bind as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();

        return Response::json($pg->envelope($this->mapPublicRows($stmt->fetchAll()), $total));
    }

    public function publicShow(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? $params['id'] ?? '';
        $where = $this->publicSlugColumn() . ' = ?';
        if ($this->usesPublishedScope()) {
            $where .= ' AND ' . PublishedScope::sql();
        }
        $stmt = Database::pdo()->prepare("SELECT * FROM {$this->table()} WHERE $where LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            // Mismo 404 para inexistente y no publicado (no filtrar existencia)
            return Response::error('not_found', 'No encontrado', 404);
        }
        return Response::json(['data' => $this->mapPublicRow($row)]);
    }

    /** @return array<string, mixed> clause => value */
    protected function publicFilters(Request $request): array
    {
        return [];
    }

    /** @param list<array<string,mixed>> $rows */
    protected function mapPublicRows(array $rows): array
    {
        return array_map(fn ($r) => $this->mapPublicRow($r), $rows);
    }

    /** @param array<string,mixed> $row */
    protected function mapPublicRow(array $row): array
    {
        return $row;
    }
}
