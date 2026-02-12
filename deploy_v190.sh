#!/bin/bash
###############################################################################
# SARH AL-ITQAN Enterprise v1.9.0 — Production Deployment Script
# Target: Hostinger Shared Hosting (sarh.online)
# PHP: /usr/local/bin/php8.3
# Project: /home/u850419603/sarh/
# Public:  ~/domains/sarh.online/public_html/
###############################################################################

set -euo pipefail

# ── Configuration ────────────────────────────────────────────────────────────
PHP="/usr/local/bin/php8.3"
PROJECT="/home/u850419603/sarh"
PUBLIC_HTML="$HOME/domains/sarh.online/public_html"
ARTISAN="$PHP $PROJECT/artisan"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG="$PROJECT/storage/logs/deploy_${TIMESTAMP}.log"

# ── Helper Functions ─────────────────────────────────────────────────────────
info()    { echo -e "\033[1;34m[INFO]\033[0m $1" | tee -a "$LOG"; }
success() { echo -e "\033[1;32m[OK]\033[0m   $1" | tee -a "$LOG"; }
warn()    { echo -e "\033[1;33m[WARN]\033[0m $1" | tee -a "$LOG"; }
fail()    { echo -e "\033[1;31m[FAIL]\033[0m $1" | tee -a "$LOG"; exit 1; }

echo "=============================================" | tee "$LOG"
echo " SARH v1.9.0 Deployment — $(date)"            | tee -a "$LOG"
echo "=============================================" | tee -a "$LOG"

# ──────────────────────────────────────────────────────────────────────────────
# STEP 1: Preflight Checks
# ──────────────────────────────────────────────────────────────────────────────
info "Step 1/9: Preflight checks..."

# Verify PHP 8.3 exists
if ! [ -x "$PHP" ]; then
    fail "PHP 8.3 not found at $PHP"
fi

PHP_VERSION=$($PHP -v | head -n1)
info "PHP: $PHP_VERSION"

# Verify required extensions
for ext in pdo_mysql mbstring openssl tokenizer xml ctype json bcmath fileinfo; do
    if $PHP -m 2>/dev/null | grep -qi "^${ext}$"; then
        success "Extension: $ext"
    else
        warn "Extension $ext not detected (may still work via shared module)"
    fi
done

# Verify project directory
if ! [ -d "$PROJECT" ]; then
    fail "Project directory not found: $PROJECT"
fi

cd "$PROJECT"
success "Working directory: $(pwd)"

# ──────────────────────────────────────────────────────────────────────────────
# STEP 2: Pull Latest Code
# ──────────────────────────────────────────────────────────────────────────────
info "Step 2/9: Pulling latest code from GitHub..."

if [ -d ".git" ]; then
    git pull origin main --ff-only 2>&1 | tee -a "$LOG" || warn "Git pull failed — continuing with current code"
    success "Git pull complete"
else
    warn "No .git directory — skipping pull (manual upload assumed)"
fi

# ──────────────────────────────────────────────────────────────────────────────
# STEP 3: Composer Install (Production)
# ──────────────────────────────────────────────────────────────────────────────
info "Step 3/9: Installing Composer dependencies..."

if [ -f "composer.json" ]; then
    $PHP $(which composer 2>/dev/null || echo "$HOME/bin/composer.phar") install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist \
        2>&1 | tee -a "$LOG"
    success "Composer install complete"
else
    fail "composer.json not found in $PROJECT"
fi

# ──────────────────────────────────────────────────────────────────────────────
# STEP 4: Environment Verification
# ──────────────────────────────────────────────────────────────────────────────
info "Step 4/9: Environment verification..."

if ! [ -f ".env" ]; then
    fail ".env file missing — copy .env.example and configure manually"
fi

# Verify APP_KEY exists
if grep -q "^APP_KEY=base64:" .env; then
    success "APP_KEY is set"
