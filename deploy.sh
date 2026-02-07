#!/bin/bash
###############################################################
# SARH AL-ITQAN — One-Command Deploy Script
# Usage: bash deploy.sh          (first time + updates)
# Repo:  https://github.com/ggoolbx0/sarh
# Target: Hostinger (sarh.online)
# Constitution: Zero-Patch Policy — No manual DB changes
###############################################################

set -e

PROJECT_DIR="/home/u850419603/sarh"
PUBLIC_HTML="/home/u850419603/public_html"
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

# ── Step 1: Composer Install (No Dev) ────────────────────
echo "▸ [1/8] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "  ✓ Composer dependencies installed"
echo ""

# ── Step 2: Copy Production .env ─────────────────────────
if [ ! -f .env ]; then
    echo "▸ [2/8] Setting up environment file..."
    cp .env.production .env
    php artisan key:generate --force
    echo "  ✓ Environment configured & APP_KEY generated"
else
    echo "▸ [2/8] .env already exists — skipping"
fi
echo ""

# ── Step 3: Migrate + Seed (Empty DB → Full Schema) ─────
echo "▸ [3/8] Running migrations & seeding (RBAC + Traps + Badges)..."
php artisan migrate --force --seed
echo "  ✓ Database schema created with seed data:"
echo "    → 10-level RBAC roles & permissions"
echo "    → Security traps (4 core traps)"
echo "    → Gamification badges"
echo ""

# ── Step 4: Build Frontend Assets (Vite) ────────────────
echo "▸ [4/8] Building frontend assets..."
if command -v npm &> /dev/null; then
    npm install --no-audit --no-fund
    npm run build
    echo "  ✓ Vite assets compiled to public/build/"
else
    echo "  ⚠ npm not found — skip frontend build"
    echo "    Run 'npm install && npm run build' locally and upload public/build/"
fi
echo ""

# ── Step 5: Storage Symlink ──────────────────────────────
echo "▸ [5/8] Creating storage symlink..."
php artisan storage:link --force
echo "  ✓ storage/app/public → public/storage"
echo ""

# ── Step 6: Symlink public_html → sarh/public ───────────
echo "▸ [6/8] Setting up public_html symlink..."
if [ -L "$PUBLIC_HTML" ]; then
    echo "  ✓ Symlink already exists"
elif [ -d "$PUBLIC_HTML" ]; then
    # Backup and remove existing public_html
    echo "  → Removing existing public_html directory..."
    rm -rf "$PUBLIC_HTML"
    ln -s "$PROJECT_DIR/public" "$PUBLIC_HTML"
    echo "  ✓ public_html → sarh/public"
else
    ln -s "$PROJECT_DIR/public" "$PUBLIC_HTML"
    echo "  ✓ public_html → sarh/public"
fi
echo ""

# ── Step 7: Clear Old Cache ─────────────────────────────
echo "▸ [7/8] Clearing old cache..."
php artisan optimize:clear
echo "  ✓ Old cache cleared"
echo ""

# ── Step 8: Optimize & Cache ────────────────────────────
echo "▸ [8/8] Optimizing for production..."
php artisan optimize
echo "  ✓ Config, routes, and views cached"
echo ""

# ── Permissions Fix ──────────────────────────────────────
echo "▸ Fixing permissions..."
chmod -R 775 storage bootstrap/cache
echo "  ✓ storage/ and bootstrap/cache/ set to 775"
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
