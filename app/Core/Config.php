<?php

namespace App\Core;

/**
 * Immutable runtime configuration registry.
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $values = [];

    /**
     * Load a PHP config file returning an array.
     *
     * @param string $file Absolute path to the config file.
     * @return void
     */
    public static function load(string $file): void
    {
        $values = require $file;
        if (!is_array($values)) {
            throw new \RuntimeException('Configuration file must return an array.');
        }

        self::$values = $values;
    }

    /**
     * Get a configuration value by dot notation.
     *
     * @param string $key Dot notation key.
     * @param mixed $default Default value when the key is missing.
     * @return mixed Configured value or default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$values;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Get a string configuration value.
     *
     * @param string $key Dot notation key.
     * @param string $default Default value.
     * @return string String value.
     */
    public static function getString(string $key, string $default = ''): string
    {
        return (string)self::get($key, $default);
    }

    /**
     * Get an integer configuration value.
     *
     * @param string $key Dot notation key.
     * @param int $default Default value.
     * @return int Integer value.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        return (int)self::get($key, $default);
    }

    /**
     * Get a boolean configuration value.
     *
     * @param string $key Dot notation key.
     * @param bool $default Default value.
     * @return bool Boolean value.
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        return (bool)self::get($key, $default);
    }
}
