#!/usr/bin/env bash
# =============================================================================
# secure_cleanup.sh — SARH Project Sensitive-Data Sanitizer
# =============================================================================
# الاستخدام:
#   bash secure_cleanup.sh             ← وضع المعاينة (لا يغيّر أي شيء)
#   bash secure_cleanup.sh --fix       ← تطبيق الاستبدال الفعلي
#   bash secure_cleanup.sh --fix --report-only   ← فقط تقرير بدون استبدال
# =============================================================================

set -euo pipefail

# ─── ألوان الطرفية ────────────────────────────────────────────────────────────
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

# ─── إعدادات قابلة للتخصيص ───────────────────────────────────────────────────
PROJECT_ROOT="${1:-$(pwd)}"
REPORT_FILE="$(pwd)/security_scan_report_$(date +%Y%m%d_%H%M%S).txt"
DRY_RUN=true
REPORT_ONLY=false

# معالجة المعاملات
for arg in "$@"; do
    case "$arg" in
        --fix)         DRY_RUN=false ;;
        --report-only) REPORT_ONLY=true ;;
        --help)
            echo "الاستخدام: $0 [PROJECT_ROOT] [--fix] [--report-only]"
            exit 0
            ;;
    esac
done

# ─── إحصاءات ─────────────────────────────────────────────────────────────────
declare -A STATS
STATS[files_scanned]=0
STATS[files_flagged]=0
STATS[values_redacted]=0
STATS[files_modified]=0

# ─── أنماط البيانات الحساسة ──────────────────────────────────────────────────
# كل نمط بالصيغة: "وصف:regex"
PATTERNS=(
    # كلمات مرور
    "PLAIN_PASSWORD:password\s*=\s*['\"][^'\"]{4,}['\"]"
    "PLAIN_PASSWORD:passwd\s*=\s*['\"][^'\"]{4,}['\"]"
    "PLAIN_PASSWORD:pwd\s*=\s*['\"][^'\"]{4,}['\"]"
    "ENV_PASSWORD:DB_PASSWORD\s*=\s*.+"
    "ENV_PASSWORD:REDIS_PASSWORD\s*=\s*.+"
    "ENV_PASSWORD:MAIL_PASSWORD\s*=\s*.+"

    # مفاتيح API وتوكنات
    "API_TOKEN:api_key\s*=\s*['\"][a-zA-Z0-9_\-]{16,}['\"]"
    "API_TOKEN:API_KEY\s*=\s*.{8,}"
    "API_TOKEN:SECRET_KEY\s*=\s*.{8,}"
    "API_TOKEN:STRIPE_KEY\s*=\s*sk_[a-z]+_[a-zA-Z0-9]+"
    "API_TOKEN:PUSHER_APP_SECRET\s*=\s*.+"

    # مفاتيح SSH
    "SSH_KEY:-----BEGIN (RSA|EC|OPENSSH|DSA) PRIVATE KEY-----"
    "SSH_KEY:ssh-rsa AAAA[0-9A-Za-z+/]+"

    # بيانات اتصال قاعدة البيانات
    "DB_CREDENTIALS:DB_USERNAME\s*=\s*.+"
    "DB_CREDENTIALS:DB_DATABASE\s*=\s*.+"
    "DB_CREDENTIALS:DB_HOST\s*=\s*.{4,}"
    "DB_CREDENTIALS:mysql://[^@]+:[^@]+@"
    "DB_CREDENTIALS:postgresql://[^@]+:[^@]+@"

    # مفاتيح التطبيق
    "APP_KEY:APP_KEY\s*=\s*base64:.+"
    "APP_KEY:APP_KEY\s*=\s*[A-Za-z0-9+/]{32,}"

    # بيانات حساسة أخرى
    "PRIVATE_KEY:private_key\s*=\s*.{8,}"
    "SECRET:client_secret\s*=\s*.{8,}"
    "SECRET:OAUTH_SECRET\s*=\s*.{8,}"
    "IP_ADDRESS:[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}"
)

# ─── ملفات/مسارات يجب استثناؤها من الفحص ────────────────────────────────────
EXCLUDE_PATHS=(
    "vendor/"
    "node_modules/"
    ".git/"
    "public/build/"
    "storage/framework/"
    "*.min.js"
    "*.min.css"
    "bootstrap/cache/"
)

