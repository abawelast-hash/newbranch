#!/bin/bash

# ================================
# نشر سريع على sarh.online
# ================================

set -e

SERVER="u307296675@194.164.74.250"
PORT="65002"
PROJECT_PATH="/home/u307296675/sarh"

echo "🚀 بدء النشر..."

# 1. Commit وPush محلياً
echo ""
echo "📦 Commit & Push..."
git add -A
git commit -m "deploy: Quick deployment $(date +%Y-%m-%d_%H:%M:%S)" || echo "لا توجد تغييرات للـ commit"
git push newrepo main

# 2. تحديث السيرفر
echo ""
echo "🌐 تحديث السيرفر..."
ssh -p $PORT $SERVER "cd $PROJECT_PATH && \
    git fetch origin main && \
    git reset --hard origin/main && \
    php artisan migrate --force && \
    php artisan optimize:clear && \
    php artisan optimize"

echo ""
echo "✅ النشر مكتمل!"
echo "   🔗 https://sarh.io"
