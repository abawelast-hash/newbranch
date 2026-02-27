# 📘 دليل التشغيل القياسي (SOP) — نظام صرح الإتقان v3.0
> **الإصدار:** 3.0.0 (مستقر) | **تاريخ التحديث:** 2026-02-16  
> **المعدّ:** فريق هندسة صرح | **التصنيف:** سري — للاستخدام الداخلي فقط  
> **البيئة:** Hostinger Shared Hosting | PHP 8.3 | MySQL 8.0 | Laravel 11 + Filament 3  
> **Git:** `https://github.com/abawelast-hash/newbranch.git`

---

## 🎯 ما الجديد في v3.0.0

### التغييرات الرئيسية
✅ **إزالة نظام الفخاخ النفسية** — تم حذف 37+ ملف و مكون مرتبط  
✅ **واجهة Navy + Gold الفاخرة** — تصميم فاخر بألوان الأزرق الداكن (#0F172A) والذهبي (#D4A841)  
✅ **تنظيف شامل للكود** — إزالة الملفات الزائدة والسكريبتات الخطرة  
✅ **تحسين الأمان** — إزالة ML/ChurnPredictor غير المستخدم  
✅ **مستودع Git جديد** — نقل من abdullah-713/sarh18 إلى abawelast-hash/newbranch  

### الملفات المحذوفة
```
app/Models/Trap.php
app/Models/TrapInteraction.php
app/Events/TrapTriggered.php
app/Listeners/LogTrapInteraction.php
app/Services/TrapResponseService.php
app/Http/Controllers/TrapController.php
app/Filament/Resources/TrapResource.php
app/Filament/Resources/TrapInteractionResource.php
app/Filament/Pages/TrapAuditPage.php
app/Filament/Widgets/RiskWidget.php
app/Filament/Widgets/IntegrityAlertHub.php
app/ML/* (كامل المجلد)
create_test_user.php, reset_passwords.php (و4 سكريبتات خطرة أخرى)
```

---

## فهرس المحتويات

1. [نظرة عامة على المعمارية](#1-نظرة-عامة-على-المعمارية)
2. [دليل المسارات وآلية الربط الآمن](#2-دليل-المسارات-وآلية-الربط-الآمن)
3. [آلية العمليات (Workflows)](#3-آلية-العمليات-workflows)
4. [مصفوفة الصلاحيات الكاملة](#4-مصفوفة-الصلاحيات-الكاملة)
5. [دليل الأخطاء الشائعة وحلولها](#5-دليل-الأخطاء-الشائعة-وحلولها)
6. [أوامر الصيانة المرجعية](#6-أوامر-الصيانة-المرجعية)
7. [الواجهة الجديدة (Navy + Gold)](#7-الواجهة-الجديدة-navy--gold)

---

## 1. نظرة عامة على المعمارية

### 1.1 البنية التحتية

| العنصر | القيمة |
|--------|--------|
| **النطاق** | `sarh.io` |
| **الخادم** | Hostinger Shared Hosting |
| **SSH** | `ssh -p 65002 u307296675@194.164.74.250` |
| **كلمة المرور** | `Goolbx512@@` |
| **مسار المشروع** | `/home/u307296675/sarh` |
| **مسار الويب العام** | `/home/u307296675/domains/sarh.io/public_html` → bridge index.php |
| **قاعدة البيانات** | `u307296675_sarh1` @ `127.0.0.1:3306` |
| **PHP** | 8.3 مع إضافات: `pdo_mysql`, `bcmath`, `fileinfo`, `gd`, `openssl` |
| **إطار العمل** | Laravel 11 + Filament 3 + Livewire 3 + Tailwind CSS |
| **Git Remote** | `newrepo` → `https://github.com/abawelast-hash/newbranch.git` |

### 1.2 البوابات (Panels)

يعمل النظام ببوابتين منفصلتين تماماً:

| البوابة | المسار | الغرض | المستخدمون | الألوان |
|---------|--------|-------|------------|---------|
| **لوحة الإدارة** | `/admin` | إدارة الفروع، الحضور، التقارير، قبو البلاغات | `security_level >= 4` | Navy + Gold |
| **بوابة الموظف (PWA)** | `/app` | تسجيل حضور، عرض نقاط، إرسال بلاغات، مراسلات | `security_level >= 1` | Navy + Gold |

### 1.3 طبقات التطبيق v3.0

```
┌─────────────────────────────────────────────────┐
│          المتصفح / PWA (Navy + Gold Theme)       │
├────────────────────┬────────────────────────────┤
│   /admin (Filament) │      /app (Livewire)       │
├────────────────────┴────────────────────────────┤
│              Laravel 11 (Middleware)              │
│  ┌──────────┐ ┌──────────┐ ┌──────────────────┐ │
│  │Gate::     │ │TrustProxy│ │GeofencePermission│ │
│  │before()   │ │(*)       │ │Policy            │ │
│  └──────────┘ └──────────┘ └──────────────────┘ │
├─────────────────────────────────────────────────┤
│              طبقة الخدمات (Services)              │
│  ┌────────────────┐ ┌─────────────────────────┐ │
│  │GeofencingService│ │AttendanceService        │ │
│  │(Haversine 17m) │ │(Check-in/out + Finance) │ │
│  └────────────────┘ └─────────────────────────┘ │
│  ┌────────────────┐ ┌─────────────────────────┐ │
│  │AnalyticsService│ │FinancialReporting       │ │
│  │                │ │Service                  │ │
│  └────────────────┘ └─────────────────────────┘ │
│  ┌────────────────┐ ┌─────────────────────────┐ │
│  │AnomalyDetector │ │FormulaEngineService     │ │
│  │                │ │                         │ │
│  └────────────────┘ └─────────────────────────┘ │
├─────────────────────────────────────────────────┤
│          طبقة الأحداث والمهام (v3.0)             │
│  ┌────────────────┐ ┌─────────────────────────┐ │
│  │Events:         │ │Jobs (Queue):            │ │
│  │BadgeAwarded    │ │ProcessAttendanceJob     │ │
│  │AnomalyDetected │ │SendCircularJob          │ │
│  │AttendanceRec.  │ │RecalculateMonthlyJob    │ │
│  └────────────────┘ └─────────────────────────┘ │
│  ┌────────────────┐ ┌─────────────────────────┐ │
│  │Listeners:      │ │Policies:                │ │
│  │HandleBadgePts  │ │UserPolicy               │ │
│  │(Badge points)  │ │AttendanceLogPolicy      │ │
│  └────────────────┘ └─────────────────────────┘ │
├─────────────────────────────────────────────────┤
│         Eloquent ORM + MySQL 8.0                 │
│         (24 جدول نشط + فهارس أداء)               │
│         UserShift / UserBadge كيانات مستقلة      │
└─────────────────────────────────────────────────┘
```

**ملاحظة:** تم إزالة TrapResponseService و ML/ChurnPredictor من المعمارية.

---

## 2. دليل المسارات وآلية الربط الآمن

### 2.1 المشكلة الأمنية

في الاستضافة المشتركة (Shared Hosting)، المجلد الذي يراه المتصفح هو `public_html/`. إذا وُضع كامل مشروع Laravel داخله، ستكون ملفات `.env` وقاعدة البيانات والكود المصدري **مكشوفة للعالم**. هذا خطر أمني كارثي.

### 2.2 الحل المعتمد: فصل المسارات

```
/home/u850419603/
├── sarh/                          ← 🔒 مشروع Laravel الكامل (خارج الويب)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env                       ← 🔒 بيانات حساسة (غير مكشوفة)
│   └── public/                    ← ✅ المجلد العام الوحيد
│       ├── build/                 ← أصول Vite المُجمّعة (CSS/JS)
│       ├── .htaccess
│       └── index.php
│
└── public_html/                   ← 🌐 ما يراه المتصفح
    └── → symlink → /home/u850419603/sarh/public
```

### 2.3 إنشاء الرابط الرمزي

```bash
# إزالة المجلد القديم وإنشاء الرابط
rm -rf /home/u850419603/public_html
ln -s /home/u850419603/sarh/public /home/u850419603/public_html
```

**التحقق:**
```bash
ls -la /home/u850419603/public_html
# يجب أن يُظهر: public_html → /home/u850419603/sarh/public
```

---

## 3. آلية العمليات (Workflows)

### 3.1 عملية تسجيل الحضور الجغرافي

#### 3.1.1 مخطط التدفق

```
┌─────────────────────────────────────────────────────────┐
│                 الموظف يفتح PWA (/app)                   │
│                 يضغط "تسجيل حضور" 🛰️                    │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│     المتصفح يطلب إذن GPS (Geolocation API)              │
│     navigator.geolocation.getCurrentPosition()           │
└────────────────────────┬────────────────────────────────┘
                         │ {latitude, longitude}
                         ▼
┌─────────────────────────────────────────────────────────┐
│     POST /attendance/check-in                            │
│     البيانات: {latitude, longitude}                      │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│          AttendanceController@checkIn                     │
│  1. تحميل بيانات فرع الموظف (Branch)                    │
│  2. التحقق من المستوى الأمني:                           │
│     ├── Level 10 أو is_super_admin → bypass-geofence     │
│     └── غير ذلك → GeofencingService                     │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│          GeofencingService::validatePosition()            │
│                                                          │
│  📐 حساب المسافة بصيغة Haversine:                       │
│  d = 2R × arcsin(√(sin²(Δφ/2) +                        │
│       cos(φ₁) × cos(φ₂) × sin²(Δλ/2)))                │
│                                                          │
│  R = 6,371,000 متر (نصف قطر الأرض)                    │
│                                                          │
│  النتيجة:                                                │
│  ├── distance_meters: المسافة الفعلية                   │
│  └── within_geofence: ≤ 17 متراً؟                      │
└────────────┬──────────────────────┬─────────────────────┘
             │                      │
        ≤ 17 متر              > 17 متر
             │                      │
             ▼                      ▼
┌────────────────────┐  ┌────────────────────────────────┐
│   ✅ قبول التسجيل   │  │  ❌ رفض التسجيل                 │
│                    │  │  رسالة: "أنت خارج نطاق        │
│                    │  │  الفرع (XX متر)"               │
└────────┬───────────┘  └────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────┐
│          AttendanceService::checkIn()                     │
│  1. تحديد الوردية الحالية                               │
│  2. حساب التأخير بالدقائق                               │
│  3. حساب التكلفة المالية:                               │
│     cost = (delay_minutes / 60) × hourly_rate            │
│  4. تحديد الحالة: on_time / late / absent               │
│  5. إنشاء سجل AttendanceLog                             │
│  6. تحديث نقاط المنافسة                                 │
└─────────────────────────────────────────────────────────┘
```

### 3.2 محرك المنافسة (Competition Engine)

#### 3.2.1 منطق احتساب النقاط

```
┌─────────────────────────────────────────────────────────┐
│              مصادر النقاط لكل موظف                       │
├─────────────────────────────────────────────────────────┤
│  📅  الحضور اليومي:                                     │
│  ├── حضور في الموعد (on_time)     → +10 نقاط           │
│  ├── متأخر (late)                 → +0 نقاط            │
│  └── غائب (absent)                → -5 نقاط            │
│                                                          │
│  🏅 الشارات (Badges):                                    │
│  ├── سيد الانضباط (شهر بدون تأخير)   → +200 نقطة      │
│  ├── سلسلة حديدية (7 أيام متتالية)   → +70 نقطة       │
│  ├── سلسلة ذهبية (30 يوم متتالي)     → +500 نقطة      │
│  ├── صفر خسائر (شهر بدون خسارة)      → +300 نقطة      │
│  └── موفر التكاليف (50+ ساعة إضافية) → +150 نقطة      │
│                                                          │
│  ⭐ تعديل يدوي (المستوى 10 فقط):                        │
│  └── تعديل النقاط + سبب التعديل                         │
│      يُحفظ عبر PointsTransaction                         │
└─────────────────────────────────────────────────────────┘
```

#### 3.2.2 نقاط الفرع

```
نقاط الفرع = Σ (total_points) لجميع الموظفين المنتمين للفرع
```

#### 3.2.3 مستويات الفروع

| المستوى | الرمز | نطاق النقاط | الوصف |
|---------|-------|------------|-------|
| **أسطوري** | 🏆 | ≥ 150 | أعلى مرتبة — انضباط استثنائي |
| **ألماسي** | 💎 | ≥ 120 | أداء متميز جداً |
| **ذهبي** | 🥇 | ≥ 100 | أداء ممتاز |
| **فضي** | 🥈 | ≥ 80 | أداء جيد |
| **برونزي** | 🥉 | ≥ 60 | أداء مقبول |
| **مبتدئ** | 🐢 | < 60 | يحتاج تحسين |

---

## 4. مصفوفة الصلاحيات الكاملة

### 4.1 نظام المستويات الأمنية

| المستوى | الدور | الوصف |
|---------|-------|-------|
| **1** | متدرب | حضور شخصي فقط |
| **2** | موظف | حضور + مالية شخصية + إجازات + بلاغات |
| **3** | موظف أول | + تصدير بيانات |
| **4** | قائد فريق | + عرض حضور الفريق + اعتماد إجازات |
| **5** | مدير فرع | + إدارة الفرع + تقارير مالية محلية |
| **6** | مدير إقليمي | + إدارة عدة فروع |
| **7** | مدير عمليات | + تقارير شاملة |
| **8** | نائب المدير العام | + صلاحيات إدارية واسعة |
| **9** | مدير تنفيذي | + كل شيء ما عدا قبو المستوى 10 |
| **10** | **المدير العام** | **تحكم مطلق — وضع الله** |

### 4.2 البوابات (Gates) المُعرّفة

| البوابة | الشرط | التأثير |
|---------|-------|---------|
| **`Gate::before()`** | `security_level === 10 \|\| is_super_admin` | تجاوز مطلق لكل الصلاحيات |
| `access-whistleblower-vault` | `security_level >= 10` | الوصول لقبو البلاغات المشفرة |
| `bypass-geofence` | `security_level >= 10` | تسجيل حضور من أي موقع |
| `manage-competition` | `security_level >= 10` | إدارة إعدادات المنافسة |
| `adjust-points` | `security_level >= 10` | تعديل نقاط الموظفين يدوياً |

---

## 5. دليل الأخطاء الشائعة وحلولها

### 5.1 جدول الأخطاء السريع

| الرمز | العَرَض | السبب | الحل |
|-------|---------|-------|------|
| **500** | Internal Server Error | صلاحيات الملفات أو كاش تالف | `chmod -R 775 storage bootstrap/cache && php artisan optimize:clear` |
| **405** | Method Not Allowed | كاش المسارات قديم | `php artisan route:clear` |
| **419** | Page Expired | CSRF أو Trusted Proxies | تحقق من `bootstrap/app.php` → `trustProxies(at: '*')` |
| **404** | Not Found | `.htaccess` مفقود | `cp public/.htaccess public_html/` |
| — | صفحة بيضاء | `APP_DEBUG=false` | مؤقتاً اجعلها `true` لرؤية الخطأ |

### 5.2 الإصلاح الشامل

```bash
#!/bin/bash
cd /home/u850419603/sarh

echo "▸ تنظيف الكاش..."
php artisan optimize:clear

echo "▸ إصلاح الصلاحيات..."
chmod -R 775 storage bootstrap/cache

echo "▸ إعادة بناء الكاش..."
php artisan optimize

echo "▸ تحسين Filament..."
php artisan filament:optimize

echo "✅ تم الإصلاح"
```

---

## 6. أوامر الصيانة المرجعية

### 6.1 الأوامر اليومية

```bash
# الاتصال بالخادم
sshpass -p 'Goolbx512@@' ssh -p 65002 u307296675@194.164.74.250

# الانتقال للمشروع
cd /home/u850419603/sarh
```

### 6.2 جدول الأوامر

| الغرض | الأمر |
|-------|-------|
| **تنظيف كل الكاش** | `php artisan optimize:clear` |
| **إعادة بناء الكاش** | `php artisan optimize` |
| **تشغيل الترحيلات** | `php artisan migrate --force` |
| **بذر البيانات** | `php artisan db:seed --force` |
| **إنشاء مدير نظام** | `php artisan sarh:install` |
| **فحص المسارات** | `php artisan route:list` |
| **رابط التخزين** | `php artisan storage:link --force` |
| **تحسين Filament** | `php artisan filament:optimize` |
| **النسخ الاحتياطي** | `mysqldump -u u850419603_sarh -p u850419603_sarh > backup.sql` |

---

## 7. الواجهة الجديدة (Navy + Gold)

### 7.1 نظام الألوان v3.0

```css
/* متغيرات CSS الرئيسية */
:root {
  --sarh-gold: #D4A841;      /* الذهبي الأساسي */
  --sarh-navy: #0F172A;      /* الأزرق الداكن */
  --sarh-gold-light: #F5E4A3;
  --sarh-gold-dark: #967520;
  --sarh-navy-light: #1E293B;
}
```

### 7.2 المكونات المحدثة

| المكون | اللون القديم | اللون الجديد |
|--------|------------|-------------|
| **Primary Palette** | Orange #FF8C00 | Gold #D4A841 |
| **Background** | White/Gray | Navy #0F172A |
| **Accent** | - | Gold gradient |
| **Sidebar** | Gray | Navy + Gold border |
| **Buttons** | Orange | Gold with glow |
| **Login Card** | White | Glassmorphism Navy |
| **Meta Theme** | #FF8C00 | #0F172A |

### 7.3 الملفات المحدثة

```
resources/css/filament/admin/theme.css  ✅ محدث بالكامل
resources/css/filament/app/theme.css    ✅ محدث بالكامل
app/Providers/Filament/AdminPanelProvider.php  ✅ Gold palette
app/Providers/Filament/AppPanelProvider.php    ✅ Gold palette
```

---

## 8. النشر والتحديث

### 8.1 النشر الكامل

```bash
# 1. بناء الأصول محلياً
cd "/home/sarh/سطح المكتب/work/proj/sarh"
npm install
npm run build

# 2. Commit + Push
git add -A
git commit -m "v3.0.0: Navy+Gold UI, Removed Traps, Cleanup"
git push newrepo main

# 3. النشر على Hostinger
sshpass -p 'Goolbx512@@' ssh -p 65002 -o StrictHostKeyChecking=no u307296675@194.164.74.250 'cd /home/u307296675/sarh && bash deploy.sh 2>&1'
```

### 8.2 التحقق بعد النشر

✅ https://sarh.online/admin — يُحمّل بدون أخطاء  
✅ الواجهة Navy + Gold تظهر بشكل صحيح  
✅ تسجيل دخول المدير العام يعمل  
✅ جميع الأنظمة الأساسية تعمل  
✅ PWA على الموبايل (/app) يعمل  

---

## 9. معلومات الوصول

| العنصر | القيمة |
|--------|--------|
| **حساب المدير العام** | `abdullah@sarh.app` |
| **كلمة المرور** | `Goolbx512@@` |
| **المستوى الأمني** | 10 (God Mode) |
| **لوحة الإدارة** | `https://sarh.io/admin` |
| **بوابة الموظف** | `https://sarh.io/app` |
| **توثيق API** | `https://sarh.io/docs/api` |

---

> **صرح الإتقان v3.0** — نظام الموارد البشرية والذكاء المالي المؤسسي  
> *"صفر تأخير. صفر خسائر. واجهة فاخرة."*
