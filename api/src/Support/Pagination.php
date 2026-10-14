<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Request;

/**
 * Paginacion estandar del contrato: page, limit, meta.total.
 * Limit techo evita listados abusivos desde clientes anonimos.
 */
final class Pagination
{
    public const DEFAULT_LIMIT = 12;
    public const MAX_LIMIT = 50;

    public function __construct(
        public readonly int $page,
        public readonly int $limit,
        public readonly int $offset,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = (int) $request->query('limit', self::DEFAULT_LIMIT);
        $limit = max(1, min(self::MAX_LIMIT, $limit));
        $offset = ($page - 1) * $limit;

        return new self($page, $limit, $offset);
    }

    /** @param list<mixed> $data */
    /** @return array{data:list<mixed>,meta:array{page:int,limit:int,total:int}} */
    public function envelope(array $data, int $total): array
    {
        return [
            'data' => $data,
            'meta' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $total,
            ],
        ];
    }
}
