<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Http\Request;
use App\Support\InvalidSortException;
use App\Support\Sort;
use PHPUnit\Framework\TestCase;

final class SortTest extends TestCase
{
    public function testAcceptsWhitelistedColumn(): void
    {
        $req = new Request('GET', '/', ['sort' => 'titulo_es', 'direction' => 'asc'], [], [], '', '127.0.0.1');
        $sort = Sort::fromRequest($req, ['id', 'titulo_es'], 'id');
        $this->assertSame('titulo_es', $sort['column']);
        $this->assertSame('asc', $sort['direction']);
    }

    public function testRejectsUnknownColumn(): void
    {
        $this->expectException(InvalidSortException::class);
        $req = new Request('GET', '/', ['sort' => 'password_hash'], [], [], '', '127.0.0.1');
        Sort::fromRequest($req, ['id', 'titulo_es'], 'id');
    }
}
