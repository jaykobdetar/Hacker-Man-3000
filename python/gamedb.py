"""Shared helpers for the game's Python scripts (python/ and cron2/).

Configuration comes from environment variables or the .env file in the repository root, the same
settings the PHP code uses (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD, DB_SOCKET).
"""

import os
import subprocess
import sys

BASE_PATH = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


def _load_env():
    env_file = os.path.join(BASE_PATH, '.env')
    if not os.path.isfile(env_file):
        return
    with open(env_file, encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            key, value = line.split('=', 1)
            key = key.strip()
            if key.startswith('export '):
                key = key[len('export '):].strip()
            value = value.strip()
            if len(value) >= 2 and value[0] == value[-1] and value[0] in '"\'':
                value = value[1:-1]
            # real environment variables win over .env, like in the PHP code
            os.environ.setdefault(key, value)


_load_env()


def config(key, default=None):
    value = os.environ.get(key)
    return default if value is None or value == '' else value


def connect():
    """Opens a connection to the game database."""
    import pymysql

    password = config('DB_PASSWORD')
    if password is None:
        sys.exit('DB_PASSWORD is not configured (see .env.example)')

    options = dict(
        user=config('DB_USER', 'he'),
        password=password,
        database=config('DB_NAME', 'game'),
        charset=config('DB_CHARSET', 'utf8mb4'),
        init_command="SET SESSION sql_mode = '%s'" % config('DB_SQL_MODE', 'NO_ENGINE_SUBSTITUTION').replace("'", ''),
    )
    if config('DB_SOCKET'):
        options['unix_socket'] = config('DB_SOCKET')
    else:
        options['host'] = config('DB_HOST', '127.0.0.1')
        options['port'] = int(config('DB_PORT', '3306'))
    return pymysql.connect(**options)


def path(*parts):
    """Absolute path inside the repository, e.g. path('html/profile/')."""
    return os.path.join(BASE_PATH, *parts)


def run_script(script, *args):
    """Runs python/<script> (or cron2/<script> when given as 'cron2/x.py') with the current
    interpreter; arguments are passed directly, no shell is involved."""
    script_path = path(script) if '/' in script else path('python', script)
    subprocess.run([sys.executable, script_path] + [str(a) for a in args], check=False)


def write_file(file_path, content):
    """Writes a generated HTML page (UTF-8), creating the directory if needed."""
    os.makedirs(os.path.dirname(file_path), exist_ok=True)
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)


def set_locale(name):
    """locale.setlocale() that falls back to the C locale when `name` is not installed."""
    import locale
    try:
        locale.setlocale(locale.LC_ALL, name)
    except locale.Error:
        try:
            locale.setlocale(locale.LC_ALL, name + '.UTF-8')
        except locale.Error:
            locale.setlocale(locale.LC_ALL, 'C')
