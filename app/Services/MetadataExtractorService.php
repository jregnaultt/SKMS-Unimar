<?php

namespace App\Services;

use App\Services\AiExtraction\GroqExtractor;
use App\Services\AiExtraction\OllamaExtractor;
use App\Services\Parsers\ToonParser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\PdfToText\Pdf;

class MetadataExtractorService
{
    /**
     * MetadataExtractorService constructor.
     */
    public function __construct(
        protected ?ToonParser $toonParser = null,
        protected ?GroqExtractor $groqExtractor = null,
        protected ?OllamaExtractor $ollamaExtractor = null
    ) {}

    /**
     * Set the extractor services manually (useful for testing and mocks).
     */
    public function setExtractorServices(ToonParser $toonParser, GroqExtractor $groqExtractor, OllamaExtractor $ollamaExtractor): void
    {
        $this->toonParser = $toonParser;
        $this->groqExtractor = $groqExtractor;
        $this->ollamaExtractor = $ollamaExtractor;
    }

    /**
     * Extracts full metadata from a file, using a hybrid cascading pipeline (Regex -> Groq -> Ollama).
     *
     * @return array{title: ?string, authors: ?string, tutor: ?string, abstract: ?string, keywords: ?string}
     */
    public function extractMetadata(string $filePath): array
    {
        $text = $this->extractText($filePath);

        $metadata = [
            'title' => $this->extractTitle($text),
            'authors' => $this->extractAuthors($text),
            'tutor' => $this->extractTutor($text),
            'abstract' => $this->extractAbstract($text),
            'keywords' => $this->extractKeywords($text),
        ];

        if ($this->needsAiRefinement($metadata)) {
            Log::info('El motor Regex no completó todos los metadatos. Activando refinamiento con IA para: '.basename($filePath));
            try {
                $aiText = $this->extractTextForAi($filePath);
                $groqExtractor = $this->groqExtractor ?? new GroqExtractor;
                $toonParser = $this->toonParser ?? new ToonParser;
                $toonResult = $groqExtractor->extract($aiText);
                $refined = $toonParser->parse($toonResult);

                $metadata = $this->mergeMetadata($metadata, $refined);
                $metadata['_prompt'] = Str::limit($aiText, 1500, ' ... [Texto truncado en la consola para evitar límites de tamaño en WebSocket]');
                $metadata['_toon'] = Str::limit($toonResult, 1500, ' ... [Texto truncado en la consola para evitar límites de tamaño en WebSocket]');
            } catch (\Exception $groqEx) {
                Log::warning('La extracción con Groq falló, intentando fallback local con Ollama (Qwen2.5): '.$groqEx->getMessage());
                try {
                    $aiText = $this->extractTextForAi($filePath);
                    $ollamaExtractor = $this->ollamaExtractor ?? new OllamaExtractor;
                    $toonParser = $this->toonParser ?? new ToonParser;
                    $toonResult = $ollamaExtractor->extract($aiText);
                    $refined = $toonParser->parse($toonResult);

                    $metadata = $this->mergeMetadata($metadata, $refined);
                    $metadata['_prompt'] = Str::limit($aiText, 1500, ' ... [Texto truncado en la consola para evitar límites de tamaño en WebSocket]');
                    $metadata['_toon'] = Str::limit($toonResult, 1500, ' ... [Texto truncado en la consola para evitar límites de tamaño en WebSocket]');
                } catch (\Exception $ollamaEx) {
                    Log::error('Todos los métodos de extracción con IA fallaron para '.basename($filePath).': '.$ollamaEx->getMessage());
                }
            }
        }

        return $metadata;
    }

