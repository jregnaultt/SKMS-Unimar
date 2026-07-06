<?php

namespace App\Services\AiExtraction;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class OllamaExtractor
 *
 * Handles metadata extraction using a local Ollama instance (Offline Fallback).
 */
class OllamaExtractor
{
    protected string $host;

    protected string $model;

    /**
     * OllamaExtractor constructor.
     */
    public function __construct()
    {
        $this->host = config('services.ollama.host', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'qwen2.5:1.5b');
    }

    /**
     * Extracts metadata from text using local Ollama.
     *
     * @param  string  $textFragment  Fragment of text containing cover page and abstract.
     * @return string The raw TOON output from Ollama.
     *
     * @throws \Exception
     */
    public function extract(string $textFragment): string
    {
        $systemPrompt = "Eres un asistente experto en catalogación de tesis universitarias. Tu tarea es extraer la información solicitada a partir de los primeros fragmentos de texto provistos de una tesis de la Universidad de Margarita.\n"
            ."Debes responder ÚNICAMENTE en el formato TOON (Token-Oriented Object Notation) con las siguientes claves y estructura:\n\n"
            ."title: [Título completo en mayúsculas. Debe ser exactamente el título de la investigación]\n"
            ."authors: [Nombre y apellido de los autores. Si son varios, sepáralos por coma. Limpia títulos como Br., Bachiller]\n"
            ."tutor: [Nombre del tutor académico. Limpia títulos como Ing., Prof., Dr.]\n"
            ."abstract: [El resumen de la tesis. Debe ser el resumen textual de la investigación. Excluye dedicatorias, agradecimientos, índices o introducciones]\n"
            ."keywords: [Palabras clave de la investigación separadas por coma]\n\n"
            ."Reglas críticas:\n"
            ."- Evita incluir el membrete o encabezado institucional de la portada (como 'UNIVERSIDAD DE MARGARITA', 'VICERRECTORADO ACADÉMICO', 'DECANATO DE...', 'TRABAJO DE GRADO', etc.) al inicio del título. El título debe empezar directamente con el título de la investigación. Sin embargo, si el nombre de la universidad o del decanato forma parte del título natural del trabajo al final o en el medio, consérvalo tal cual.\n"
            ."- No uses llaves {}, ni comillas \"\", ni corchetes [] alrededor de las claves ni de los valores.\n"
            ."- No incluyas ningún texto explicativo, preámbulo ni postulado. Empieza directamente con 'title:'.\n"
            ."- No agregues bloques de código de markdown como ```toon o ```text.\n"
            .'- Si un campo no es encontrado, escribe "? No encontrado".';

        try {
            $response = Http::timeout(60) // timeout más largo para inferencia local en CPU
                ->post(rtrim($this->host, '/').'/api/chat', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Fragmento de la tesis:\n\n".$textFragment],
                    ],
                    'stream' => false,
                ]);

            if ($response->failed()) {
                throw new \Exception('Error de Ollama local: Status '.$response->status().' - '.$response->body());
            }

            $data = $response->json();
            $content = $data['message']['content'] ?? '';

            if (empty($content)) {
                throw new \Exception('La respuesta de Ollama no contiene contenido.');
            }

            return trim($content);
        } catch (\Exception $e) {
            Log::warning('Error en OllamaExtractor: '.$e->getMessage());
            throw $e;
        }
    }
}
