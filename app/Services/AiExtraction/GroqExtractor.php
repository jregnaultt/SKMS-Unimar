<?php

namespace App\Services\AiExtraction;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class GroqExtractor
 *
 * Handles metadata extraction using the Groq API in the cloud.
 */
class GroqExtractor
{
    protected string $apiKey;

    protected string $model;

    /**
     * GroqExtractor constructor.
     */
    public function __construct()
    {
        $this->apiKey = config('services.groq.key') ?? env('GROQ_API_KEY', '');
        $this->model = config('services.groq.model') ?? env('GROQ_MODEL', 'llama-3.1-8b-instant');
    }

    /**
     * Extracts metadata from text using Groq Cloud API.
     *
     * @param  string  $textFragment  Fragment of text containing cover page and abstract.
     * @return string The raw TOON output from Groq.
     *
     * @throws \Exception
     */
    public function extract(string $textFragment): string
    {
        if (empty($this->apiKey)) {
            throw new \Exception('La API Key de Groq no está configurada.');
        }

        $systemPrompt = "Eres un asistente experto en catalogación de tesis universitarias. Tu tarea es extraer la información solicitada a partir de los primeros fragmentos de texto provistos de una tesis de la Universidad de Margarita.\n"
            ."Debes responder ÚNICAMENTE en el formato TOON (Token-Oriented Object Notation) con las siguientes claves y estructura:\n\n"
            ."title: [Título completo en mayúsculas. Debe ser exactamente el título de la investigación]\n"
            ."authors: [Nombre y apellido de los autores. Si son varios, sepáralos por coma. Limpia títulos como Br., Bachiller]\n"
            ."tutor: [Nombre del tutor académico. Limpia títulos como Ing., Prof., Dr.]\n"
            ."abstract: [El resumen de la tesis. Debe ser el resumen textual de la investigación. Excluye dedicatorias, agradecimientos, índices o introducciones]\n"
            ."keywords: [Palabras clave de la investigación separadas por coma]\n\n"
            ."Reglas críticas:\n"
            ."- No uses llaves {}, ni comillas \"\", ni corchetes [] alrededor de las claves ni de los valores.\n"
            ."- No incluyas ningún texto explicativo, preámbulo ni postulado. Empieza directamente con 'title:'.\n"
            ."- No agregues bloques de código de markdown como ```toon o ```text.\n"
            .'- Si un campo no es encontrado, escribe "? No encontrado".';

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Fragmento de la tesis:\n\n".$textFragment],
                    ],
                    'temperature' => 0.1,
                ]);

            if ($response->failed()) {
                throw new \Exception('Error de la API de Groq: Status '.$response->status().' - '.$response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            if (empty($content)) {
                throw new \Exception('La respuesta de Groq no contiene contenido.');
            }

            return trim($content);
        } catch (\Exception $e) {
            Log::warning('Error en GroqExtractor: '.$e->getMessage());
            throw $e;
        }
    }
}
