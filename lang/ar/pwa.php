<?php

return [

    // App
    'app_title'       => 'صَرْح الإتقان',
    'app_name'        => 'صَرْح',
    'app_subtitle'    => 'منصة الموارد البشرية',

    // Navigation
    'dashboard'          => 'لوحة التحكم',
    'nav_dashboard'      => 'الرئيسية',
    'nav_messages'       => 'الرسائل',
    'nav_whistleblower'  => 'الإبلاغ السري',

    // Dashboard
    'welcome'            => 'مرحباً',
    'welcome_subtitle'   => 'إليك ملخص يومك',

    // Attendance Widget
    'attendance_title'   => 'الحضور والانصراف',
    'status_checked_in'  => 'تم تسجيل الحضور',
    'status_checked_out' => 'تم تسجيل الانصراف',
    'status_absent'      => 'لم يتم التسجيل',
    'status_not_checked_in' => 'لم يسجّل بعد',
    'btn_check_in'       => 'تسجيل حضور',
    'btn_check_out'      => 'تسجيل انصراف',
    'btn_done'           => 'اكتمل اليوم',
    'check_in_time'      => 'وقت الحضور',
    'check_out_time'     => 'وقت الانصراف',
    'check_in_success'   => 'تم تسجيل الحضور بنجاح',
    'check_out_success'  => 'تم تسجيل الانصراف بنجاح',
    'gps_acquiring'      => 'جارٍ تحديد الموقع...',
    'gps_error'          => 'تعذر تحديد الموقع الجغرافي',
    'inside_geofence'    => 'داخل نطاق العمل',
    'outside_geofence'   => 'خارج نطاق العمل',
    'meters'             => 'م',

    // Gamification Widget
    'gamification_title' => 'الإنجازات والنقاط',
    'points'             => 'النقاط',
    'current_streak'     => 'تتابع حالي',
    'longest_streak'     => 'أفضل تتابع',
    'days'               => 'يوم',
    'badges'             => 'الأوسمة',
    'no_badges'          => 'لم تحصل على أوسمة بعد',

    // Financial Widget
    'financial_title'    => 'المؤشرات المالية',
    'on_time_rate'       => 'معدل الانضباط',
    'delay_cost'         => 'تكلفة التأخير',
    'currency'           => 'ر.س',
    'late_days'          => 'أيام التأخير',
    'this_month'         => 'هذا الشهر',

    // Circulars Widget
    'circulars_title'    => 'التعاميم',
    'acknowledge'        => 'اطلعت',
    'acknowledged'       => 'تم الاطلاع',
    'no_circulars'       => 'لا توجد تعاميم حالياً',
    'priority_urgent'    => 'عاجل',
    'priority_high'      => 'مرتفع',
    'priority_normal'    => 'عادي',

    // Whistleblower Form
    'wb_title'           => 'الإبلاغ السري',
    'wb_subtitle'        => 'بلاغ مجهول الهوية ومشفر بالكامل',
    'wb_security_title'  => 'حماية هويتك',
    'wb_security_1'      => 'لا يتم تسجيل هويتك أو عنوان IP الخاص بك',
    'wb_security_2'      => 'محتوى البلاغ مشفر بتقنية AES-256-CBC',
    'wb_security_3'      => 'احتفظ برمز التتبع السري لمتابعة بلاغك',
    'wb_category'        => 'التصنيف',
    'wb_select_category' => 'اختر التصنيف',
    'wb_cat_fraud'       => 'احتيال مالي',
    'wb_cat_corruption'  => 'فساد إداري',
    'wb_cat_harassment'  => 'تحرش أو تنمر',
    'wb_cat_safety'      => 'مخالفة أمن وسلامة',
    'wb_cat_discrimination' => 'تمييز عنصري',
    'wb_cat_other'       => 'أخرى',
    'wb_severity'        => 'مستوى الخطورة',
    'wb_sev_low'         => 'منخفض',
    'wb_sev_medium'      => 'متوسط',
    'wb_sev_high'        => 'مرتفع',
    'wb_sev_critical'    => 'حرج',
    'wb_content'         => 'تفاصيل البلاغ',
    'wb_content_placeholder' => 'اكتب تفاصيل البلاغ بوضوح. كلما زادت التفاصيل كان التحقيق أسرع...',
    'wb_submit'          => 'إرسال البلاغ',
    'wb_success_title'   => 'تم إرسال البلاغ بنجاح',
    'wb_ticket'          => 'رقم البلاغ',
    'wb_secret_token'    => 'رمز التتبع السري',
    'wb_token_warning'   => 'احفظ هذا الرمز فوراً! لن يظهر مرة أخرى ولا يمكن استرجاعه.',
    'wb_new_report'      => 'إرسال بلاغ جديد',

    // Whistleblower Track
    'wb_track_title'     => 'متابعة البلاغ',
    'wb_track_subtitle'  => 'أدخل رمز التتبع السري لمعرفة حالة بلاغك',
    'wb_enter_token'     => 'رمز التتبع',
    'wb_token_placeholder' => 'أدخل رمز التتبع السري',
    'wb_track_btn'       => 'استعلام',
    'wb_report_status'   => 'حالة البلاغ',
    'wb_status'          => 'الحالة',
    'wb_status_pending'  => 'قيد المراجعة',
    'wb_status_investigating' => 'قيد التحقيق',
    'wb_status_resolved' => 'تم المعالجة',
    'wb_status_dismissed' => 'مرفوض',
    'wb_submitted_at'    => 'تاريخ الإرسال',
    'wb_resolved_at'     => 'تاريخ المعالجة',
    'wb_not_found'       => 'لم يتم العثور على بلاغ بهذا الرمز',

    // Messaging
    'messaging_title'    => 'الرسائل',
    'msg_group'          => 'مجموعة',
    'msg_unknown'        => 'مستخدم',
    'msg_no_messages'    => 'لا رسائل بعد',
    'msg_empty'          => 'لا توجد محادثات',
    'msg_participants'   => 'مشاركين',
    'msg_type_message'   => 'اكتب رسالتك...',

    // Traps
    'trap_salary_peek'   => 'كشف الرواتب',
    'trap_data_export'   => 'تصدير البيانات',
    'exporting'          => 'جارٍ التصدير',
    'export_complete'    => 'اكتمل التصدير',

    // Competition Widget
    'competition_title'      => 'منافسة الفروع',
    'your_branch'            => 'فرعك',
    'top_branches'           => 'أفضل الفروع',

    // Branch Progress Widget
    'branch_progress_title'  => 'تقدم الفرع',
    'branch_attendance_rate' => 'معدل الحضور',
    'max_level'              => 'أعلى مستوى',
    'no_branch_assigned'     => 'لم يتم تعيين فرع',

    // Attendance Stats Widget
    'attendance_stats_title' => 'إحصائيات الحضور',
    'present'                => 'حاضر',
    'late'                   => 'متأخر',
    'absent'                 => 'غائب',
    'streak'                 => 'تتابع',
    'avg_delay'              => 'متوسط التأخير',
    'hours_worked'           => 'ساعات العمل',
    'overtime_hours'         => 'ساعات إضافية',
    'weekly_breakdown'       => 'التفصيل الأسبوعي',
    'week'                   => 'أسبوع',
    'no_attendance_data'     => 'لا توجد بيانات حضور',
    'on_time_days'           => 'أيام منضبطة',

    // General
    'loading'            => 'جارٍ التحميل',

];
