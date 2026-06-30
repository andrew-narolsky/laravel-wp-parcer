<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SplFileObject;

class CsvReader
{
    /**
     * @return \Generator<array<string, string>>
     */
    public static function rows(string $storagePath): \Generator
    {
        $fullPath = Storage::path($storagePath);

        if (!file_exists($fullPath)) {
            Log::error("CsvReader: file not found: {$fullPath}");
            return;
        }

        $file = new SplFileObject($fullPath);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $header = null;

        foreach ($file as $row) {
            if ($header === null) {
                $header = array_map('trim', $row);
                continue;
            }

            if (!is_array($row) || count($row) !== count($header)) {
                continue;
            }

            yield array_combine($header, $row);
        }
    }
}
