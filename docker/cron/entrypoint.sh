#!/bin/sh
# Runs the game's scheduled jobs (see /var/www/html/crontab) as www-data and streams their
# output to the container log.
set -eu

GAME=/var/www/html
LOG=/var/log/hackerexperience/cron.log

mkdir -p "$(dirname "$LOG")"
touch "$LOG"
chown www-data:www-data "$LOG"

# cron does not pass the container environment to jobs, so hand it over through .env
# (the file only exists inside this container).
if [ ! -f "$GAME/.env" ]; then
    env | grep -E '^(APP_|DB_|MAIL_|SESSION_|TRUST_PROXY_HEADERS|FORUM_URL|WIKI_URL|BTC_PRICE_URL|PYTHON_|BACKUP_)' \
        | sed -E 's/^([^=]+)=(.*)$/\1="\2"/' > "$GAME/.env"
    chown root:www-data "$GAME/.env"
    chmod 640 "$GAME/.env"
fi

sed -e "s#^GAME=.*#GAME=$GAME#" -e "s#^LOG=.*#LOG=$LOG#" "$GAME/crontab" | crontab -u www-data -

cron
exec tail -F "$LOG"
