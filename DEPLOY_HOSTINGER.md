# صرح الإتقان — دليل النشر على Hostinger (sarh.io)
> **التاريخ:** 2026-02-08 | **حالة قاعدة البيانات:** فارغة — تثبيت جديد

---

## مرجع سريع

| العنصر | القيمة |
|--------|--------|
| النطاق | `sarh.io` |
| SSH | `ssh -p 65002 u307296675@194.164.74.250` |
| مسار المشروع | `/home/u307296675/sarh` |
| جذر الويب | `/home/u307296675/public_html` → رابط رمزي إلى `sarh/public` |
| قاعدة البيانات | `u307296675_sarh` @ `127.0.0.1:3306` |
| مستخدم قاعدة البيانات | `u307296675_sarh` |
| لوحة الإدارة | `https://sarh.online/admin` |

---

## خطوات النشر التفصيلية

### 1. الاتصال بالخادم عبر SSH

```bash
ssh -p 65002 u307296675@194.164.74.250
```

### 2. رفع المشروع

**الخيار أ — استنساخ Git (إذا كان المستودع موجوداً):**
```bash
cd /home/u850419603
git clone <رابط_المستودع> sarh
cd sarh
```

**الخيار ب — الرفع عبر مدير الملفات / SFTP:**
ارفع كامل مجلد `sarh/` إلى `/home/u307296675/sarh`.

> **أمان:** المشروع يقع **فوق** `public_html`، لذا فقط مجلد `public/` يكون متاحاً عبر الويب.

### 3. تشغيل سكربت النشر

```bash
cd /home/u307296675/sarh
chmod +x deploy.sh
bash deploy.sh
```

هذا الأمر الواحد سيقوم بـ:
1. ✅ `composer install --no-dev --optimize-autoloader`
2. ✅ نسخ `.env.production` → `.env` وتوليد `APP_KEY`
3. ✅ `php artisan migrate --force --seed` (إنشاء 16 جدول + بذر الأدوار والمصائد والشارات)
4. ✅ `npm install && npm run build` (تجميع أصول Vite/Tailwind)
5. ✅ `php artisan storage:link`
6. ✅ حذف `public_html/` وربطه رمزياً بـ `sarh/public/`
7. ✅ `php artisan optimize` (تخزين الإعدادات والمسارات والعروض مؤقتاً)
8. ✅ إصلاح صلاحيات `storage/` و `bootstrap/cache/`

### 4. إنشاء المدير العام

بعد اكتمال deploy.sh:

```bash
php artisan sarh:install
```

> هذا هو أمر `SarhInstallCommand`. بما أن الترحيلات تمت بالفعل، سينتقل مباشرة لإنشاء حساب المدير العام (المستوى 10).  
> أدخل: **الاسم_العربي**، **الاسم_الإنجليزي**، **البريد_الإلكتروني**، **كلمة_المرور** عند الطلب.

### 5. التحقق

افتح `https://sarh.online/admin` وسجل الدخول ببيانات المدير العام.

---

## إذا لم يكن npm متاحاً على Hostinger

استضافة Hostinger المشتركة قد لا تتضمن Node.js. في هذه الحالة:

1. ابنِ محلياً على جهازك:
```bash
cd sarh
npm install
npm run build
```

2. ارفع مجلد `public/build/` المُنشأ إلى `/home/u307296675/sarh/public/build/` عبر SFTP.

سكربت النشر يكتشف ذلك ويُنبهك — لن يفشل.

---

## مرجع الأوامر اليدوية

إذا كنت تفضل تنفيذ الخطوات فردياً بدلاً من `deploy.sh`:

```bash
# الانتقال للمشروع
cd /home/u307296675/sarh

# نسخ ملف البيئة
cp .env.production .env
php artisan key:generate --force

# مكتبات PHP
composer install --no-dev --optimize-autoloader --no-interaction

# قاعدة البيانات (فارغة → مخطط كامل + بيانات أولية)
php artisan migrate --force --seed

# الواجهة الأمامية (إذا كان npm متاحاً)
npm install --no-audit --no-fund && npm run build

# رابط التخزين
php artisan storage:link --force

# الرابط الرمزي لـ public_html
rm -rf /home/u307296675/public_html
ln -s /home/u307296675/sarh/public /home/u307296675/public_html

# كاش الإنتاج
php artisan optimize

# الصلاحيات
chmod -R 775 storage bootstrap/cache

# إنشاء المدير العام
php artisan sarh:install
```

---

## إصلاح الصلاحيات (عند ظهور خطأ 500)

```bash
cd /home/u307296675/sarh
chmod -R 775 storage bootstrap/cache
chmod -R 644 storage/logs/*.log 2>/dev/null || true
```

إذا كان Hostinger يستخدم مستخدم خادم ويب مختلف:
```bash
# فحص مستخدم خادم الويب
ps aux | grep -E 'apache|nginx|lsws' | head -1

# إذا لزم الأمر، اضبط ملكية المجموعة
chgrp -R www-data storage bootstrap/cache
```

---

## استكشاف الأخطاء وإصلاحها

| العَرَض | الحل |
|---------|------|
| خطأ 500 Internal Server Error | `chmod -R 775 storage bootstrap/cache` |
| "Vite manifest not found" | ارفع `public/build/` — بناء npm كان مفقوداً |
| "SQLSTATE Connection refused" | تحقق أن DB host هو `127.0.0.1` في `.env` وليس `localhost` |
| CSS/JS لا يُحمّل | تأكد من الرابط الرمزي: `ls -la /home/u307296675/public_html` يجب أن يُظهر `→ sarh/public` |
| "No application encryption key" | `php artisan key:generate --force` |
| 404 على كل المسارات ما عدا `/` | تحقق من وجود `.htaccess` في `public/`، Hostinger قد يحتاج `mod_rewrite` |
| صفحة تسجيل الدخول فارغة | `php artisan filament:optimize` ثم `php artisan view:clear` |

---

## قائمة التحقق بعد النشر

- [ ] `https://sarh.io` يُحمّل بدون أخطاء
- [ ] `https://sarh.io/admin` يُظهر صفحة تسجيل دخول Filament
- [ ] المدير العام يستطيع تسجيل الدخول (المستوى 10)
- [ ] التنسيق العربي RTL يظهر بشكل صحيح
- [ ] القائمة الجانبية تُظهر: الحضور، الأمان، غرفة القيادة
- [ ] `APP_DEBUG=false` مؤكد في `.env`
