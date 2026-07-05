<?php

namespace Tests\Unit;

use App\Services\MetadataExtractorService;
use PHPUnit\Framework\TestCase;

class MetadataExtractorServiceTest extends TestCase
{
    public function test_it_extracts_title()
    {
        $text = "UNIVERSIDAD DE MARGARITA SUBSISTEMA DE DOCENCIA\nDECANATO DE INGENIERÍA Y AFINES TRABAJO DE INVESTIGACIÓN\nDESARROLLO DE UN MÓDULO DE MEDIOS AUDIOVISUALES CON INTEGRACIÓN DE INTELIGENCIA ARTIFICIAL PARA EL PORTAL WEB DE LA UNIVERSIDAD DE MARGARITA, MUNICIPIO GARCÍA, ESTADO NUEVA ESPARTA.\nElaborado por: Br. Manuel Isaac Casique Osuna Tutor: Ing. César Humberto Requena Lora";

        $service = new MetadataExtractorService;
        $title = $service->extractTitle($text);

        $this->assertEquals(
            'DESARROLLO DE UN MÓDULO DE MEDIOS AUDIOVISUALES CON INTEGRACIÓN DE INTELIGENCIA ARTIFICIAL PARA EL PORTAL WEB DE LA UNIVERSIDAD DE MARGARITA, MUNICIPIO GARCÍA, ESTADO NUEVA ESPARTA.',
            $title
        );
    }

    public function test_it_extracts_authors_and_tutors()
    {
        $text = "Elaborado por: Br. Manuel Isaac Casique Osuna Tutor: Ing. César Humberto Requena Lora\nValle del Espíritu Santo, Marzo de 2026";

        $service = new MetadataExtractorService;
        $authors = $service->extractAuthors($text);
        $tutor = $service->extractTutor($text);

        $this->assertEquals('Manuel Isaac Casique Osuna', $authors);
        $this->assertEquals('César Humberto Requena Lora', $tutor);
    }

    public function test_it_extracts_abstract_and_keywords()
    {
        $text = "RESUMEN\nEl presente trabajo tiene como objetivo el desarrollo de un módulo audiovisual. Este módulo permite subir videos usando inteligencia artificial.\nPalabras Clave: módulo, audiovisual, inteligencia artificial, portal web.\nINTRODUCCIÓN";

        $service = new MetadataExtractorService;
        $abstract = $service->extractAbstract($text);
        $keywords = $service->extractKeywords($text);

        $this->assertStringContainsString('El presente trabajo tiene como objetivo el desarrollo', $abstract);
        $this->assertEquals('módulo, audiovisual, inteligencia artificial, portal web', $keywords);
    }

    public function test_it_extracts_abstract_with_missing_header_fallback()
    {
        $text = "UNIVERSIDAD DE MARGARITA\n"
            ."FASE DE DEMOSTRACIÓN\n"
            ."EL ROL DEL COMUNICADOR SOCIAL\n"
            ."Elaborado por: Alejandro Quintero\n"
            ."Diciembre, 2025\n\n"
            ."La presente investigación analiza el fenómeno de las apuestas.\n\n"
            .'Palabras clave: Apuestas, Juego, Regulación.';

        $service = new MetadataExtractorService;
        $abstract = $service->extractAbstract($text);

        $this->assertStringContainsString('La presente investigación analiza el fenómeno', $abstract);
        $this->assertStringNotContainsString('Elaborado por', $abstract);
    }

    public function test_it_extracts_text_from_docx()
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive class not found (php-zip extension missing).');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        unlink($tempFile);
        $tempFile .= '.docx';

        $zip = new \ZipArchive;
        $zip->open($tempFile, \ZipArchive::CREATE);
        $xmlContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'
            .'<w:p><w:r><w:t>UNIVERSIDAD DE MARGARITA</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t>Elaborado por: Br. Manuel Isaac Casique Osuna Tutor: Ing. César Humberto Requena Lora</w:t></w:r></w:p>'
            .'</w:body>'
            .'</w:document>';
        $zip->addFromString('word/document.xml', $xmlContent);
        $zip->close();

        $service = new MetadataExtractorService;
        $text = $service->extractTextFromDocx($tempFile);

        $this->assertStringContainsString('UNIVERSIDAD DE MARGARITA', $text);
        $this->assertStringContainsString('Elaborado por: Br. Manuel Isaac Casique Osuna', $text);

        unlink($tempFile);
    }
}
