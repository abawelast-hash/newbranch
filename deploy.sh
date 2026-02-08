#!/bin/bash
###############################################################
# SARH AL-ITQAN — One-Command Deploy Script
# Usage: bash deploy.sh          (first time + updates)
# Repo:  https://github.com/ggoolbx0/sarh
# Target: Hostinger (sarh.online)
# Path:   domains/sarh.online/public_html (NOT ~/public_html)
# Constitution: Zero-Patch Policy — No manual DB changes
###############################################################

set -e

PROJECT_DIR="/home/u850419603/sarh"
DOMAIN_PUBLIC="/home/u850419603/domains/sarh.online/public_html"
REPO_URL="https://github.com/ggoolbx0/sarh.git"

echo ""
echo "╔═══════════════════════════════════════════╗"
echo "║   SARH AL-ITQAN — Production Deployment  ║"
echo "║   sarh.online                             ║"
echo "╚═══════════════════════════════════════════╝"
echo ""

# ── Step 0: Clone or Pull from GitHub ────────────────────
if [ ! -d "$PROJECT_DIR/.git" ]; then
    echo "▸ [0/8] First deploy — cloning from GitHub..."
    git clone "$REPO_URL" "$PROJECT_DIR"
    echo "  ✓ Repository cloned"
else
    echo "▸ [0/8] Pulling latest from GitHub..."
    cd "$PROJECT_DIR"
    git fetch origin
    git reset --hard origin/main
    echo "  ✓ Code updated to latest main"
fi
echo ""

cd "$PROJECT_DIR"

# ── Step 1: Composer Install (WITH dev — views need it) ──
echo "▸ [1/8] Installing PHP dependencies..."
composer install --optimize-autoloader --no-interaction
echo "  ✓ Composer dependencies installed"
echo ""

# ── Step 2: Copy Production .env ─────────────────────────
if [ ! -f .env ]; then
    echo "▸ [2/8] Setting up environment file..."
    cp .env.production .env
    php artisan key:generate --force
    echo "  ✓ Environment configured & APP_KEY generated"
else
    echo "▸ [2/8] .env exists — ensuring APP_KEY is set..."
    if ! grep -q "^APP_KEY=base64:" .env; then
        php artisan key:generate --force
        echo "  ✓ APP_KEY generated"
    else
        echo "  ✓ APP_KEY already set"
    fi
fi
echo ""

# ── Step 2.5: Enforce Hardened Session Protocol ──────────
# SARH Constitution: Session config MUST NOT be overwritten by git pull.
# Force file-based sessions, no encryption, null domain.
echo "▸ [2.5] Enforcing Hardened Session Protocol..."
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env
sed -i 's/^SESSION_ENCRYPT=.*/SESSION_ENCRYPT=false/' .env
sed -i 's/^SESSION_DOMAIN=.*/SESSION_DOMAIN=null/' .env
# Ensure keys exist if missing
grep -q "^SESSION_DRIVER=" .env    || echo "SESSION_DRIVER=file" >> .env
grep -q "^SESSION_ENCRYPT=" .env   || echo "SESSION_ENCRYPT=false" >> .env
grep -q "^SESSION_DOMAIN=" .env    || echo "SESSION_DOMAIN=null" >> .env
grep -q "^SESSION_SECURE_COOKIE=" .env || echo "SESSION_SECURE_COOKIE=true" >> .env
grep -q "^SESSION_LIFETIME=" .env  || echo "SESSION_LIFETIME=120" >> .env
# Force secure cookie value
sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env
echo "  ✓ SESSION_DRIVER=file"
echo "  ✓ SESSION_ENCRYPT=false"
echo "  ✓ SESSION_DOMAIN=null (auto-detect)"
echo "  ✓ SESSION_SECURE_COOKIE=true (HTTPS)"
echo ""

