<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Auth\Rbac;
use PHPUnit\Framework\TestCase;

final class RbacTest extends TestCase
{
    public function testEditorCannotManageUsers(): void
    {
        $this->assertFalse(Rbac::can('editor', 'usuarios'));
        $this->assertTrue(Rbac::can('admin', 'usuarios'));
    }

    public function testSecretariaCanManageContactosButNotPaginas(): void
    {
        $this->assertTrue(Rbac::can('secretaria', 'contactos'));
        $this->assertFalse(Rbac::can('secretaria', 'paginas'));
    }

    public function testUnknownResourceDenied(): void
    {
        $this->assertFalse(Rbac::can('admin', 'recurso_inventado'));
    }
}
