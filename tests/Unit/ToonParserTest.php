<?php

namespace Tests\Unit;

use App\Services\Parsers\ToonParser;
use PHPUnit\Framework\TestCase;

class ToonParserTest extends TestCase
{
    /**
     * Test parsing a well-formed TOON string.
     */
    public function test_it_parses_well_formed_toon(): void
    {
        $toonText = <<<'TOON'
title: DISEÑO DE UN SISTEMA BASADO EN INTELIGENCIA ARTIFICIAL
authors: Silvio Scocci, Manuel Casique
tutor: Rafael Millán
abstract: El presente trabajo de investigación analiza el desarrollo
de un sistema capaz de predecir inventarios.
La metodología empleada fue ágil.
keywords: IA, cocina, restaurante
TOON;

        $parser = new ToonParser;
        $result = $parser->parse($toonText);

        $this->assertEquals('DISEÑO DE UN SISTEMA BASADO EN INTELIGENCIA ARTIFICIAL', $result['title']);
        $this->assertEquals('Silvio Scocci, Manuel Casique', $result['authors']);
        $this->assertEquals('Rafael Millán', $result['tutor']);
        $this->assertStringContainsString('El presente trabajo de investigación analiza el desarrollo', $result['abstract']);
        $this->assertStringContainsString('La metodología empleada fue ágil.', $result['abstract']);
        $this->assertEquals('IA, cocina, restaurante', $result['keywords']);
    }

    /**
     * Test parsing TOON with missing fields.
     */
    public function test_it_handles_missing_fields_in_toon(): void
    {
        $toonText = <<<'TOON'
title: SISTEMA DE CONTROL
authors: ? No encontrado
tutor: Rafael Millán
TOON;

        $parser = new ToonParser;
        $result = $parser->parse($toonText);

        $this->assertEquals('SISTEMA DE CONTROL', $result['title']);
        $this->assertEquals('? No encontrado', $result['authors']);
        $this->assertEquals('Rafael Millán', $result['tutor']);
        $this->assertNull($result['abstract']);
        $this->assertNull($result['keywords']);
    }
}
