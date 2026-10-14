<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Slug;
use PHPUnit\Framework\TestCase;

final class SlugTest extends TestCase
{
    public function testNormalizesSpanishCharacters(): void
    {
        $this->assertSame('educacion-secundaria', Slug::from('Educación Secundaria'));
        $this->assertSame('ano-nuevo', Slug::from('Año Nuevo'));
    }
}
