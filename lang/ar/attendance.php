<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Module — Arabic
    |--------------------------------------------------------------------------
    */

    // Navigation
    'navigation_group'   => 'الحضور',
    'navigation_label'   => 'سجل الحضور',
    'model_label'        => 'سجل حضور',
    'plural_model_label' => 'سجلات الحضور',

    // Form Sections
    'check_in_section'  => 'بيانات الحضور',
    'financial_section' => 'الأثر المالي',
    'gps_section'       => 'بيانات الموقع الجغرافي',

    // Fields
    'employee'        => 'الموظف',
    'branch'          => 'الفرع',
    'date'            => 'التاريخ',
    'check_in_time'   => 'وقت الحضور',
    'check_out_time'  => 'وقت الانصراف',
    'status'          => 'الحالة',
    'delay_minutes'   => 'دقائق التأخير',
    'cost_per_minute' => 'تكلفة الدقيقة',
    'delay_cost'      => 'تكلفة التأخير',
    'overtime_minutes' => 'دقائق الإضافي',
    'overtime_value'   => 'قيمة العمل الإضافي',
    'worked_minutes'   => 'دقائق العمل',
    'check_in_distance' => 'المسافة عند الحضور',
    'within_geofence' => 'داخل الحدود الجغرافية',
    'manual_entry'    => 'إدخال يدوي',
    'notes'           => 'ملاحظات',
    'meters'          => 'متر',
    'min'             => 'د',
    'sar'             => 'ريال',
    'sar_min'         => 'ريال/د',

    // Statuses
    'status_present'  => 'حاضر',
    'status_late'     => 'متأخر',
    'status_absent'   => 'غائب',
    'status_on_leave' => 'في إجازة',
    'status_holiday'  => 'عطلة',
    'status_remote'   => 'عن بُعد',
    'status_half_day' => 'نصف يوم',

    // Filters
    'from_date'             => 'من تاريخ',
    'until_date'            => 'إلى تاريخ',
    'date_range'            => 'نطاق التاريخ',
    'with_financial_loss'   => 'مع خسائر مالية',

    // Dashboard Widget
    'today_present'         => 'الحاضرون اليوم',
    'today_late'            => 'المتأخرون اليوم',
    'today_absent'          => 'الغائبون اليوم',
    'today_delay_losses'    => 'خسائر التأخير اليوم',
    'today_overtime_value'  => 'قيمة العمل الإضافي اليوم',
    'out_of_total'          => 'من أصل :total موظف',
    'late_warning'          => 'يوجد تأخير!',
    'no_late'               => 'لا يوجد تأخير ✓',
    'financial_impact_today' => 'الأثر المالي لليوم',
    'overtime_at_1_5x'      => 'بمعدل 1.5× من تكلفة الدقيقة',

    // API Messages
    'check_in_success'   => 'تم تسجيل الحضور بنجاح',
    'check_out_success'  => 'تم تسجيل الانصراف بنجاح',
    'not_checked_in'     => 'لم يتم تسجيل الحضور اليوم بعد',
    'today_status'       => 'حالة اليوم',
    'outside_geofence'   => 'أنت خارج الحدود الجغرافية المسموحة. المسافة: :distance متر (الحد المسموح: :radius متر)',
    'no_branch_assigned' => 'لا يوجد فرع مُعيّن لهذا الموظف',
];