# ─── ملفات .env خاصة — استبدال القيم فقط لا الحذف ───────────────────────────
ENV_SAFE_KEYS=(
    "APP_NAME"
    "APP_ENV"
    "APP_TIMEZONE"
    "APP_LOCALE"
    "APP_FALLBACK_LOCALE"
    "APP_FAKER_LOCALE"
    "APP_MAINTENANCE_DRIVER"
    "APP_DEBUG"
    "BROADCAST_CONNECTION"
    "FILESYSTEM_DISK"
    "QUEUE_CONNECTION"
    "SESSION_DRIVER"
    "SESSION_LIFETIME"
    "LOG_CHANNEL"
    "LOG_LEVEL"
    "CACHE_DRIVER"
    "CACHE_PREFIX"
    "MAIL_MAILER"
    "MAIL_PORT"
    "MAIL_ENCRYPTION"
    "MAIL_FROM_ADDRESS"
    "MAIL_FROM_NAME"
    "VITE_APP_NAME"
    "DB_CONNECTION"
    "DB_PORT"
)

# =============================================================================
# دوال مساعدة
# =============================================================================

log_info()    { echo -e "${CYAN}[INFO]${RESET}  $*"; }
log_warn()    { echo -e "${YELLOW}[WARN]${RESET}  $*"; }
log_error()   { echo -e "${RED}[ERROR]${RESET} $*"; }
log_success() { echo -e "${GREEN}[OK]${RESET}    $*"; }

# طباعة في التقرير والشاشة معاً
report() { echo "$*" | tee -a "$REPORT_FILE" > /dev/null; }

# هل المسار مستثنى؟
is_excluded() {
    local filepath="$1"
    for pattern in "${EXCLUDE_PATHS[@]}"; do
        if [[ "$filepath" == *"$pattern"* ]]; then
            return 0
        fi
    done
    return 1
}

# =============================================================================
# الدالة الرئيسية: فحص ملف واحد
# =============================================================================

