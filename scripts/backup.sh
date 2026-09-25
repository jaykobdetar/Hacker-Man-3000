#!/usr/bin/env bash
# Dumps the game database to BACKUP_DIR and, if BACKUP_S3_URI is set, uploads the dump with the
# AWS CLI (credentials come from the usual AWS_* environment variables or instance role).
#
# Reads DB_* / BACKUP_* settings from the environment or from the .env file next to this repo.
# Replaces the old cron/backup_game.php, which embedded credentials and the AWS SDK v2.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
if [ -f "$ROOT/.env" ]; then
    set -a
    # shellcheck disable=SC1091
    . "$ROOT/.env"
    set +a
fi

: "${DB_NAME:=game}"
: "${DB_USER:=he}"
: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=3306}"
: "${BACKUP_DIR:=$ROOT/backups}"
: "${BACKUP_KEEP_DAYS:=14}"

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"
file="$BACKUP_DIR/$(date -u +%Y%m%d-%H%M)_${DB_NAME}.sql.gz"

# Pass the password through the environment instead of the command line (visible in `ps`).
MYSQL_PWD="${DB_PASSWORD:?DB_PASSWORD is not set}" mysqldump \
    --single-transaction --quick --routines \
    -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" | gzip > "$file"
chmod 600 "$file"

if [ -n "${BACKUP_S3_URI:-}" ]; then
    aws s3 cp "$file" "${BACKUP_S3_URI%/}/$(date -u +%Y/%m/%d)/$(basename "$file")"
fi

find "$BACKUP_DIR" -name "*_${DB_NAME}.sql.gz" -mtime "+$BACKUP_KEEP_DAYS" -delete
