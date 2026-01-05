#!/usr/bin/env bash
set -euo pipefail

SITE_HOST="${1:-damncute.local}"
DOCROOT="${2:-/home/allie/Projects/damncute}"
HTTPD_CONF="/etc/httpd/conf/httpd.conf"
VHOSTS_CONF="/etc/httpd/conf/extra/httpd-vhosts.conf"
DB_HOST="127.0.0.1"
DB_USER="cute_db_user"
DB_PASS="V*ocKI31"
DB_NAME="cute_wb_db"
ADMIN_USER="allie"
ADMIN_EMAIL="allie@alliehere.com"
ADMIN_PASS="vojecoki1"

echo "Configuring Apache vhost for ${SITE_HOST} -> ${DOCROOT}"

sudo sed -i 's/^#Include conf\/extra\/httpd-vhosts.conf/Include conf\/extra\/httpd-vhosts.conf/' "$HTTPD_CONF"
sudo sed -i 's/^#LoadModule rewrite_module/LoadModule rewrite_module/' "$HTTPD_CONF"

sudo env SITE_HOST="$SITE_HOST" DOCROOT="$DOCROOT" VHOSTS_CONF="$VHOSTS_CONF" python - <<'PY'
import os
import re
from pathlib import Path

host = os.environ["SITE_HOST"]
docroot = os.environ["DOCROOT"]
path = Path(os.environ["VHOSTS_CONF"])
text = path.read_text()

def strip_vhost(content, server_name):
    pattern = re.compile(
        r"<VirtualHost\\s+\\*:80>.*?ServerName\\s+%s.*?</VirtualHost>\\n?" % re.escape(server_name),
        re.S,
    )
    return pattern.sub("", content)

for name in ("dummy-host.example.com", "dummy-host2.example.com", host):
    text = strip_vhost(text, name)

block = f"""
<VirtualHost *:80>
  ServerName {host}
  DocumentRoot "{docroot}"
  <Directory "{docroot}">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
  </Directory>
  ErrorLog "/var/log/httpd/{host}-error.log"
  CustomLog "/var/log/httpd/{host}-access.log" common
</VirtualHost>
"""

parts = text.split("<VirtualHost", 1)
header = parts[0].rstrip()
rest = ""
if len(parts) > 1:
    rest = "<VirtualHost" + parts[1].lstrip()

new_content = header + "\n\n" + block.lstrip() + "\n"
if rest.strip():
    new_content += rest.strip() + "\n"

path.write_text(new_content)
PY

sudo python - <<'PY'
from pathlib import Path

path = Path("/etc/hosts")
text = path.read_text()
if text and not text.endswith("\n"):
    path.write_text(text + "\n")
PY

if ! sudo grep -q "${SITE_HOST}" /etc/hosts; then
  echo "127.0.0.1 ${SITE_HOST}" | sudo tee -a /etc/hosts >/dev/null
fi

sudo systemctl restart httpd

echo "Granting admin access for ${ADMIN_USER}"
mariadb --protocol=tcp -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" -e "
INSERT INTO wp_users (user_login, user_pass, user_nicename, user_email, user_registered, user_status, display_name)
VALUES ('${ADMIN_USER}', MD5('${ADMIN_PASS}'), '${ADMIN_USER}', '${ADMIN_EMAIL}', NOW(), 0, '${ADMIN_USER}')
ON DUPLICATE KEY UPDATE user_pass=VALUES(user_pass), user_email=VALUES(user_email), display_name=VALUES(display_name);
SET @uid := (SELECT ID FROM wp_users WHERE user_login='${ADMIN_USER}' LIMIT 1);
REPLACE INTO wp_usermeta (user_id, meta_key, meta_value)
VALUES
(@uid, 'wp_capabilities', 'a:1:{s:13:\"administrator\";b:1;}'),
(@uid, 'wp_user_level', '10');
"

echo "Setting wp-content permissions for Apache"
sudo chgrp -R http "${DOCROOT}/wp-content"
sudo chmod -R g+rwX "${DOCROOT}/wp-content"
sudo find "${DOCROOT}/wp-content" -type d -exec chmod g+s {} \;

echo "Done. Open: http://${SITE_HOST}/wp-admin"