    /**
     * Determines if the extracted metadata is incomplete and needs AI refinement.
     */
    protected function needsAiRefinement(array $metadata): bool
    {
        foreach (['title', 'authors', 'tutor', 'abstract'] as $field) {
            $value = $metadata[$field] ?? null;
            if (empty($value) || $value === '? No encontrado' || trim($value) === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Merges regex-extracted metadata with LLM-refined metadata.
     */
    protected function mergeMetadata(array $original, array $refined): array
    {
        $merged = $original;
        foreach ($refined as $key => $value) {
            if (! empty($value) && $value !== '? No encontrado' && trim($value) !== '') {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Extracts a limited fragment of text suitable for LLM processing (cover pages + abstract).
     */
    public function extractTextForAi(string $filePath): string
    {
        if (! file_exists($filePath)) {
            throw new \Exception('Archivo no encontrado: '.$filePath);
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $safePath = $tempDir.DIRECTORY_SEPARATOR.'temp_extract_ai_'.uniqid().'.'.$extension;

        if (! copy($filePath, $safePath)) {
            throw new \Exception('No se pudo copiar el archivo al directorio temporal para procesamiento: '.$filePath);
        }

        try {
            if ($extension === 'docx') {
                $text = $this->extractTextFromDocx($safePath);

                return mb_substr($text, 0, 15000, 'UTF-8');
            } else {
                return (new Pdf('pdftotext'))
                    ->setPdf($safePath)
                    ->setOptions(['-enc UTF-8', '-f 1', '-l 15'])
                    ->text();
            }
        } finally {
            if (file_exists($safePath)) {
                unlink($safePath);
            }
        }
    }

    /**
     * Extracts raw text from a PDF or DOCX file, handling encoding and path issues.
     */
    public function extractText(string $filePath): string
    {
        if (! file_exists($filePath)) {
            throw new \Exception('Archivo no encontrado: '.$filePath);
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Copy to a safe ASCII path to avoid Windows shell encoding bugs with accented characters
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $safePath = $tempDir.DIRECTORY_SEPARATOR.'temp_extract_'.uniqid().'.'.$extension;

        if (! copy($filePath, $safePath)) {
            throw new \Exception('No se pudo copiar el archivo al directorio temporal para procesamiento: '.$filePath);
        }

        try {
            if ($extension === 'docx') {
                $text = $this->extractTextFromDocx($safePath);
            } else {
                $text = (new Pdf('pdftotext'))
                    ->setPdf($safePath)
                    ->setOptions(['-enc UTF-8'])
                    ->text();
            }
        } finally {
            if (file_exists($safePath)) {
                unlink($safePath);
            }
        }

        // Clean UTF-8 encoding
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if (function_exists('mb_scrub')) {
            $text = mb_scrub($text, 'UTF-8');
        }

        // Remove zero-width spaces, BOM, replacement characters, and normalize non-breaking spaces
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{FFFD}]/u', '', $text);
        $text = preg_replace('/\x{00A0}/u', ' ', $text);

        return $text;
    }

    /**
     * Extracts raw text from a DOCX (Word) file.
     *
     * @throws \Exception
     */
    public function extractTextFromDocx(string $filePath): string
    {
        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \Exception('No se pudo abrir el archivo Word (DOCX).');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xml) {
            return '';
        }

        // Clean XML encoding
        $xml = iconv('UTF-8', 'UTF-8//IGNORE', $xml);
        if (function_exists('mb_scrub')) {
            $xml = mb_scrub($xml, 'UTF-8');
        }

        // Capture paragraphs using word boundary for w:p
        preg_match_all('/<w:p\b[^>]*>(.*?)<\/w:p>/us', $xml, $paragraphs);

        $output = [];
        foreach ($paragraphs[1] as $pXml) {
            $hasPageBreak = (str_contains($pXml, 'type="page"') || str_contains($pXml, 'lastRenderedPageBreak'));
            // Replace line breaks with a newline
            $pXml = preg_replace('/<w:br\b[^>]*\/>/us', "\n", $pXml);

            // Extract all text elements in this paragraph using word boundary \b
            preg_match_all('/<w:t\b[^>]*>(.*?)<\/w:t>/us', $pXml, $tMatches);

            $pText = '';
            foreach ($tMatches[1] as $tVal) {
                $pText .= html_entity_decode($tVal, ENT_QUOTES | ENT_XML1, 'UTF-8');
            }

            if ($hasPageBreak) {
                $output[] = $pText."\f";
            } else {
                $output[] = $pText;
            }
        }

        return implode("\n", $output);
    }

    /**
     * Cleans up names (authors or tutors) by removing titles, ID numbers, and parenthesis.
     */
    public function cleanName(string $name): string
    {
        // Replace commas with space to handle name separations and clean names
        $name = str_replace(',', ' ', $name);

        // Remove ID cards like V-12.345.678 or C.I. 12.345.678
        $name = preg_replace('/(?:\b[VC]\.?I?\.?\s*[-.:]?\s*\d+(?:\.\d+)*\b|\b\d{2}\.\d{3}\.\d{3}\b)/iu', '', $name);

        // Remove parenthesis and brackets content
        $name = preg_replace('/\((.*?)\)/iu', '', $name);
        $name = preg_replace('/\[(.*?)\]/iu', '', $name);

        // Remove title prefixes globally with word boundaries to clean inner titles in multi-author scenarios
        $innerTitles = [
            '/\bBr\b\.?/iu',
            '/\bBrs\b\.?/iu',
            '/\bBachiller(?:es)?\b\.?/iu',
            '/\bEstudiante(?:s)?\b\.?/iu',
            '/\bIntegrante(?:s)?\b\.?/iu',
            '/\bAlumno(?:s)?\b\.?/iu',
            '/\bIng\b\.?/iu',
            '/\bProf\b\.?/iu',
            '/\bDr\b\.?/iu',
            '/\bDra\b\.?/iu',
            '/\bMSc\b\.?/iu',
            '/\bEsp\b\.?/iu',
            '/\bLic\b\.?/iu',
            '/\bAbg\b\.?/iu',
            '/\bMs\b\.?\s*(?:C\b\.?)?/iu',
        ];

        foreach ($innerTitles as $p) {
            $name = preg_replace($p, ' ', $name);
        }

        $name = trim($name, " \t\n\r\0\x0B:-.,;()[]/?");
        $name = preg_replace('/\s+/', ' ', $name);

        return $name;
    }

    /**
     * Normalizes a name (swaps last/first names if comma is present, cleans up spacing,
     * and maps to canonical normalized names for known tutors and authors).
     */
    public function normalizeName(string $name): string
    {
        $name = trim($name);
        if (empty($name)) {
            return $name;
        }

        // 1. Check if we have multiple names separated by commas (e.g. "Santiago Rodríguez, Mariana Mouhamed")
        if (str_contains($name, ',')) {
            $parts = explode(',', $name);
            if (count($parts) === 2) {
                $part1 = trim($parts[0]);
                $part2 = trim($parts[1]);

                // Count words in each part
                $words1 = count(preg_split('/\s+/', $part1));
                $words2 = count(preg_split('/\s+/', $part2));

                // If both parts have 2 or more words (excluding metadata like C.I.), they are separate authors
                if ($words1 >= 2 && $words2 >= 2 && ! preg_match('/^(?:C\.?I\.?|V-|E-|\d)/i', $part2)) {
                    $norm1 = $this->normalizeName($part1);
                    $norm2 = $this->normalizeName($part2);

                    return $norm1.', '.$norm2;
                }

                // Otherwise, swap "Lastname, Firstname" format if part2 is not metadata
                if (! preg_match('/^(?:C\.?I\.?|V-|E-|\d)/i', $part2) && strlen($part1) > 1 && strlen($part2) > 1) {
                    $name = $part2.' '.$part1;
                }
            }
        }

        // Clean name (removes Br, Prof, Ing, CI numbers, etc.)
        $name = $this->cleanName($name);

        // 2. Map of known aliases/variants to normalized names
        $normalizationMap = [
            // Tutors
            'requena cesar' => 'César Requena',
            'cesar requena' => 'César Requena',
            'oswald marin' => 'Oswald Marín',
            'oswald marín' => 'Oswald Marín',
            'madeline rodriguez' => 'Madeline Rodríguez',
            'madeline rodríguez' => 'Madeline Rodríguez',
            'mariano garcia' => 'Mariano García',
            'mariano garcía' => 'Mariano García',
            'guadalupe malave' => 'Guadalupe Malaver',
            'guadalupe malavé' => 'Guadalupe Malaver',
            'guadalupe malaver' => 'Guadalupe Malaver',
            'guadalupe j malaver n' => 'Guadalupe Malaver',
            'valentina martinez' => 'Valentina Martínez',
            'valentina martínez' => 'Valentina Martínez',
            'joselis totesautt' => 'Joselis Totesautt',
            'joselis totesautt t' => 'Joselis Totesautt',
            'joselis totesautt triana' => 'Joselis Totesautt',
            'rafael millan' => 'Rafael Millán',
            'rafael millán' => 'Rafael Millán',
            'hiram gonzalez' => 'Hiram González',
            'hiram gonzález' => 'Hiram González',
            'hiram gonzalez gomez' => 'Hiram González',
            'hiram gonzález gómez' => 'Hiram González',
            'silvestre cardenas' => 'Silvestre Cárdenas',
            'silvestre cárdenas' => 'Silvestre Cárdenas',
            'mariana marval' => 'Mariana Marval',

            // Authors/Students
            'franklin fuentes' => 'Franklin Fuentes',
            'fuentes franklin' => 'Franklin Fuentes',
            'marquina astrid' => 'Astrid Marquina',
            'noriega norbenys' => 'Norbenys Noriega',
            'moises antonio petit benitez' => 'Moisés Antonio Petit Benítez',
            'moises antonio petit benítez' => 'Moisés Antonio Petit Benítez',
            'williams alas' => 'Williams Alas',
            'moises alejandro gomez salazar' => 'Moisés Alejandro Gómez Salazar',
            'moises alejandro gómez salazar' => 'Moisés Alejandro Gómez Salazar',
            'pedro jose hernandez' => 'Pedro José Hernández',
            'pedro josé hernández' => 'Pedro José Hernández',
            'samuel marcano' => 'Samuel Marcano',
            'angel perez' => 'Ángel Pérez',
            'jorge silva' => 'Jorge Silva',
            'abdl taktak' => 'Abdl Taktak',
            'eduardo sanchez garcia' => 'Eduardo Sánchez García',
            'eduardo sánchez garcía' => 'Eduardo Sánchez García',
            'galvys rodriguez' => 'Galvys Rodríguez',
            'galvys rodríguez' => 'Galvys Rodríguez',
            'manuel alejandro delgado sandoval' => 'Manuel Alejandro Delgado Sandoval',
            'oscar enrique vega gomez' => 'Oscar Enrique Vega Gómez',
            'oscar enrique vega gómez' => 'Oscar Enrique Vega Gómez',
            'grecia alejandra valerio moussa' => 'Grecia Alejandra Valerio Moussa',
            'daniel alarcon' => 'Daniel Alarcón',
            'daniel alarcón' => 'Daniel Alarcón',
            'estefania garcia' => 'Estefanía García',
            'estefanía garcía' => 'Estefanía García',
            'gilberto jimenez marcano' => 'Gilberto Jiménez Marcano',
            'gilberto jiménez marcano' => 'Gilberto Jiménez Marcano',
            'mariselys a. fuentes k' => 'Mariselys Fuentes',
            'mariselys a. fuentes k c.i' => 'Mariselys Fuentes',
            'mariselys fuentes' => 'Mariselys Fuentes',
            'teilor aguilar' => 'Teilor Aguilar',
            'barbara t. padilla lopez' => 'Bárbara T. Padilla López',
            'barbara t. padilla lópez' => 'Bárbara T. Padilla López',
            'nicol rodriguez' => 'Nicol Rodríguez',
            'nicol rodríguez' => 'Nicol Rodríguez',
            'daniel mendoza' => 'Daniel Mendoza',
            'luis cordova' => 'Luis Córdova',
            'luis córdova' => 'Luis Córdova',
        ];

        // Clean accents and lower case for key matching
        $key = strtolower(preg_replace('/[\x{0300}-\x{036f}]/u', '', iconv('UTF-8', 'ASCII//TRANSLIT', $name)));
        // Remove multiple spaces
        $key = preg_replace('/\s+/', ' ', $key);
        $key = trim($key, " \t\n\r\0\x0B:-.,;()[]/?");

        if (isset($normalizationMap[$key])) {
            return $normalizationMap[$key];
        }

        return $name;
    }

    /**
     * Extracts the title from the cover page text.
     */
    public function extractTitle(string $text): ?string
    {
        $authorMarkers = [
            '/Elaborado\s+por/iu',
            '/Realizado\s+por/iu',
            '/Presentado\s+por/iu',
            '/Creado\s+por/iu',
            '/Autor(?:es)?\b/iu',
            '/Brs?\b\.?/iu',
            '/(?:\n\s*|:\s*|,\s*)Bachiller(?:es)?\b/iu',
            '/(?:\n\s*|:\s*|,\s*)Estudiante(?:s)?\b/iu',
            '/(?:\n\s*|:\s*|,\s*)Integrante(?:s)?\b/iu',
            '/(?:\n\s*|:\s*|,\s*)Alumno(?:s)?\b/iu',
            '/Por:/iu',
            '/Tutor(?:a)?(?:es)?\b/iu',
        ];

        $endPos = min(mb_strlen($text), 5000);
        foreach ($authorMarkers as $pattern) {
            if (preg_match($pattern, mb_substr($text, 0, $endPos), $match, PREG_OFFSET_CAPTURE)) {
                $byteOffset = $match[0][1];
                $charOffset = mb_strlen(substr($text, 0, $byteOffset));
                if ($charOffset < $endPos) {
                    $endPos = $charOffset;
                }
            }
        }

        $titleBlock = mb_substr($text, 0, $endPos);

        $stripPatterns = [
            '/^UNIVERSIDAD\s+DE\s+MARGARITA\b/iu',
            '/^UNIVE[R]?SIDAD\s+DE\s+MARGARITA\b/iu',
            '/^UNIVERISDAD\s+DE\s+MARGARITA\b/iu',
            '/^UNIVERSIDAD\s+CAT[OÓ]LICA\s+SANTA\s+ROSA\b/iu',
            '/^ALMA\s+M[AÁ]TER\s+DEL\s+CARIBE\b/iu',
            '/^SUBSISTEMA\s+DE\s+DOCENCIA\b/iu',
            '/^SUBSISTEMA\s+DE\s+DECENCIA\b/iu',
            '/^VICERRECTORADO\s+ACAD[EÉ]MICO\b/iu',
            '/^DECANATO\s+DE\s+INGEN[I]?ER[IÍ]A\s+Y\s+AFINES\b/iu',
            '/^DECANATO\s+DE\s+INGEN[I]?ER[IÍ]A\s+DE\s+SISTEMAS\b/iu',
            '/^DECANATO\s+DE\s+INGEN[I]?ER[IÍ]A\b/iu',
            '/^DECANATO\s+DE\s+HUMANIDADES,\s+ARTES\s+Y\s+EDUCACI[OÓ]N\b/iu',
            '/^DECANATO\s+DE\s+CIENCIAS\s+JUR[IÍ]DICAS\s+Y\s+POL[IÍ]TICAS\b/iu',
            '/^DECANATO\s+DE\s+CIENCIAS\s+ECON[OÓ]MICAS\s+Y\s+SOCIALES\b/iu',
            '/^DECANATO\s+DE\s+SISTEMAS?(?:\s+Y\s+AFINES)?\b/iu',
            '/^COORDINACI[OÓ]N\s+DE\s+INVESTIGACI[OÓ]N(?:ES)?\s+Y\s+PASANT[IÍ]AS?\b/iu',
            '/^COORDINACI[OÓ]N\s+DE\s+INVESTIGACI[OÓ]N(?:ES)?\b/iu',
            '/^COORDINACI[OÓ]N\s+DE\s+COMUNICACI[OÓ]N\s+SOCIAL\b/iu',
            '/^COORDINACI[OÓ]N\s+DE\s+INGEN[I]?ER[IÍ]A\s+DE\s+SISTEMAS\b/iu',
            '/^COORDINACI[OÓ]N\s+DE\s+PSICOLOG[IÍ]A\b/iu',
            '/^COORDINACI[OÓ]N\s+DE\s+DERECHO\b/iu',
            '/^COORDINACI[OÓ]N\s+DE\s+IDIOMAS\s+MODERNOS\b/iu',
            '/^COORDINACI[OÓ]N\s+DE\s+ADMINISTRACI[OÓ]N\b/iu',
            '/^COORDINACI[OÓ]N\s+DE\s+CONTADUR[IÍ]A\b/iu',
            '/^Y\s+AFINES\s+COORDINACI[OÓ]N\s+DE\s+INVESTIGACI[OÓ]N(?:ES)?\s+Y\s+PASANT[IÍ]AS?\b/iu',
            '/^Y\s+AFINES\s+COORDINACI[OÓ]N\s+DE\s+INVESTIGACI[OÓ]N(?:ES)?\b/iu',
            '/^DE\s+SISTEMA\s+Y\s+AFINES\s+COORDINACI[OÓ]N\s+DE\s+INVESTIGACI[OÓ]N(?:ES)?\b/iu',
            '/^Y\s+CIENCIAS\s+AFINES\b/iu',
            '/^PSICOLOG[IÍ]A\s+MENCI[OÓ]N\s+INTERVENCI[OÓ]N\s+SOCIAL\b/iu',
            '/^PSICOLOG[IÍ]A\b/iu',
            '/^INGEN[I]?ER[IÍ]A\s+DE\s+SISTEMAS\b/iu',
            '/^DERECHO\b/iu',
            '/^ADMINISTRACI[OÓ]N\s+DE\s+EMPRESAS\b/iu',
            '/^CONTADUR[IÍ]A\s+P[UÚ]BLICA\b/iu',
            '/^IDIOMAS\s+MODERNOS\b/iu',
            '/^SEMINARIO\s+METODOL[OÓ]GICO\s+DE\s+LA\s+INVESTIGACI[OÓ]N\b/iu',
            '/^SEMINARIO\s+METOL[OÓ]GICO\s+DE\s+INVESTIGACI[OÓ]N\b/iu',
            '/^SEMINARIO\s+METODOL[OÓ]GICO\s+DE\s+INVESTIGACI[OÓ]N\b/iu',
            '/^SEMINARIO\s+METODOL[OÓ]GICO\b/iu',
            '/^UNIDAD\s+CURRICULAR\s*:\s*TRABAJO\s+DE\s+GRADO\s+I[I]?\b/iu',
            '/^TRABAJO\s+DE\s+GRADO\s+I[I]?\b/iu',
            '/^TRABAJO\s+DE\s+INVESTIGACI[OÓ]N\s+I[I]?\b/iu',
            '/^TRABAJO\s+DE\s+INVESTIGACI[OÓ]N\b/iu',
            '/^PROYECTO\s+DE\s+GRADO\b/iu',
            '/^MONOGRAF[IÍ]A\b/iu',
            '/^TESIS\s+DE\s+GRADO\b/iu',
            '/^TESIS\b/iu',
            '/^TRABAJO\s+ESPECIAL\s+DE\s+GRADO\b/iu',
            '/^INFORME\s+DE\s+PASANT[IÍ]A\b/iu',
            '/^FASE\s+DE\s+DEMOSTRACI[OÓ]N\b/iu',
            '/^DEMOSTRACI[OÓ]N\b/iu',
            '/^PASANT[IÍ]A\b/iu',
            '/(?:Trabajo\s+de\s+Investigaci[oó]n|Trabajo\s+de\s+Grado(?: I+)?|Tesis(?:\s+de\s+Grado)?|Proyecto\s+de\s+Grado|Informe\s+de\s+Pasant[ií]a)\s*$/iu',
        ];

        $lines = explode("\n", $titleBlock);
        $cleanLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $changed = true;
            while ($changed) {
                $changed = false;
                foreach ($stripPatterns as $pat) {
                    $cleaned = trim(preg_replace($pat, '', $line));
                    if ($cleaned !== $line) {
                        $line = $cleaned;
                        $changed = true;
                    }
                }
            }

            $line = ltrim($line, " \t\n\r\0\x0B:-.,;“”\"'?");
            $line = rtrim($line, " \t\n\r\0\x0B“”\"'?");
            if ($line !== '') {
                $cleanLines[] = $line;
            }
        }

        $title = implode(' ', $cleanLines);
        $title = trim(preg_replace('/\s+/', ' ', $title));
        $title = ltrim($title, " \t\n\r\0\x0B:-.,;“”\"'?");
        $title = rtrim($title, " \t\n\r\0\x0B“”\"'?");

        return (strlen($title) > 10 && strlen($title) < 500) ? $title : null;
    }

    /**
     * Extracts the authors from the cover page text.
     */
    public function extractAuthors(string $text): ?string
    {
        $coverText = mb_substr($text, 0, 45000);
        $pattern = '/\b(?:Elaborado\s+por|Realizado\s+por|Presentado\s+por|Creado\s+por|Autor(?:es)?\s*:?|Brs?\b\.?|Bachiller(?:es)?\b\s*(?::|\r?\n)|Estudiante(?:s)?\b\s*(?::|\r?\n)|Integrante(?:s)?\b\s*(?::|\r?\n)|Alumno(?:s)?\b\s*(?::|\r?\n))\s*(?:Br\.\s+)?(.*?)(?:\b(?:Tutor(?:a)?(?:es)?|Prof|Ing|Dr|Dra|MSc|Lic|Abg)\b|\bEl\s+Valle\b|\n\s*(?-i:[A-ZÁÉÍÓÚÑ]{3,})\b|\n\s*CARTA\s+DE|\n\s*DEDICATORIA|\b\d{4}\b|\b(?:Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre)\b|$)/isu';

        if (preg_match_all($pattern, $coverText, $matches)) {
            foreach ($matches[1] as $match) {
                // Split multi-author column lists by title prefixes or newlines
                $parts = preg_split('/\s+(?=Br\b|Brs\b|Bachiller|Estudiante|Integrante|Alumno)/i', $match);
                $authorsList = [];
                foreach ($parts as $part) {
                    $lines = explode("\n", $part);
                    foreach ($lines as $line) {
                        $author = $this->normalizeName($line);
                        if (strlen($author) > 2 && strlen($author) < 100 && ! str_contains($author, '....') && $this->isValidName($author)) {
                            $authorsList[] = $author;
                        }
                    }
                }
                if (! empty($authorsList)) {
                    return implode(', ', $authorsList);
                }
            }
        }

        return null;
    }

    /**
     * Extracts the tutor from the cover page text.
     */
    public function extractTutor(string $text): ?string
    {
        $coverText = mb_substr($text, 0, 45000);

        // Pattern 1: Tutor/Prof/Asesor followed by name (Tutor: Ing. Rafael Millan)
        $patternAfter = '/\b(?:Tutor(?:a)?(?:es)?\b|Prof(?:\.)?(?:\s*\(a\))?|Asesor(?:a)?\b)\s*[-.:]?\s*(?:(?:\s+(?:Ing|Prof|Dr|Dra|MSc|Esp|Lic|Abg)(?:\.)?(?:a)?)+)?\s*[-.:]?\s*(.*?)(?:\r?\n|$|\bEl\s+Valle\b|\b(?:Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre)\b|\b\d{4}\b)/isu';

        if (preg_match_all($patternAfter, $coverText, $matches)) {
            foreach ($matches[1] as $match) {
                // Split multi-tutor column lists by title prefixes
                $parts = preg_split('/\s+(?=Ing\b|Prof\b|Dr\b|Dra\b|MSc\b|Esp\b|Lic\b|Abg\b)/i', $match);
                $tutorsList = [];
                foreach ($parts as $part) {
                    $lines = explode("\n", $part);
                    foreach ($lines as $line) {
                        $tutor = $this->normalizeName($line);
                        if (strlen($tutor) > 2 && strlen($tutor) < 100 && ! str_contains($tutor, '....') && $this->isValidName($tutor)) {
                            $tutorsList[] = $tutor;
                        }
                    }
                }
                if (! empty($tutorsList)) {
                    return implode(', ', $tutorsList);
                }
            }
        }

        // Pattern 2: Name followed by Tutor/Tutora (Valentina Martinez\nTUTOR)
        $patternBefore = '/(?:Ing\.|Prof\.|Dr\.|Dra\.|MSc\.|Lic\.|Abg\.)?\s*([A-ZÁÉÍÓÚÑ][a-zâêîôûàèìòùáéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-zâêîôûàèìòùáéíóúñ]+)+)(?:\s*\r?\n\s*|\s+)Tutor(?:a)?(?:es)?\b/iu';

        if (preg_match_all($patternBefore, $coverText, $matches)) {
            foreach ($matches[1] as $match) {
                $tutor = $this->normalizeName($match);
                if (strlen($tutor) > 2 && strlen($tutor) < 100 && ! str_contains($tutor, '....') && $this->isValidName($tutor)) {
                    return $tutor;
                }
            }
        }

        return null;
    }

    /**
     * Extracts the abstract from the document text.
     */
    public function extractAbstract(string $text): ?string
    {
        $resumenPattern = '/\b(?:R\s*E\s*S\s*[UÚ]\s*M\s*E\s*N|A\s*B\s*S\s*T\s*R\s*A\s*C\s*T)\b/iu';
        $keywordsPattern = '/(?:Palabras\s+[Cc]laves?|Descriptores):/iu';

        // 1. Page split strategy (cleanest, prevents leaks)
        if (str_contains($text, "\f")) {
            $pages = explode("\f", $text);
            foreach ($pages as $pageIdx => $page) {
                if (preg_match($keywordsPattern, $page)) {
                    if (preg_match_all($resumenPattern, $page, $matches, PREG_OFFSET_CAPTURE)) {
                        for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                            $match = $matches[0][$i];
                            $matchPos = $match[1];
                            $candidate = substr($page, $matchPos + strlen($match[0]));

                            if (! $this->isTableOfContents($candidate)) {
                                if (preg_match($keywordsPattern, $candidate, $kwMatch, PREG_OFFSET_CAPTURE)) {
                                    $abstract = substr($candidate, 0, $kwMatch[0][1]);
                                    $abstract = $this->cleanAbstract($abstract);
                                    if (strlen($abstract) > 50) {
                                        return $abstract;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Fallback for page split: what if RESUMEN was on the immediately preceding page?
            // ONLY if the preceding page doesn't look like a Table of Contents!
            foreach ($pages as $pageIdx => $page) {
                if (preg_match($keywordsPattern, $page) && $pageIdx > 0) {
                    $prevPage = $pages[$pageIdx - 1];
                    if (! $this->isTableOfContents($prevPage)) {
                        $combined = $prevPage."\n".$page;
                        if (preg_match_all($resumenPattern, $combined, $matches, PREG_OFFSET_CAPTURE)) {
                            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                                $match = $matches[0][$i];
                                $matchPos = $match[1];
                                $candidate = substr($combined, $matchPos + strlen($match[0]));

                                if (! $this->isTableOfContents($candidate)) {
                                    if (preg_match($keywordsPattern, $candidate, $kwMatch, PREG_OFFSET_CAPTURE)) {
                                        $abstract = substr($candidate, 0, $kwMatch[0][1]);
                                        $abstract = $this->cleanAbstract($abstract);
                                        if (strlen($abstract) > 50) {
                                            return $abstract;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // 2. Fallback: full text search within 45000 chars
        $bodyText = mb_substr($text, 0, 45000);
        if (preg_match($keywordsPattern, $bodyText, $kwMatches, PREG_OFFSET_CAPTURE)) {
            $kwPos = $kwMatches[0][1];
            $startPos = max(0, $kwPos - 5000);
            $preText = substr($bodyText, $startPos, $kwPos - $startPos);
            $preText = iconv('UTF-8', 'UTF-8//IGNORE', $preText);
            if (function_exists('mb_scrub')) {
                $preText = mb_scrub($preText, 'UTF-8');
            }

            if (preg_match_all($resumenPattern, $preText, $matches, PREG_OFFSET_CAPTURE)) {
                for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                    $match = $matches[0][$i];
                    $matchPos = $match[1];
                    $candidate = substr($preText, $matchPos + strlen($match[0]));

                    if (! $this->isTableOfContents($candidate)) {
                        $candidateClean = $this->cleanAbstract($candidate);
                        if (strlen($candidateClean) > 50) {
                            return $candidateClean;
                        }
                    }
                }
            }

            // If still not found, try search backwards from kwPos for any of the other headers (dates, authors)
            $fallbackPatterns = [
                '/\b(?:Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre),?\s*\d{4}\b/iu',
                '/Elaborado\s+por\b/iu',
                '/Autor(?:es)?\b/iu',
                '/Tutor(?:a)?(?:es)?\b/iu',
            ];

            $bestPos = -1;
            $bestLen = 0;
            foreach ($fallbackPatterns as $pattern) {
                if (preg_match_all($pattern, $preText, $matches, PREG_OFFSET_CAPTURE)) {
                    $lastMatch = end($matches[0]);
                    if ($lastMatch[1] > $bestPos) {
                        $bestPos = $lastMatch[1];
                        $bestLen = strlen($lastMatch[0]);
                    }
                }
            }

            if ($bestPos !== -1) {
                $candidate = substr($preText, $bestPos + $bestLen);
                $candidate = iconv('UTF-8', 'UTF-8//IGNORE', $candidate);
                if (function_exists('mb_scrub')) {
                    $candidate = mb_scrub($candidate, 'UTF-8');
                }
                if (! $this->isTableOfContents($candidate)) {
                    $abstract = $this->cleanAbstract($candidate);
                    if (strlen($abstract) > 50) {
                        return $abstract;
                    }
                }
            }
        }

        // 3. Fallback: If no keyword marker at all (like Jose Alberto Martinez)
        if (preg_match_all($resumenPattern, $bodyText, $matches, PREG_OFFSET_CAPTURE)) {
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $match = $matches[0][$i];
                $matchPos = $match[1];
                $sub = substr($bodyText, $matchPos + strlen($match[0]));
                $sub = iconv('UTF-8', 'UTF-8//IGNORE', $sub);
                if (function_exists('mb_scrub')) {
                    $sub = mb_scrub($sub, 'UTF-8');
                }

                if (! $this->isTableOfContents($sub)) {
                    // Search for next big heading
                    if (preg_match('/\b(?:CAP[IÍ]TULO\s+[IVX\d]+|INTRODUCCI[OÓ]N|PARTE\s+[IVX\d]+)\b/iu', $sub, $headingMatch, PREG_OFFSET_CAPTURE)) {
                        $abstract = substr($sub, 0, $headingMatch[0][1]);
                        $abstract = $this->cleanAbstract($abstract);
                        if (strlen($abstract) > 50) {
                            return $abstract;
                        }
                    } else {
                        // Grab next 2000 chars
                        $abstract = substr($sub, 0, 2000);
                        $abstract = $this->cleanAbstract($abstract);
                        if (strlen($abstract) > 50) {
                            return $abstract;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extracts keywords from the document text.
     */
    public function extractKeywords(string $text): ?string
    {
        $bodyText = mb_substr($text, 0, 45000);

        // Stop at double newline, form feed, or standard uppercase headings
        $pattern = '/(?:Palabras\s+[Cc]laves?|Descriptores):\s*(.*?)(?:\r?\n\r?\n|\f|\n\s*[A-ZÁÉÍÓÚÑ]{3,}(?:\s+[A-ZÁÉÍÓÚÑ]{3,})*\s*(?:\r?\n|$)|$)/isu';

        if (preg_match($pattern, $bodyText, $match)) {
            $kw = $match[1];
            $kw = preg_replace('/\s+/', ' ', $kw);
            $kw = trim($kw);

            $changed = true;
            while ($changed) {
                $changed = false;
                $old = $kw;

                // Match trailing numbers or roman numerals preceded by punctuation/space
                $kw = preg_replace('#[\s\-\.,;:\(\)\[\]/\\|]+(?:\d+|[ivxIVX]+)\s*$#u', '', $kw);
                $kw = trim($kw, " \t\n\r\0\x0B:-.,;?/\\|");

                if ($kw !== $old) {
                    $changed = true;
                }
            }

            if (strlen($kw) > 5 && strlen($kw) < 300) {
                return $kw;
            }
        }

        return null;
    }

    /**
     * Cleans metadata prefixes from the start of the abstract.
     */
    private function cleanAbstract(string $abstract): string
    {
        $abstract = trim($abstract);

        $changed = true;
        while ($changed) {
            $changed = false;

            // Strip leading "RESUMEN" or "ABSTRACT"
            $new = preg_replace('/^(?:RESUMEN|ABSTRACT)\s*:?/iu', '', $abstract);
            $new = trim($new);
            if ($new !== $abstract) {
                $abstract = $new;
                $changed = true;

                continue;
            }

            // Strip metadata block starting with title or headers up to author/tutor and ending with date/year or double newlines
            // E.g., "DISEÑO DE UN REPOSITORIO... Autor: Bárbara... Noviembre, 2023"
            $new = preg_replace('/^.*?\b(?:Autor(?:es)?|Tutor(?:a)?(?:es)?|Elaborado\s+por|Realizado\s+por|Brs?\b|Bachiller(?:es)?)\b.*?(?:\b\d{4}\b|(?:\r?\n){2,}|$)/isu', '', $abstract);
            $new = trim($new);

            // Strip metadata headers up to the date/year or up to the newline
            $new = preg_replace('/^(?:Autor|Tutor|Fecha|Br|Realizado|Elaborado|Realizado\s+por|Elaborado\s+por|Presentado\s+por|Tutores|Tutora|Docente|Asesor)\b.*?(?:\b\d{4}\b|(?:\r?\n)+|$)/iu', '', $new);
            $new = trim($new);

            // Strip leading dates like "Julio de 2025" or "noviembre de 2024"
            $months = '(?:Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre)';
            $new = preg_replace('/^(?:'.$months.')(?:\s+de)?\s+\d{4}\b.*?(?:(?:\r?\n)+|$)/iu', '', $new);
            $new = trim($new);

            if ($new !== $abstract) {
                $abstract = $new;
                $changed = true;
            }
        }

        $abstract = ltrim($abstract, " \t\n\r\0\x0B:-.,;“”\"'?");
        $abstract = rtrim($abstract, " \t\n\r\0\x0B“”\"'?");

        // Strip trailing page numbers/roman numerals or dot lines
        $abstract = preg_replace('/\b\d+\s*$/u', '', $abstract);
        $abstract = preg_replace('/\b[ivxIVX]+\s*$/u', '', $abstract);
        $abstract = trim($abstract, " \t\n\r\0\x0B:-.,;“”\"'?");

        return $abstract;
    }

    /**
     * Identifies if a block of text belongs to a Table of Contents (TOC) or Index.
     */
    private function isTableOfContents(string $text): bool
    {
        $text = substr($text, 0, 1000);

        // 1. If it contains consecutive dots (3 or more)
        if (preg_match('/\.{3,}/u', $text)) {
            return true;
        }

        // 2. If it contains the word INDICE / ÍNDICE as a standalone heading or line
        if (preg_match('/\b(?:[IÍ]NDICE(?: GENERAL)?|TABLA\s+DE\s+CONTENIDOS?)\b/iu', $text)) {
            return true;
        }
        if (preg_match('/(?:^|\n)\s*CONTENIDOS?\s*(?:\r?\n|$)/iu', $text)) {
            return true;
        }

        // 3. If it contains multiple CAPÍTULO listings (a clear sign of TOC)
        if (preg_match('/\bCAP[IÍ]TULO\s+I\b/iu', $text) && preg_match('/\bCAP[IÍ]TULO\s+II\b/iu', $text)) {
            return true;
        }

        // 4. If it has lines ending with numbers/roman numerals (like TOC page listings)
        $lines = explode("\n", substr($text, 0, 400));
        $numericLines = 0;
        $totalLines = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $totalLines++;
            if (preg_match('/(?:\b\d+|\b[ivxIVX]+)$/u', $line)) {
                $numericLines++;
            }
        }
        if ($totalLines >= 5 && ($numericLines / $totalLines) > 0.5) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a string is a valid capitalized proper name.
     */
    private function isValidName(string $name): bool
    {
        if ($name === '') {
            return false;
        }
        $firstChar = mb_substr($name, 0, 1);

        return mb_strtoupper($firstChar) === $firstChar && mb_strtolower($firstChar) !== $firstChar;
    }

    /**
     * Detects if the first page of the PDF contains ONLY "UNIVERSIDAD DE MARGARITA"
     * and removes it using Ghostscript if true.
     */
    public function removeExtraUnimarCoverPage(string $filePath): void
    {
        try {
            if (! file_exists($filePath) || filesize($filePath) === 0) {
                return;
            }

            // Only run on PDF files
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if ($extension !== 'pdf') {
                return;
            }

            // Extract text from page 1 only
            $page1Text = (new Pdf)
                ->setPdf($filePath)
                ->setOptions(['f 1', 'l 1'])
                ->text();

            $cleanText = trim(preg_replace('/\s+/', ' ', $page1Text));

            // Check if page 1 text is exactly "UNIVERSIDAD DE MARGARITA" (case-insensitive)
            if (strcasecmp($cleanText, 'UNIVERSIDAD DE MARGARITA') === 0) {
                $tempOutput = tempnam(sys_get_temp_dir(), 'pdf_clean_').'.pdf';

                // Use Ghostscript to copy from page 2 to the end
                $command = '/usr/bin/gs -sDEVICE=pdfwrite -dNOPAUSE -dBATCH -dSAFER -dFirstPage=2 -sOutputFile='.escapeshellarg($tempOutput).' '.escapeshellarg($filePath).' 2>&1';
                exec($command, $output, $resultCode);

                if ($resultCode === 0 && file_exists($tempOutput) && filesize($tempOutput) > 0) {
                    copy($tempOutput, $filePath);
                    @unlink($tempOutput);
                    Log::info('Successfully removed blank cover page from PDF: '.$filePath);
                } else {
                    Log::error('Ghostscript failed to remove first page: '.implode("\n", $output));
                }
            }
        } catch (\Exception $e) {
            Log::error('Error checking/removing first page of PDF: '.$e->getMessage());
        }
    }
}
