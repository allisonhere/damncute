#!/usr/bin/env bash
set -euo pipefail

THEME_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REMOTE_USER="allieher"
REMOTE_HOST="alliehere.com"
REMOTE_PORT="1157"
REMOTE_PATH="/home/allieher/www/damncute/wp-content/themes/damncute"
SSH_KEY_PATH="${SSH_KEY_PATH:-}"
SSH_OPTS="-o BatchMode=yes -o IdentitiesOnly=yes"

EXCLUDES=(
  "--exclude=.git/"
  "--exclude=.github/"
  "--exclude=scripts/"
  "--exclude=debug.log"
  "--exclude=php-error.log"
  "--exclude=.DS_Store"
)

RSYNC_BASE=(
  rsync -avz
  --delete
  --itemize-changes
  "${EXCLUDES[@]}"
)

if [[ -n "${SSH_KEY_PATH}" ]]; then
  RSYNC_BASE+=( -e "ssh ${SSH_OPTS} -i ${SSH_KEY_PATH} -p ${REMOTE_PORT}" )
else
  RSYNC_BASE+=( -e "ssh ${SSH_OPTS} -p ${REMOTE_PORT}" )
fi

echo "DamnCute Theme Deploy"
echo "======================"
echo "Source : ${THEME_ROOT}"
echo "Target : ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}"
echo

read -r -p "Choose: [1] dry-run  [2] publish  [x] quit: " ACTION
case "${ACTION}" in
  1)
    echo
    echo "Mode   : dry run"
    "${RSYNC_BASE[@]}" --dry-run "${THEME_ROOT}/" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/"
    echo
    ;;
  2)
    echo
    echo "Mode   : live"
    "${RSYNC_BASE[@]}" "${THEME_ROOT}/" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/"
    echo
    echo "Deploy complete."
    ;;
  x|X)
    echo "Aborted."
    ;;
  *)
    echo "Unknown option."
    exit 1
    ;;
esac
