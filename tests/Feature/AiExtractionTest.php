<?php

namespace Tests\Feature;

use App\Services\AiExtraction\GroqExtractor;
use App\Services\AiExtraction\OllamaExtractor;
use App\Services\MetadataExtractorService;
use App\Services\Parsers\ToonParser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiExtractionTest extends TestCase
{
    /**
     * Test Groq extractor HTTP communication.
     */
    public function test_groq_extractor_sends_correct_payload_and_returns_toon(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "title: TESIS GROQ\nauthors: Autor Groq\ntutor: Tutor Groq\nabstract: Resumen Groq\nkeywords: k1, k2",
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Define env keys to bypass skip
        config(['services.groq.key' => 'dummy-key']);

        $extractor = new GroqExtractor;
        $result = $extractor->extract('fragmento de tesis');

        $this->assertStringContainsString('title: TESIS GROQ', $result);
        $this->assertStringContainsString('authors: Autor Groq', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && $request['model'] === 'llama-3.1-8b-instant'
                && str_contains($request['messages'][1]['content'], 'fragmento de tesis');
        });
    }

    /**
     * Test Ollama extractor HTTP communication.
     */
    public function test_ollama_extractor_sends_correct_payload_and_returns_toon(): void
    {
        Http::fake([
            'localhost:11434/*' => Http::response([
                'message' => [
                    'content' => "title: TESIS OLLAMA\nauthors: Autor Ollama\ntutor: Tutor Ollama\nabstract: Resumen Ollama\nkeywords: k1, k2",
                ],
            ], 200),
        ]);

        $extractor = new OllamaExtractor;
        $result = $extractor->extract('fragmento de tesis');

        $this->assertStringContainsString('title: TESIS OLLAMA', $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/chat')
                && $request['model'] === 'qwen2.5:1.5b'
                && str_contains($request['messages'][1]['content'], 'fragmento de tesis');
        });
    }

    public function test_cascade_uses_groq_when_successful(): void
    {
        config(['services.groq.key' => 'dummy-key']);

        // Create a partial mock of MetadataExtractorService and set dependencies
        $service = \Mockery::mock(MetadataExtractorService::class)->makePartial();
        $service->setExtractorServices(app(ToonParser::class), app(GroqExtractor::class), app(OllamaExtractor::class));

        // Mock only the file/regex extraction methods
        $service->shouldReceive('extractText')->andReturn('texto de la tesis');
        $service->shouldReceive('extractTextForAi')->andReturn('fragmento de tesis');
        $service->shouldReceive('extractTitle')->andReturn('TITULO REGEX');
        $service->shouldReceive('extractAuthors')->andReturn(null);
        $service->shouldReceive('extractTutor')->andReturn('Tutor Regex');
        $service->shouldReceive('extractAbstract')->andReturn(null);
        $service->shouldReceive('extractKeywords')->andReturn('k1, k2');

        // Mock Groq API call successfully
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "title: TITULO GROQ\nauthors: Autor Groq\ntutor: Tutor Groq\nabstract: Resumen Groq\nkeywords: k1, k2",
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $service->extractMetadata('dummy.pdf');

        $this->assertEquals('TITULO GROQ', $result['title']);
        $this->assertEquals('Autor Groq', $result['authors']);
        $this->assertEquals('Tutor Groq', $result['tutor']);
        $this->assertEquals('Resumen Groq', $result['abstract']);

        // Ollama should not be called
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '11434');
        });
    }

    /**
     * Test fallback cascade in MetadataExtractorService: Groq fails, Ollama succeeds.
     */
    public function test_cascade_falls_back_to_ollama_when_groq_fails(): void
    {
        config(['services.groq.key' => 'dummy-key']);

        // Create a partial mock of MetadataExtractorService and set dependencies
        $service = \Mockery::mock(MetadataExtractorService::class)->makePartial();
        $service->setExtractorServices(app(ToonParser::class), app(GroqExtractor::class), app(OllamaExtractor::class));

        // Mock only the file/regex extraction methods
        $service->shouldReceive('extractText')->andReturn('texto de la tesis');
        $service->shouldReceive('extractTextForAi')->andReturn('fragmento de tesis');
        $service->shouldReceive('extractTitle')->andReturn('TITULO REGEX');
        $service->shouldReceive('extractAuthors')->andReturn('? No encontrado');
        $service->shouldReceive('extractTutor')->andReturn('Tutor Regex');
        $service->shouldReceive('extractAbstract')->andReturn(null);
        $service->shouldReceive('extractKeywords')->andReturn('k1, k2');

        // Mock Groq to fail and Ollama to succeed
        Http::fake([
            'api.groq.com/*' => Http::response([], 500),
            'localhost:11434/*' => Http::response([
                'message' => [
                    'content' => "title: TITULO OLLAMA\nauthors: Autor Ollama\ntutor: Tutor Ollama\nabstract: Resumen Ollama\nkeywords: k1, k2, k3",
                ],
            ], 200),
        ]);

        $result = $service->extractMetadata('dummy.pdf');

        $this->assertEquals('TITULO OLLAMA', $result['title']);
        $this->assertEquals('Autor Ollama', $result['authors']);
        $this->assertEquals('Tutor Ollama', $result['tutor']); // parsed from Ollama
        $this->assertEquals('Resumen Ollama', $result['abstract']);
        $this->assertEquals('k1, k2, k3', $result['keywords']);
    }
}
