<?php

/**
 * Runs the helper scripts in python/ (badges, profile pages, fame/ranking generators).
 * Every argument is shell-escaped; the interpreter can be changed with PYTHON_BIN.
 */
class Python {

    public function add_badge($userID, $badgeID, $clan = ''){

        $userBadge = ($clan == '') ? 'user' : 'clan';
        self::run('badge_add.py', [$userBadge, $userID, $badgeID], 15);

    }

    public function generateProfile($id, $l = 'en'){

        self::run('profile_generator.py', [$id, $l], 20);

    }

    /** Runs python/<script> with the given arguments (used by the cron jobs as well). */
    public static function run($script, array $args = [], $queries = 0){

        if (!preg_match('/^[A-Za-z0-9_]+\.py$/', $script)) {
            throw new InvalidArgumentException('Invalid script name');
        }

        $python = Config::get('PYTHON_BIN', 'python3');
        $cmd = escapeshellcmd($python) . ' ' . escapeshellarg(BASE_PATH . '/python/' . $script);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg((string) $arg);
        }

        $log = Config::get('PYTHON_LOG', '');
        $cmd .= $log !== '' ? ' >> ' . escapeshellarg($log) . ' 2>&1' : ' > /dev/null 2>&1';

        exec($cmd);

        if ($queries > 0) {
            exec(escapeshellcmd($python) . ' ' . escapeshellarg(BASE_PATH . '/python/query_counter.py') . ' ' . (int) $queries . ' > /dev/null 2>&1');
        }

    }

}
