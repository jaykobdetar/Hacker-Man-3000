<?php

require_once __DIR__ . '/../bootstrap.php';

/**
 * PDO connection that also accepts SqlQuery objects, which are executed as prepared statements.
 */
class GamePDO extends PDO {

    #[\ReturnTypeWillChange]
    public function query($query, ?int $fetchMode = null, ...$fetchModeArgs) {
        if ($query instanceof SqlQuery) {
            $stmt = parent::prepare($query->sql);
            $query->bindTo($stmt);
            $stmt->execute();
            if ($fetchMode !== null) {
                $stmt->setFetchMode($fetchMode, ...$fetchModeArgs);
            }
            return $stmt;
        }
        return $fetchMode === null ? parent::query($query) : parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    #[\ReturnTypeWillChange]
    public function exec($statement) {
        if ($statement instanceof SqlQuery) {
            return $this->query($statement)->rowCount();
        }
        return parent::exec($statement);
    }

    #[\ReturnTypeWillChange]
    public function prepare($query, array $options = []) {
        if ($query instanceof SqlQuery) {
            $stmt = parent::prepare($query->sql, $options);
            $query->bindTo($stmt);
            return $stmt;
        }
        return parent::prepare($query, $options);
    }

}

class PDO_DB {

    private static $dbh = null;

    /** Returns the shared database connection (one per request). */
    public static function factory() {
        if (self::$dbh === null) {
            self::$dbh = self::connect();
        }
        return self::$dbh;
    }

    private static function connect(): GamePDO {
        $dsn = Config::get('DB_DSN');
        if (!$dsn) {
            $charset = Config::get('DB_CHARSET', 'utf8mb4');
            $socket = Config::get('DB_SOCKET');
            $dsn = $socket
                ? 'mysql:unix_socket=' . $socket
                : 'mysql:host=' . Config::get('DB_HOST', '127.0.0.1') . ';port=' . Config::get('DB_PORT', '3306');
            $dsn .= ';dbname=' . Config::get('DB_NAME', 'game') . ';charset=' . $charset;
        }
        $pdo = new GamePDO($dsn, Config::get('DB_USER', 'he'), Config::require('DB_PASSWORD'), [
            PDO::ATTR_CASE => PDO::CASE_LOWER,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        // The game was written for MySQL 5.x's permissive defaults (e.g. '' for numeric columns).
        $pdo->exec('SET SESSION sql_mode = ' . $pdo->quote(Config::get('DB_SQL_MODE', 'NO_ENGINE_SUBSTITUTION')));
        return $pdo;
    }

}
