#!/usr/bin/env bash
#
# Git-based Docker deployment script for Backup Manager
#
# Philosophy:
#   - You push code to Git (GitHub, etc.)
#   - This script SSHes to the server and does `git pull` (or reset)
#   - Then rebuilds the Docker images and restarts the services
#
# Prerequisites:
#   - Passwordless SSH (key-based) from your machine to the server
#   - Git repository already cloned on the server (or provide GIT_REPO_URL for first deploy)
#   - Production .env file already present on the SERVER (never committed)
#   - docker + docker compose installed on the server
#
# Usage:
#   REMOTE_USER=ubuntu \
#   REMOTE_HOST=backup.example.com \
#   REMOTE_PATH=/opt/backup-manager \
#   ./scripts/deploy.sh
#
# With branch:
#   DEPLOY_BRANCH=main ./scripts/deploy.sh
#
# Flags:
#   --dry-run      Preview commands only
#   --no-build     Skip docker compose build
#   --no-migrate   Skip artisan migrate
#   --force-reset  Use git reset --hard (stronger than pull)

set -euo pipefail

# ---------------- CONFIGURATION ----------------
REMOTE_USER="${REMOTE_USER:-}"
REMOTE_HOST="${REMOTE_HOST:-}"
REMOTE_PATH="${REMOTE_PATH:-}"

DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
GIT_REPO_URL="${GIT_REPO_URL:-}"          # Optional: used only for initial clone

# Data path for volumes (you use /var/www/backup with Portainer)
DATA_PATH="${DATA_PATH:-/var/www/backup}"

COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"

DRY_RUN=false
DO_BUILD=true
DO_MIGRATE=true
FORCE_RESET=false

# If you manage the stack mainly through Portainer UI, set this to true.
# The script will still do git pull + build, but will skip the final "up" and give Portainer instructions.
PORTAINER_MODE="${PORTAINER_MODE:-false}"

# ---------------- ARGUMENT PARSING ----------------
while [[ $# -gt 0 ]]; do
  case $1 in
    --dry-run)     DRY_RUN=true; shift ;;
    --no-build)    DO_BUILD=false; shift ;;
    --no-migrate)  DO_MIGRATE=false; shift ;;
    --force-reset) FORCE_RESET=true; shift ;;
    -h|--help)
      echo "Usage: REMOTE_USER=... REMOTE_HOST=... REMOTE_PATH=... [options] $0"
      echo ""
      echo "Common variables:"
      echo "  DATA_PATH=/var/www/backup     # your persistent volumes location"
      echo "  PORTAINER_MODE=true           # skip 'up', redeploy from Portainer UI"
      echo ""
      echo "Options:"
      echo "  --dry-run      Show commands without executing"
      echo "  --no-build     Do not rebuild Docker images"
      echo "  --no-migrate   Skip database migrations"
      echo "  --force-reset  Force git reset --hard origin/<branch>"
      exit 0
      ;;
    *) echo "Unknown option: $1"; exit 1 ;;
  esac
done

# ---------------- VALIDATION ----------------
if [[ -z "$REMOTE_USER" || -z "$REMOTE_HOST" || -z "$REMOTE_PATH" ]]; then
  echo "ERROR: Missing required variables."
  echo ""
  echo "Example:"
  echo "  REMOTE_USER=ubuntu REMOTE_HOST=192.168.1.50 REMOTE_PATH=/opt/backup-manager ./scripts/deploy.sh"
  exit 1
fi

REMOTE="${REMOTE_USER}@${REMOTE_HOST}"
SSH_CMD="ssh -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15 ${REMOTE}"

echo "==> Deploy target : ${REMOTE}:${REMOTE_PATH}"
echo "==> Branch        : ${DEPLOY_BRANCH}"
echo "==> Data path     : ${DATA_PATH}"
echo "==> Compose files : ${COMPOSE_FILES}"
echo ""

run_remote() {
  local cmd="$*"
  if $DRY_RUN; then
    echo "[DRY] ssh ${REMOTE} 'cd ${REMOTE_PATH} && ${cmd}'"
  else
    echo "→ ${cmd}"
    $SSH_CMD "cd ${REMOTE_PATH} && ${cmd}"
  fi
}

# ---------------- SSH CHECK ----------------
echo "==> Checking SSH connection..."
if ! $DRY_RUN; then
  if ! $SSH_CMD "echo 'SSH OK'" >/dev/null; then
    echo "ERROR: Cannot SSH to ${REMOTE}"
    echo "Test manually: ssh ${REMOTE} 'echo connected'"
    exit 1
  fi
  echo "SSH OK"
else
  echo "[DRY] Would test SSH"
