<?php

/*
 * Router for PHP's built-in web server, for local development and the CI smoke test only:
 *
 *     php -S 127.0.0.1:8080 scripts/dev-router.php
 *
 * It mirrors the rules in .htaccess: internal files are not served and pages work without
 * the .php extension. Use Apache (see Dockerfile) or nginx in production.
 */

$root = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

$blocked = '#^/(classes|cron|cron2|python|scripts|template|info|json|locale|vendor|mail_templates|wiki|backups|docker|docs|tests)(/|$)#';
if (preg_match($blocked, $path) || preg_match('#(^|/)\.#', $path)
    || preg_match('#\.(sql|sql\.gz|po|mo|py|sh|dia|md|lock|ini|log|example|yml|yaml)$#i', $path)
    || preg_match('#^/images/.*\.(php|phtml|phar|pht|php\d)$#i', $path)
    || in_array($path, ['/composer.json', '/crontab', '/Dockerfile', '/LICENSE'], true)) {
    http_response_code(404);
    echo 'Not found';
    return true;
}

$file = realpath($root . $path);
if ($file !== false && is_dir($file)) {
    $file = is_file($file . '/index.php') ? $file . '/index.php' : false;
} elseif ($file === false && is_file($root . $path . '.php')) {
    $file = realpath($root . $path . '.php');
}

if ($file === false || strpos($file, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
    http_response_code(404);
    echo 'Not found';
    return true;
}

if (substr($file, -4) !== '.php') {
    return false; // static file, served by the built-in server
}

$_SERVER['SCRIPT_FILENAME'] = $file;
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = substr($file, strlen($root));
chdir(dirname($file));
require $file;
return true;