else
    warn "APP_KEY missing — generating..."
    $ARTISAN key:generate --force 2>&1 | tee -a "$LOG"
fi

# Verify critical .env values
if grep -q "^APP_ENV=production" .env; then
    success "APP_ENV=production"
else
    warn "APP_ENV is not 'production' — update .env manually"
fi

if grep -q "^APP_DEBUG=false" .env; then
    success "APP_DEBUG=false"
else
    warn "APP_DEBUG should be false in production — update .env"
fi

# Session security hardening check
if grep -q "^SESSION_SECURE_COOKIE=true" .env 2>/dev/null; then
    success "SESSION_SECURE_COOKIE=true"
else
    warn "Consider setting SESSION_SECURE_COOKIE=true for HTTPS"
fi

# ──────────────────────────────────────────────────────────────────────────────
# STEP 5: Database Migrations
# ──────────────────────────────────────────────────────────────────────────────
info "Step 5/9: Running database migrations..."

$ARTISAN migrate --force 2>&1 | tee -a "$LOG"
success "Migrations complete"

# ──────────────────────────────────────────────────────────────────────────────
# STEP 6: Frontend Assets
# ──────────────────────────────────────────────────────────────────────────────
info "Step 6/9: Frontend assets..."

if command -v npm &> /dev/null; then
    info "npm found — building assets..."
    npm ci --production=false 2>&1 | tee -a "$LOG"
    npm run build 2>&1 | tee -a "$LOG"
    success "Vite build complete"
elif [ -d "public/build" ]; then
    success "Pre-built assets found in public/build — skipping npm"
else
    warn "No npm and no pre-built assets — upload public/build manually"
fi

# ──────────────────────────────────────────────────────────────────────────────
# STEP 7: Storage Symlinks
# ──────────────────────────────────────────────────────────────────────────────
info "Step 7/9: Storage symlinks..."

# Project-level storage link
if [ -L "$PROJECT/public/storage" ]; then
    success "Project storage link exists"
else
    $ARTISAN storage:link 2>&1 | tee -a "$LOG"
    success "Project storage link created"
fi

# Domain-level storage link
if [ -L "$PUBLIC_HTML/storage" ]; then
    success "Domain storage link exists"
else
    ln -sf "$PROJECT/storage/app/public" "$PUBLIC_HTML/storage"
    success "Domain storage link created"
fi

# ──────────────────────────────────────────────────────────────────────────────
# STEP 8: Deploy to Domain public_html
# ──────────────────────────────────────────────────────────────────────────────
info "Step 8/9: Deploying to $PUBLIC_HTML..."

# Create public_html if it doesn't exist
mkdir -p "$PUBLIC_HTML"

# Copy built assets
if [ -d "$PROJECT/public/build" ]; then
    cp -rf "$PROJECT/public/build" "$PUBLIC_HTML/build"
    success "Build assets deployed"
fi

# Copy static assets
for asset_dir in css js images; do
    if [ -d "$PROJECT/public/$asset_dir" ]; then
        cp -rf "$PROJECT/public/$asset_dir" "$PUBLIC_HTML/$asset_dir"
        success "Copied public/$asset_dir"
    fi
done

# Copy robots.txt and favicon
for file in robots.txt favicon.ico; do
    if [ -f "$PROJECT/public/$file" ]; then
        cp -f "$PROJECT/public/$file" "$PUBLIC_HTML/$file"
    fi
done