fi

# ---------------- PREPARE DIRECTORY ----------------
run_remote "mkdir -p ${REMOTE_PATH}"

# ---------------- GIT OPERATIONS ON SERVER ----------------
echo ""
echo "==> Updating code from git (branch: ${DEPLOY_BRANCH})..."

# Check if this is the first deploy (no .git yet)
if $DRY_RUN; then
  echo "[DRY] Would check if ${REMOTE_PATH}/.git exists"
else
  HAS_GIT=$($SSH_CMD "test -d ${REMOTE_PATH}/.git && echo 'yes' || echo 'no'")
fi

if [[ "${HAS_GIT:-no}" == "no" ]]; then
  if [[ -n "$GIT_REPO_URL" ]]; then
    echo "==> No git repo found. Cloning..."
    run_remote "git clone --branch ${DEPLOY_BRANCH} ${GIT_REPO_URL} ."
  else
    echo "ERROR: Directory ${REMOTE_PATH} is not a git repository."
    echo ""
    echo "First time setup on server (run this manually once):"
    echo "  ssh ${REMOTE} 'git clone git@github.com:yourname/backup-manager.git ${REMOTE_PATH}'"
    echo "  # or with https if you prefer"
    echo "Then create the production .env on the server and run the deploy script again."
    exit 1
  fi
else
  # Normal update path
  if $FORCE_RESET; then
    run_remote "git fetch origin"
    run_remote "git checkout ${DEPLOY_BRANCH}"
    run_remote "git reset --hard origin/${DEPLOY_BRANCH}"
    run_remote "git clean -fd --exclude='.env' --exclude='storage' --exclude='database'"
  else
    run_remote "git fetch origin"
    run_remote "git checkout ${DEPLOY_BRANCH}"
    run_remote "git pull --ff-only origin ${DEPLOY_BRANCH}"
  fi
fi

# Show current commit for traceability
run_remote "echo 'Deployed commit:' && git rev-parse --short HEAD && git log -1 --pretty=format:'%h %s (%cr)' --abbrev-commit"

# ---------------- DOCKER DEPLOY ----------------
echo ""
echo "==> Rebuilding and restarting Docker services..."

# We pass DATA_PATH so the prod override uses /var/www/backup for volumes
COMPOSE_ENV="DATA_PATH=${DATA_PATH}"

if $DO_BUILD; then
  run_remote "${COMPOSE_ENV} docker compose ${COMPOSE_FILES} build --pull"
else
  echo "(skipping build)"
fi

if [[ "$PORTAINER_MODE" == "true" ]]; then
  echo ""
  echo "==> PORTAINER_MODE active: skipping 'docker compose up'"
  echo "    You will redeploy the stack from the Portainer interface."
else
  run_remote "${COMPOSE_ENV} docker compose ${COMPOSE_FILES} up -d --remove-orphans"
fi

# ---------------- POST-DEPLOY TASKS ----------------
if $DO_MIGRATE; then
  echo ""
  echo "==> Running migrations..."
  run_remote "${COMPOSE_ENV} docker compose ${COMPOSE_FILES} exec -T php-fpm php artisan migrate --force --no-interaction"
else
  echo "(skipping migrations)"
fi

echo ""
echo "==> Optimizing Laravel caches..."
run_remote "${COMPOSE_ENV} docker compose ${COMPOSE_FILES} exec -T php-fpm php artisan config:cache || true"
run_remote "${COMPOSE_ENV} docker compose ${COMPOSE_FILES} exec -T php-fpm php artisan route:cache || true"
run_remote "${COMPOSE_ENV} docker compose ${COMPOSE_FILES} exec -T php-fpm php artisan view:cache || true"

if [[ "$PORTAINER_MODE" != "true" ]]; then
  echo ""
  echo "==> Container status:"
  run_remote "${COMPOSE_ENV} docker compose ${COMPOSE_FILES} ps"
fi

echo ""
echo "✅ Git pull + build completed."

if [[ "$PORTAINER_MODE" == "true" ]]; then
  echo ""
  echo "➡️  Next step (recommended with Portainer):"
  echo "   1. Open Portainer"
  echo "   2. Go to Stacks → select your backup-manager stack"
  echo "   3. Click 'Redeploy the stack' (or 'Update the stack')"
else
  echo ""
  echo "Useful commands:"
  echo "  ssh ${REMOTE} 'cd ${REMOTE_PATH} && DATA_PATH=${DATA_PATH} docker compose ${COMPOSE_FILES} logs -f --tail=80'"
fi

echo "  ssh ${REMOTE} 'cd ${REMOTE_PATH} && git log --oneline -5'"
