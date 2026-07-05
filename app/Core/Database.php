<?php

namespace App\Core;

use PDO;

/**
 * Shared PDO connection factory.
 */
final class Database
{
    private static ?PDO $connection = null;

    /**
     * Return the configured MariaDB connection.
     *
     * @return PDO Active PDO connection.
     */
    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            Config::getString('db.host'),
            Config::getInt('db.port', 3306),
            Config::getString('db.name'),
            Config::getString('db.charset', 'utf8mb4')
        );

        self::$connection = new PDO($dsn, Config::getString('db.user'), Config::getString('db.password'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }
}
