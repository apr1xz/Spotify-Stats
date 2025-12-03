<?php

class JsonStreamer
{
    /**
     * Generator that yields decoded JSON objects from a large file containing a JSON array.
     * 
     * @param string $filePath
     * @return Generator
     */
    public static function streamJsonItems($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        $buffer = '';
        $braceLevel = 0;
        $inString = false;
        $isEscaped = false;
        $itemBuffer = '';
        $started = false;

        while (!feof($handle)) {
            $char = fgetc($handle);
            if ($char === false)
                break;

            if (!$started) {
                if ($char === '[') {
                    $started = true;
                }
                continue;
            }

            // Simple state machine to track object boundaries
            if ($inString) {
                if ($isEscaped) {
                    $isEscaped = false;
                } elseif ($char === '\\') {
                    $isEscaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
            } else {
                if ($char === '"') {
                    $inString = true;
                } elseif ($char === '{') {
                    $braceLevel++;
                } elseif ($char === '}') {
                    $braceLevel--;
                } elseif ($char === ']') {
                    // End of array
                    if ($braceLevel === 0) {
                        break;
                    }
                }
            }

            if ($braceLevel > 0 || ($braceLevel === 0 && $char === '}')) {
                $itemBuffer .= $char;
            }

            // When we hit the closing brace of the top-level object
            if ($braceLevel === 0 && $char === '}' && !empty($itemBuffer)) {
                $json = json_decode($itemBuffer, true);
                if ($json !== null) {
                    yield $json;
                }
                $itemBuffer = '';
            }
        }

        fclose($handle);
    }
}
