#!/usr/bin/env bash
# =============================================================================
# install-hooks.sh — تثبيت Git Hooks لمشروع SARH
# =============================================================================
# الاستخدام: bash hooks/install-hooks.sh
# =============================================================================

set -euo pipefail

HOOKS_DIR="$(git rev-parse --git-dir)/hooks"
SOURCE_DIR="$(dirname "$0")"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BOLD='\033[1m'
RESET='\033[0m'

echo -e "\n${BOLD}تثبيت SARH Git Hooks...${RESET}\n"

# نسخ pre-commit hook
cp "$SOURCE_DIR/pre-commit" "$HOOKS_DIR/pre-commit"
chmod +x "$HOOKS_DIR/pre-commit"
echo -e "  ${GREEN}✓ تم تثبيت pre-commit${RESET}"

echo -e "\n${GREEN}${BOLD}✓ تم التثبيت بنجاح${RESET}"
echo -e "  الـ hook سيمنع رفع البيانات الحساسة تلقائياً عند كل commit\n"