# Deploy bridge index.php (absolute path resolution)
cat > "$PUBLIC_HTML/index.php" << 'BRIDGE_EOF'
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance mode check
if (file_exists($maintenance = '/home/u850419603/sarh/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader (absolute path)
require '/home/u850419603/sarh/vendor/autoload.php';

// Bootstrap Laravel and handle the request (absolute path)
(require_once '/home/u850419603/sarh/bootstrap/app.php')
    ->handleRequest(Request::capture());
BRIDGE_EOF

success "Bridge index.php deployed to $PUBLIC_HTML"

# Deploy .htaccess if it exists
if [ -f "$PROJECT/public/.htaccess" ]; then
    cp -f "$PROJECT/public/.htaccess" "$PUBLIC_HTML/.htaccess"
    success ".htaccess deployed"
fi

# ──────────────────────────────────────────────────────────────────────────────
# STEP 9: Cache Management & Permissions
# ──────────────────────────────────────────────────────────────────────────────
info "Step 9/9: Cache management & file permissions..."

# Clear all caches first
$ARTISAN optimize:clear 2>&1 | tee -a "$LOG"
success "All caches cleared"

# Rebuild safe caches only
# PROHIBITION: route:cache is forbidden (Filament v3 uses closure-based routes)
$ARTISAN config:cache 2>&1 | tee -a "$LOG"
success "Config cached"

$ARTISAN event:cache 2>&1 | tee -a "$LOG"
success "Events cached"

$ARTISAN view:cache 2>&1 | tee -a "$LOG"
success "Views cached"

$ARTISAN icons:cache 2>&1 | tee -a "$LOG" || warn "Icons cache command not available"

$ARTISAN filament:cache-components 2>&1 | tee -a "$LOG" || warn "Filament cache command not available"

# File permissions
chmod -R 775 "$PROJECT/storage" 2>/dev/null || warn "Could not set storage permissions"
chmod -R 775 "$PROJECT/bootstrap/cache" 2>/dev/null || warn "Could not set bootstrap/cache permissions"
chmod 600 "$PROJECT/.env" 2>/dev/null || warn "Could not set .env permissions"
success "File permissions set (storage: 775, .env: 600)"

# ──────────────────────────────────────────────────────────────────────────────
# POST-DEPLOY VERIFICATION
# ──────────────────────────────────────────────────────────────────────────────
echo "" | tee -a "$LOG"
echo "=============================================" | tee -a "$LOG"
echo " POST-DEPLOY VERIFICATION"                     | tee -a "$LOG"
echo "=============================================" | tee -a "$LOG"

CHECKS_PASSED=0
CHECKS_TOTAL=0

verify() {
    CHECKS_TOTAL=$((CHECKS_TOTAL + 1))
    if eval "$2"; then
        success "CHECK $CHECKS_TOTAL: $1"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        warn "CHECK $CHECKS_TOTAL: $1 — FAILED"
    fi
}

verify "Artisan responds"           "$ARTISAN --version > /dev/null 2>&1"
verify ".env exists"                "[ -f $PROJECT/.env ]"
verify "vendor/autoload.php exists" "[ -f $PROJECT/vendor/autoload.php ]"
verify "public/build exists"        "[ -d $PUBLIC_HTML/build ]"
verify "Bridge index.php exists"    "[ -f $PUBLIC_HTML/index.php ]"
verify "Storage link (project)"     "[ -L $PROJECT/public/storage ]"
verify "Storage link (domain)"      "[ -L $PUBLIC_HTML/storage ]"
verify "Config is cached"           "[ -f $PROJECT/bootstrap/cache/config.php ]"

echo "" | tee -a "$LOG"
echo "=============================================" | tee -a "$LOG"
echo " RESULT: $CHECKS_PASSED/$CHECKS_TOTAL checks passed" | tee -a "$LOG"
echo " Log: $LOG"                                    | tee -a "$LOG"
echo "=============================================" | tee -a "$LOG"

if [ "$CHECKS_PASSED" -eq "$CHECKS_TOTAL" ]; then
    success "SARH v1.9.0 deployed successfully to sarh.online"
else
    warn "Deployment completed with warnings — review log"
fi

echo ""
echo "Next steps:"
echo "  1. Visit https://sarh.online to verify the site loads"
echo "  2. Visit https://sarh.online/admin to verify the admin panel"
echo "  3. Run: $ARTISAN sarh:install  (if fresh install — creates Super Admin)"
echo ""
