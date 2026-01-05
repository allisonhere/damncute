#!/usr/bin/env bash
set -euo pipefail

VHOSTS_CONF="/etc/httpd/conf/extra/httpd-vhosts.conf"
DOCROOT="/home/allie/Projects/damncute"
HOSTNAME="damncute.test"

sudo cp "${VHOSTS_CONF}" "${VHOSTS_CONF}.bak"
sudo sh -c "cat <<'EOF' > \"${VHOSTS_CONF}\"
# Auto-managed vhost for local dev
<VirtualHost *:80>
  ServerName ${HOSTNAME}
  DocumentRoot \"${DOCROOT}\"
  <Directory \"${DOCROOT}\">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
  </Directory>
  ErrorLog \"/var/log/httpd/${HOSTNAME}-error.log\"
  CustomLog \"/var/log/httpd/${HOSTNAME}-access.log\" common
</VirtualHost>
EOF"

sudo systemctl restart httpd

echo "Fixed vhosts and restarted Apache."
