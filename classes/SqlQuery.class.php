<?php

/**
 * A SQL statement plus its bound parameters.
 *
 * Legacy code used to build queries by concatenating user input into SQL strings. Those call sites
 * now build a SqlQuery instead:
 *
 *     $sql = SqlQuery::make('SELECT id FROM users WHERE login = ? AND gameIP = ?', [$login, SqlQuery::num($ip)]);
 *     $row = $this->pdo->query($sql)->fetch(PDO::FETCH_OBJ);
 *
 * GamePDO::query(), exec() and prepare() accept a SqlQuery and run it as a prepared statement.
 *
 * Parameters are bound as strings unless wrapped in SqlQuery::num(), which binds integers as integers
 * (required for LIMIT/OFFSET, and keeps numeric comparisons numeric).
 *
 * Only values may be parameters. Identifiers (table/column names, sort direction) that are still
 * concatenated into the template must come from a whitelist, never from user input.
 */
final class SqlQuery {

    /** @var string */
    public $sql;

    /** @var array */
    public $params;

    public function __construct(string $sql, array $params = []) {
        $this->sql = $sql;
        $this->params = array_values($params);
    }

    public static function make(string $sql, array $params = []): SqlQuery {
        return new SqlQuery($sql, $params);
    }

    /** Marks a parameter that appeared in a numeric (unquoted) position. */
    public static function num($value): SqlNumber {
        return new SqlNumber($value);
    }

    /** Concatenates strings and/or SqlQuery objects, keeping parameters in order. */
    public static function concat(...$parts): SqlQuery {
        $sql = '';
        $params = [];
        foreach ($parts as $part) {
            if ($part instanceof SqlQuery) {
                $sql .= $part->sql;
                array_push($params, ...$part->params);
            } else {
                $sql .= (string) $part;
            }
        }
        return new SqlQuery($sql, $params);
    }

    public function bindTo(PDOStatement $stmt): void {
        foreach ($this->params as $i => $value) {
            [$v, $type] = self::normalize($value);
            $stmt->bindValue($i + 1, $v, $type);
        }
    }

    private static function normalize($value): array {
        if ($value instanceof SqlNumber) {
            $v = $value->value;
            if (is_bool($v)) {
                return [(int) $v, PDO::PARAM_INT];
            }
            if ($v === null) {
                return [null, PDO::PARAM_NULL];
            }
            if (is_int($v) || (is_string($v) && filter_var($v, FILTER_VALIDATE_INT) !== false)) {
                return [(int) $v, PDO::PARAM_INT];
            }
            // floats and anything else: bound as a string, MySQL converts it in numeric context
            return [(string) $v, PDO::PARAM_STR];
        }
        // Quoted-string position: the legacy code interpolated the value's string form.
        if ($value === null || $value === false) {
            return ['', PDO::PARAM_STR];
        }
        return [(string) $value, PDO::PARAM_STR];
    }

    public function __toString(): string {
        // Converting to a string would silently drop the bound parameters.
        throw new LogicException('SqlQuery cannot be used as a string; pass it to GamePDO::query()/exec()/prepare() or SqlQuery::concat().');
    }

}

final class SqlNumber {

    public $value;

    public function __construct($value) {
        $this->value = $value;
    }

}
