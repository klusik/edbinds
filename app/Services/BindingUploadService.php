<?php

namespace App\Services;

use App\Core\Config;

/**
 * Validates uploaded .binds files and prepares database payloads.
 */
final class BindingUploadService
{
    private BindParser $parser;

    /**
     * Create the upload service.
     */
    public function __construct()
    {
        $this->parser = new BindParser();
    }

    /**
     * Parse an uploaded file and build version fields.
     *
     * @param array<string,mixed> $file PHP upload array.
     * @param string $changeNote User change note.
     * @return array<string,mixed> Version payload.
     */
    public function prepareUploadedVersion(array $file, string $changeNote = ''): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Upload failed with PHP error code ' . (string)($file['error'] ?? 'unknown') . '.');
        }

        $size = (int)($file['size'] ?? 0);
        $limit = Config::getInt('app.upload_max_bytes', 1048576);
        if ($size <= 0 || $size > $limit) {
            throw new \InvalidArgumentException('Upload size must be between 1 byte and ' . $limit . ' bytes.');
        }

        $originalName = basename((string)($file['name'] ?? 'bindings.binds'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['binds', 'xml'], true)) {
            throw new \InvalidArgumentException('Upload a .binds or .xml file.');
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        $xml = file_get_contents($tmpName);
        if ($xml === false) {
            throw new \InvalidArgumentException('Could not read the uploaded file.');
        }

        $parsed = $this->parser->parseString($xml, $originalName);
        $parsedJson = json_encode($parsed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $statsJson = json_encode($parsed['stats'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $normalizedJson = $this->parser->normalizedJson($parsed);

        return [
            'original_filename' => $originalName,
            'file_hash' => hash('sha256', $xml),
            'normalized_hash' => hash('sha256', $normalizedJson),
            'xml_text' => $xml,
            'parsed_json' => $parsedJson,
            'stats_json' => $statsJson,
            'change_note' => $changeNote,
            'parsed' => $parsed,
        ];
    }
}
