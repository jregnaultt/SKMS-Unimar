<?php

namespace App\Services\Parsers;

/**
 * Class ToonParser
 *
 * Parses Token-Oriented Object Notation (TOON) string to associative array.
 */
class ToonParser
{
    /**
     * Parses a TOON string into a structured metadata array.
     *
     * @param  string  $toonText  The raw TOON format text from LLM.
     * @return array{title: ?string, authors: ?string, tutor: ?string, abstract: ?string, keywords: ?string}
     */
    public function parse(string $toonText): array
    {
        $lines = explode("\n", $toonText);
        $result = [
            'title' => null,
            'authors' => null,
            'tutor' => null,
            'abstract' => null,
            'keywords' => null,
        ];

        $currentKey = null;
        $currentValue = '';

        foreach ($lines as $line) {
            $matched = false;
            foreach (['title', 'authors', 'tutor', 'abstract', 'keywords'] as $key) {
                // Match key: at the start of a line
                if (preg_match('/^'.$key.':\s*(.*)$/i', $line, $matches)) {
                    if ($currentKey !== null) {
                        $result[$currentKey] = trim($currentValue);
                    }
                    $currentKey = $key;
                    $currentValue = $matches[1];
                    $matched = true;
                    break;
                }
            }

            if (! $matched && $currentKey !== null) {
                // Accumulate multi-line values (like abstracts)
                $currentValue .= "\n".$line;
            }
        }

        if ($currentKey !== null) {
            $result[$currentKey] = trim($currentValue);
        }

        return $result;
    }
}