# ── Step 3: Migrate + Seed (Empty DB → Full Schema) ─────
echo "▸ [3/8] Running migrations & seeding (RBAC + Traps + Badges)..."
php artisan migrate --force --seed
echo "  ✓ Database schema created with seed data"
echo ""

# ── Step 4: Build Frontend Assets (Vite) ────────────────
echo "▸ [4/8] Building frontend assets..."
if command -v npm &> /dev/null; then
    npm install --no-audit --no-fund
    npm run build
    echo "  ✓ Vite assets compiled to public/build/"
else
    echo "  ⚠ npm not found — using pre-built assets from git"
fi
echo ""

# ── Step 5: Storage Symlink ─────────────────────────────
echo "▸ [5/8] Creating storage symlink..."
mkdir -p "$PROJECT_DIR/storage/app/public"
if [ -L "$PROJECT_DIR/public/storage" ]; then
    echo "  ✓ Symlink already exists"
else
    ln -sf "$PROJECT_DIR/storage/app/public" "$PROJECT_DIR/public/storage"
    echo "  ✓ storage symlink created"
fi
echo ""

# ── Step 6: Deploy to domains/sarh.online/public_html ───
echo "▸ [6/8] Deploying to domain public_html..."
mkdir -p "$DOMAIN_PUBLIC"

# Copy all public assets (JS, CSS, build, images, etc.)
cp -r "$PROJECT_DIR/public/"* "$DOMAIN_PUBLIC/"
cp "$PROJECT_DIR/public/.htaccess" "$DOMAIN_PUBLIC/" 2>/dev/null || true

# *** CRITICAL: Overwrite index.php with bridge (absolute paths) ***
# The cp above copies the default Laravel index.php with __DIR__ paths
# which point to domains/sarh.online/ (wrong). We MUST overwrite it.
cat > "$DOMAIN_PUBLIC/index.php" << 'BRIDGE'
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance mode
if (file_exists($maintenance = '/home/u850419603/sarh/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader — absolute path to project
require '/home/u850419603/sarh/vendor/autoload.php';

// Bootstrap & handle request — absolute path to project
(require_once '/home/u850419603/sarh/bootstrap/app.php')
    ->handleRequest(Request::capture());
BRIDGE

# Storage link in domain public_html
ln -sf "$PROJECT_DIR/storage/app/public" "$DOMAIN_PUBLIC/storage"

# Permissions
chmod 755 "$DOMAIN_PUBLIC"
chmod 644 "$DOMAIN_PUBLIC/index.php"
chmod 644 "$DOMAIN_PUBLIC/.htaccess" 2>/dev/null || true

echo "  ✓ Files deployed to $DOMAIN_PUBLIC"
echo "  ✓ Bridge index.php with absolute paths created"
echo ""

# ── Step 7: Clear Old Cache + Stale Sessions ────────────
echo "▸ [7/8] Clearing old cache + stale sessions..."
php artisan optimize:clear
rm -f storage/framework/sessions/* 2>/dev/null || true
echo "  ✓ Old cache cleared"
echo "  ✓ Stale session files purged"
echo ""

# ── Step 8: Cache config & routes (skip views — causes error) ─
echo "▸ [8/8] Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan event:cache
echo "  ✓ Config, routes, events cached (views NOT cached — intentional)"
echo ""

# ── Permissions Fix ──────────────────────────────────────
echo "▸ Fixing permissions..."
chmod -R 775 storage bootstrap/cache
echo "  ✓ Permissions set"
echo ""

# ── Done ─────────────────────────────────────────────────
echo "╔═══════════════════════════════════════════╗"
echo "║   ✅ DEPLOYMENT COMPLETE                  ║"
echo "║                                           ║"
echo "║   URL:   https://sarh.online              ║"
echo "║   Admin: https://sarh.online/admin        ║"
echo "║                                           ║"
echo "║   Next: Run 'php artisan sarh:install'    ║"
echo "║   to create the Super Admin (Level 10)    ║"
echo "╚═══════════════════════════════════════════╝"
echo ""
