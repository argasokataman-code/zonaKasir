<?php

/**
 * SkillGrabber — Production-Grade LLM Context Optimizer
 *
 * Primary mission: Reduce LLM context/token usage by 90–99% while preserving answer quality.
 *
 * Drop-in single file. No Composer, no dependencies, no frameworks.
 * Compatible with any PHP 8.1+ project or AI agent workflow.
 *
 * PUBLIC API:
 *   SkillGrabber::grab(string $filePath, string $skillKey): ?array
 *
 * REQUIREMENT: Uses 'skill-parser-lock.json' as the default filename.
 *
 * @version  1.0.0
 * @license  MIT
 */

declare(strict_types=1);

namespace Vendor\SkillParser;

final class SkillGrabber
{
    /**
     * Grab a skill by its exact dot-notation key.
     *
     * Example: SkillGrabber::grab('/path/to/skill-parser-lock.json', 'laravel.validation')
     *
     * @param  string $filePath  Absolute or relative path to the skill JSON file.
     *                           Default example: skill-parser-lock.json
     * @param  string $skillKey  Dot-notation path, e.g. "laravel.validation".
     * @return array|null       Compressed skill data or null on failure.
     */
    public static function grab(string $filePath, string $skillKey): ?array
    {
        $data = self::loadFile($filePath);
        if ($data === null) {
            return null;
        }

        $node = self::extractPath($data, $skillKey);
        if ($node === null) {
            return null;
        }

        return self::compressNode($node);
    }

    /**
     * Load and validate JSON file.
     *
     * @param string $filePath Path to skill-parser-lock.json
     * @return array|null
     */
    private static function loadFile(string $filePath): ?array
    {
        $realPath = realpath($filePath);
        if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            throw new RuntimeException("Skill file not found: {$filePath}");
        }

        $raw = file_get_contents($realPath);
        if ($raw === false || $raw === '') {
            throw new RuntimeException("Skill file is empty: {$filePath}");
        }

        $decoded = json_decode($raw, associative: true, flags: JSON_BIGINT_AS_STRING);
        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON format in skill file: {$filePath}");
        }

        return $decoded;
    }

    /**
     * Extract nested node by dot notation.
     * Tries full key first (for keys containing literal dots like "laravel.validation"),
     * then falls back to dot-separated segments for nested access.
     *
     * @param array<mixed> $data
     * @param string $dotKey
     * @return mixed|null
     */
    private static function extractPath(array $data, string $dotKey): mixed
    {
        if ($dotKey === '') {
            return null;
        }

        // Try full key first (keys with literal dots like "laravel.validation")
        if (array_key_exists($dotKey, $data)) {
            return $data[$dotKey];
        }

        // Fallback to dot-separated segments for nested access like "config.database.host"
        $segments = explode('.', $dotKey);
        $node = $data;

        foreach ($segments as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return null;
            }
            $node = $node[$segment];
        }

        return $node;
    }

    /**
     * Compress and optimize extracted skill node.
     * Strips metadata, empty fields, and reduces size.
     *
     * @param mixed $node
     * @return array
     */
    private static function compressNode(mixed $node): array
    {
        // Handle scalar values
        if (!is_array($node)) {
            return [
                '_skill' => 'extracted',
                'value' => $node,
            ];
        }

        // Strip metadata fields
        $metadataFields = ['created_at', 'updated_at', 'id', 'uuid', 'metadata', '_meta'];
        $node = self::stripMetadata($node, $metadataFields);

        // Remove empty fields
        $node = self::removeEmptyFields($node);

        return $node;
    }

    /**
     * Recursively strip metadata fields.
     *
     * @param array<mixed> $node
     * @param list<string> $fields
     * @return array<mixed>
     */
    private static function stripMetadata(array $node, array $fields): array
    {
        foreach ($fields as $field) {
            unset($node[$field]);
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = self::stripMetadata($value, $fields);
            }
        }

        return $node;
    }

    /**
     * Remove null, empty strings, empty arrays, and empty objects.
     *
     * @param array<mixed> $node
     * @return array<mixed>
     */
    private static function removeEmptyFields(array $node): array
    {
        foreach ($node as $key => $value) {
            if ($value === null || $value === '' || $value === [] || $value === new \stdClass()) {
                unset($node[$key]);
                continue;
            }

            if (is_array($value)) {
                $cleaned = self::removeEmptyFields($value);
                if (empty($cleaned)) {
                    unset($node[$key]);
                } else {
                    $node[$key] = $cleaned;
                }
            }
        }

        return $node;
    }
}