scan_file() {
    local filepath="$1"
    local relative_path="${filepath#$PROJECT_ROOT/}"
    local found_issues=()
    local line_count=0

    STATS[files_scanned]=$((STATS[files_scanned] + 1))

    while IFS= read -r line; do
        ((line_count++))
        for pattern_def in "${PATTERNS[@]}"; do
            local label="${pattern_def%%:*}"
            local regex="${pattern_def#*:}"

            if echo "$line" | grep -qiE "$regex" 2>/dev/null; then
                found_issues+=("  سطر $line_count [$label]: $(echo "$line" | sed 's/./*/g' | cut -c1-60)...")
            fi
        done
    done < "$filepath"

    if [[ ${#found_issues[@]} -gt 0 ]]; then
        STATS[files_flagged]=$((STATS[files_flagged] + 1))
        echo -e "${RED}⚠${RESET}  $relative_path"
        for issue in "${found_issues[@]}"; do
            echo -e "   ${YELLOW}$issue${RESET}"
        done
        report ""
        report "FILE: $relative_path"
        for issue in "${found_issues[@]}"; do
            report "  $issue"
        done
    fi
}

# =============================================================================
# استبدال القيم الحساسة في ملف .env
# =============================================================================

redact_env_file() {
    local filepath="$1"
    local relative_path="${filepath#$PROJECT_ROOT/}"
    local tmp_file="${filepath}.tmp.$$"
    local changes=0

    while IFS= read -r line; do
        # تجاوز الأسطر الفارغة والتعليقات
        if [[ -z "$line" || "$line" =~ ^[[:space:]]*# ]]; then
            echo "$line"
            continue
        fi

        # استخرج المفتاح والقيمة
        if [[ "$line" =~ ^([A-Z_]+)= ]]; then
            local key="${BASH_REMATCH[1]}"
            local is_safe=false

            # هل المفتاح آمن (لا يحتوي بيانات حساسة)؟
            for safe_key in "${ENV_SAFE_KEYS[@]}"; do
                if [[ "$key" == "$safe_key" ]]; then
                    is_safe=true
                    break
                fi
            done

            if [[ "$is_safe" == true ]]; then
                echo "$line"
            else
                # استبدل القيمة بـ REDACTED
                local new_line="${key}=REDACTED"
                echo "$new_line"
                ((changes++)) || true
                STATS[values_redacted]=$((STATS[values_redacted] + 1))
            fi
        else
            echo "$line"
        fi
    done < "$filepath" > "$tmp_file"

    if [[ $changes -gt 0 ]]; then
        if [[ "$DRY_RUN" == false && "$REPORT_ONLY" == false ]]; then
            mv "$tmp_file" "$filepath"
            log_success "تم تنقية $relative_path ($changes قيمة)"
            STATS[files_modified]=$((STATS[files_modified] + 1))
        else
            rm -f "$tmp_file"
            log_warn "[DRY-RUN] سيتم تنقية $relative_path ($changes قيمة)"
        fi
    else
        rm -f "$tmp_file"
    fi
}

# =============================================================================
# استبدال القيم الحساسة في ملفات .md / .txt / .json
# =============================================================================

redact_text_file() {
    local filepath="$1"
    local relative_path="${filepath#$PROJECT_ROOT/}"
    local tmp_file="${filepath}.tmp.$$"
    local changes=0

    # أنماط الاستبدال المحددة للملفات النصية
    declare -a SED_PATTERNS=(
        # كلمات مرور بين علامات اقتباس
        "s/(password|passwd|pwd)\s*[=:]\s*['\"][^'\"]{4,}['\"]/\1: REDACTED/gi"
        # توكنات API
        "s/(api[_-]?key|secret[_-]?key)\s*[=:]\s*['\"][a-zA-Z0-9_\-]{16,}['\"]/\1: REDACTED/gi"
        # JSON passwords
        "s/(\"password\"\s*:\s*\")[^\"]+\"/\1REDACTED\"/g"
        # MySQL connection strings
        "s|mysql://[^:]+:[^@]+@|mysql://USER:REDACTED@|g"
        # IP addresses مع بورت
        "s/([0-9]{1,3}\.){3}[0-9]{1,3}:[0-9]{2,5}/REDACTED_IP:PORT/g"
        # مفاتيح SSH
        "s/ssh-rsa AAAA[0-9A-Za-z+\/]+ /ssh-rsa REDACTED /g"
    )

    # فحص أولاً هل يحتوي الملف بيانات حساسة
    local has_sensitive=false
    for pattern_def in "${PATTERNS[@]}"; do
        local regex="${pattern_def#*:}"
        if grep -qiE "$regex" "$filepath" 2>/dev/null; then
            has_sensitive=true
            break
        fi
    done

    if [[ "$has_sensitive" == false ]]; then
        return 0
    fi

    # تطبيق الاستبدالات
    cp "$filepath" "$tmp_file"
    for sed_pattern in "${SED_PATTERNS[@]}"; do
        if sed -E "$sed_pattern" "$tmp_file" > "${tmp_file}.2" 2>/dev/null; then
            if ! cmp -s "$tmp_file" "${tmp_file}.2"; then
                mv "${tmp_file}.2" "$tmp_file"
                ((changes++)) || true
            else
                rm -f "${tmp_file}.2"
            fi
        else
            rm -f "${tmp_file}.2"
        fi
    done

    if [[ $changes -gt 0 ]]; then
        STATS[values_redacted]=$((STATS[values_redacted] + changes))
        if [[ "$DRY_RUN" == false && "$REPORT_ONLY" == false ]]; then
            mv "$tmp_file" "$filepath"
            log_success "تم تنقية $relative_path"
            STATS[files_modified]=$((STATS[files_modified] + 1))
        else
            rm -f "$tmp_file"
            log_warn "[DRY-RUN] سيتم تنقية $relative_path"
        fi
    else
        rm -f "$tmp_file"
    fi
}

# =============================================================================
# تنظيف التقارير من البيانات الحساسة داخلها
# =============================================================================

redact_report() {
    local filepath="$1"
    local relative_path="${filepath#$PROJECT_ROOT/}"

    # docs/ تحتوي على بيانات اتصال قديمة — فقط سجّل لا تحذف
    if [[ "$relative_path" == docs/* ]]; then
        log_warn "ملف توثيق يحتوي بيانات حساسة: $relative_path"
        report "  → يُنصح بمراجعة يدوية: $relative_path"
        return 0
    fi

    redact_text_file "$filepath"
}

# =============================================================================
# التنفيذ الرئيسي
# =============================================================================

main() {
    # ─── رأس التقرير ─────────────────────────────────────────────────────────
    echo ""
    echo -e "${BOLD}${CYAN}════════════════════════════════════════════════════════${RESET}"
    echo -e "${BOLD}${CYAN}  SARH Security Cleanup — $(date '+%Y-%m-%d %H:%M:%S')${RESET}"
    echo -e "${BOLD}${CYAN}════════════════════════════════════════════════════════${RESET}"
    echo -e "  المشروع: ${BOLD}$PROJECT_ROOT${RESET}"
    [[ "$DRY_RUN" == true ]] && echo -e "  الوضع:   ${YELLOW}${BOLD}DRY-RUN (معاينة فقط — لا تغييرات)${RESET}"
    [[ "$DRY_RUN" == false ]] && echo -e "  الوضع:   ${RED}${BOLD}LIVE (التغييرات ستُطبَّق!)${RESET}"
    echo ""

    # ─── تهيئة ملف التقرير ───────────────────────────────────────────────────
    {
        echo "SARH Security Scan Report"
        echo "Generated: $(date '+%Y-%m-%d %H:%M:%S')"
        echo "Project: $PROJECT_ROOT"
        echo "Mode: $([ "$DRY_RUN" == true ] && echo 'DRY-RUN' || echo 'LIVE')"
        echo "=============================================="
    } > "$REPORT_FILE"

    # ─── 1. فحص ملفات .env ───────────────────────────────────────────────────
    echo -e "\n${BOLD}[1/4] فحص ملفات .env${RESET}"
    report ""
    report "=== ملفات .env ==="

    while IFS= read -r -d '' file; do
        is_excluded "$file" && continue

        # .env.example آمن — لا تلمسه
        if [[ "$file" == *".env.example" ]]; then
            log_info "تخطي .env.example (آمن)"
            continue
        fi

        scan_file "$file"

        if [[ "$REPORT_ONLY" == false ]]; then
            redact_env_file "$file"
        fi
    done < <(find "$PROJECT_ROOT" -maxdepth 3 \
        -name ".env" -o -name ".env.*" \
        | grep -v vendor | grep -v node_modules \
        | tr '\n' '\0')

    # ─── 2. فحص ملفات .md ────────────────────────────────────────────────────
    echo -e "\n${BOLD}[2/4] فحص ملفات .md (التوثيق)${RESET}"
    report ""
    report "=== ملفات Markdown (.md) ==="

    while IFS= read -r -d '' file; do
        is_excluded "$file" && continue
        scan_file "$file"
        [[ "$REPORT_ONLY" == false ]] && redact_report "$file"
    done < <(find "$PROJECT_ROOT" -name "*.md" -print0 \
        | grep -zv vendor | grep -zv node_modules)

    # ─── 3. فحص ملفات .txt ───────────────────────────────────────────────────
    echo -e "\n${BOLD}[3/4] فحص ملفات .txt${RESET}"
    report ""
    report "=== ملفات Text (.txt) ==="

    while IFS= read -r -d '' file; do
        is_excluded "$file" && continue
        scan_file "$file"
        [[ "$REPORT_ONLY" == false ]] && redact_text_file "$file"
    done < <(find "$PROJECT_ROOT" -name "*.txt" -print0 \
        | grep -zv vendor | grep -zv node_modules)

    # ─── 4. فحص ملفات .json (غير vendor) ────────────────────────────────────
    echo -e "\n${BOLD}[4/4] فحص ملفات .json${RESET}"
    report ""
    report "=== ملفات JSON (.json) ==="

    while IFS= read -r -d '' file; do
        is_excluded "$file" && continue

        # تجاوز ملفات الـ lock وملفات الـ schema
        local basename
        basename=$(basename "$file")
        [[ "$basename" =~ ^(composer\.lock|package-lock\.json|yarn\.lock|.*\.schema\.json)$ ]] && continue

        scan_file "$file"
        [[ "$REPORT_ONLY" == false ]] && redact_text_file "$file"
    done < <(find "$PROJECT_ROOT" -maxdepth 4 -name "*.json" -print0 \
        | grep -zv vendor | grep -zv node_modules | grep -zv "\.git/")

    # ─── التقرير النهائي ─────────────────────────────────────────────────────
    echo ""
    echo -e "${BOLD}${CYAN}════════════════════════════════════════════════════════${RESET}"
    echo -e "${BOLD}  نتائج الفحص${RESET}"
    echo -e "${CYAN}════════════════════════════════════════════════════════${RESET}"
    echo -e "  الملفات المفحوصة:   ${BOLD}${STATS[files_scanned]}${RESET}"
    echo -e "  الملفات المُعلَّمة:  ${RED}${BOLD}${STATS[files_flagged]}${RESET}"
    echo -e "  الملفات المُعدَّلة:  ${GREEN}${BOLD}${STATS[files_modified]}${RESET}"
    echo -e "  القيم المُخفَّاة:   ${GREEN}${BOLD}${STATS[values_redacted]}${RESET}"
    echo ""

    {
        echo ""
        echo "====== الملخص ======"
        echo "الملفات المفحوصة:  ${STATS[files_scanned]}"
        echo "الملفات المعلمة:   ${STATS[files_flagged]}"
        echo "الملفات المعدّلة:  ${STATS[files_modified]}"
        echo "القيم المخفاة:    ${STATS[values_redacted]}"
    } >> "$REPORT_FILE"

    if [[ ${STATS[files_flagged]} -gt 0 ]]; then
        echo -e "  ${YELLOW}تقرير مفصّل: ${BOLD}$REPORT_FILE${RESET}"
    fi

    if [[ "$DRY_RUN" == true ]]; then
        echo ""
        echo -e "  ${YELLOW}${BOLD}لتطبيق التغييرات نفّذ:${RESET}"
        echo -e "  ${CYAN}bash secure_cleanup.sh --fix${RESET}"
    fi

    echo ""
}

main
