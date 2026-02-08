<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProjectDataSeeder extends Seeder
{
    /**
     * Seed 5 real Saudi branches + 42 employees.
     * Idempotent — uses updateOrCreate on email/code.
     */
    public function run(): void
    {
        $password = Hash::make('Goolbx512!!');

        /*
        |----------------------------------------------------------------------
        | 1. Branches — 5 Real Saudi Locations
        |----------------------------------------------------------------------
        */
        $branches = [
            [
                'code'                => 'RUH-HQ',
                'name_ar'             => 'المقر الرئيسي — الرياض',
                'name_en'             => 'Headquarters — Riyadh',
                'city'                => 'الرياض',
                'address'             => 'طريق الملك فهد، حي العليا',
                'latitude'            => 24.7136,
                'longitude'           => 46.6753,
                'geofence_radius'     => 50,
                'shift_start'         => '08:00',
                'shift_end'           => '17:00',
                'grace_period_minutes'=> 15,
                'salary_budget'       => 500000,
                'is_active'           => true,
            ],
            [
                'code'                => 'JED-01',
                'name_ar'             => 'فرع جدة — البلد',
                'name_en'             => 'Jeddah Branch — Al-Balad',
                'city'                => 'جدة',
                'address'             => 'شارع الملك عبدالعزيز، حي البلد',
                'latitude'            => 21.4858,
                'longitude'           => 39.1925,
                'geofence_radius'     => 40,
                'shift_start'         => '08:30',
                'shift_end'           => '17:30',
                'grace_period_minutes'=> 10,
                'salary_budget'       => 350000,
                'is_active'           => true,
            ],
            [
                'code'                => 'DMM-01',
                'name_ar'             => 'فرع الدمام — الكورنيش',
                'name_en'             => 'Dammam Branch — Corniche',
                'city'                => 'الدمام',
                'address'             => 'طريق الكورنيش، حي الشاطئ',
                'latitude'            => 26.4207,
                'longitude'           => 50.0888,
                'geofence_radius'     => 35,
                'shift_start'         => '07:30',
                'shift_end'           => '16:30',
                'grace_period_minutes'=> 10,
                'salary_budget'       => 280000,
                'is_active'           => true,
            ],
            [
                'code'                => 'MED-01',
                'name_ar'             => 'فرع المدينة المنورة',
                'name_en'             => 'Madinah Branch',
                'city'                => 'المدينة المنورة',
                'address'             => 'طريق الملك عبدالله، حي العزيزية',
                'latitude'            => 24.4672,
                'longitude'           => 39.6024,
                'geofence_radius'     => 30,
                'shift_start'         => '08:00',
                'shift_end'           => '17:00',
                'grace_period_minutes'=> 15,
                'salary_budget'       => 200000,
                'is_active'           => true,
            ],
            [
                'code'                => 'ABH-01',
                'name_ar'             => 'فرع أبها',
                'name_en'             => 'Abha Branch',
                'city'                => 'أبها',
                'address'             => 'شارع الملك فيصل، حي المنسك',
                'latitude'            => 18.2164,
                'longitude'           => 42.5053,
                'geofence_radius'     => 25,
                'shift_start'         => '08:00',
                'shift_end'           => '16:00',
                'grace_period_minutes'=> 10,
                'salary_budget'       => 150000,
                'is_active'           => true,
            ],
        ];

        $branchModels = [];
        foreach ($branches as $branchData) {
            $branchModels[$branchData['code']] = Branch::updateOrCreate(
                ['code' => $branchData['code']],
                $branchData
            );
        }

        /*
        |----------------------------------------------------------------------
        | 2. Super Admin — Level 10 God Mode
        |----------------------------------------------------------------------
        */
        User::updateOrCreate(
            ['email' => 'abdullah@sarh.app'],
            [
                'name_ar'                => 'عبدالله الكريم',
                'name_en'                => 'Abdullah Al-Kareem',
                'password'               => $password,
                'basic_salary'           => 45000,
                'housing_allowance'      => 11250,
                'transport_allowance'    => 3000,
                'security_level'         => 10,
                'is_super_admin'         => true,
                'branch_id'              => $branchModels['RUH-HQ']->id,
                'working_days_per_month' => 22,
                'working_hours_per_day'  => 8,
                'status'                 => 'active',
                'locale'                 => 'ar',
                'timezone'               => 'Asia/Riyadh',
                'total_points'           => 500,
            ]
        );

        /*
        |----------------------------------------------------------------------
        | 3. Employees — Distributed Across Branches
        |----------------------------------------------------------------------
        */
        $employees = [
            // ── RUH-HQ (12 employees) ──────────────────────────────
            ['name_ar' => 'فهد العتيبي',     'name_en' => 'Fahad Al-Otaibi',     'email' => 'fahad@sarh.app',    'basic_salary' => 12000, 'branch' => 'RUH-HQ'],
            ['name_ar' => 'سارة القحطاني',   'name_en' => 'Sarah Al-Qahtani',    'email' => 'sarah@sarh.app',    'basic_salary' => 11000, 'branch' => 'RUH-HQ'],
            ['name_ar' => 'محمد الغامدي',     'name_en' => 'Mohammed Al-Ghamdi',  'email' => 'mohammed@sarh.app', 'basic_salary' => 14000, 'branch' => 'RUH-HQ'],
            ['name_ar' => 'نورة الشهري',     'name_en' => 'Noura Al-Shahri',     'email' => 'noura@sarh.app',    'basic_salary' => 10500, 'branch' => 'RUH-HQ'],
            ['name_ar' => 'خالد الدوسري',    'name_en' => 'Khaled Al-Dosari',    'email' => 'khaled@sarh.app',   'basic_salary' => 13000, 'branch' => 'RUH-HQ'],
            ['name_ar' => 'ريم الحربي',      'name_en' => 'Reem Al-Harbi',       'email' => 'reem@sarh.app',     'basic_salary' => 9500,  'branch' => 'RUH-HQ'],
            ['name_ar' => 'عبدالرحمن المطيري','name_en' => 'Abdulrahman Al-Mutairi','email' => 'abdulrahman@sarh.app','basic_salary' => 15000, 'branch' => 'RUH-HQ'],
            ['name_ar' => 'هيفاء الزهراني',  'name_en' => 'Haifa Al-Zahrani',    'email' => 'haifa@sarh.app',    'basic_salary' => 10000, 'branch' => 'RUH-HQ'],
            ['name_ar' => 'تركي السبيعي',    'name_en' => 'Turki Al-Subaie',     'email' => 'turki@sarh.app',    'basic_salary' => 11500, 'branch' => 'RUH-HQ'],
            ['name_ar' => 'منال العنزي',     'name_en' => 'Manal Al-Anazi',      'email' => 'manal@sarh.app',    'basic_salary' => 9000,  'branch' => 'RUH-HQ'],
            ['name_ar' => 'ياسر الشمري',     'name_en' => 'Yasser Al-Shammari',  'email' => 'yasser@sarh.app',   'basic_salary' => 12500, 'branch' => 'RUH-HQ'],
            ['name_ar' => 'أمل الرشيدي',     'name_en' => 'Amal Al-Rashidi',     'email' => 'amal@sarh.app',     'basic_salary' => 10000, 'branch' => 'RUH-HQ'],

            // ── JED-01 (10 employees) ──────────────────────────────
            ['name_ar' => 'عمر البلوي',      'name_en' => 'Omar Al-Balawi',      'email' => 'omar@sarh.app',     'basic_salary' => 11000, 'branch' => 'JED-01'],
            ['name_ar' => 'لينا الجهني',     'name_en' => 'Lina Al-Juhani',      'email' => 'lina@sarh.app',     'basic_salary' => 10500, 'branch' => 'JED-01'],
            ['name_ar' => 'بدر الحازمي',     'name_en' => 'Badr Al-Hazmi',       'email' => 'badr@sarh.app',     'basic_salary' => 13000, 'branch' => 'JED-01'],
            ['name_ar' => 'دانة المالكي',    'name_en' => 'Dana Al-Malki',       'email' => 'dana@sarh.app',     'basic_salary' => 9500,  'branch' => 'JED-01'],
            ['name_ar' => 'سلطان الثقفي',    'name_en' => 'Sultan Al-Thaqafi',   'email' => 'sultan@sarh.app',   'basic_salary' => 12000, 'branch' => 'JED-01'],
            ['name_ar' => 'غادة الفيفي',     'name_en' => 'Ghada Al-Faifi',      'email' => 'ghada@sarh.app',    'basic_salary' => 10000, 'branch' => 'JED-01'],
            ['name_ar' => 'مشاري الزهراني',  'name_en' => 'Mishari Al-Zahrani',  'email' => 'mishari@sarh.app',  'basic_salary' => 11500, 'branch' => 'JED-01'],
            ['name_ar' => 'وعد القرشي',      'name_en' => 'Waad Al-Qurashi',     'email' => 'waad@sarh.app',     'basic_salary' => 9000,  'branch' => 'JED-01'],
            ['name_ar' => 'ماجد العمري',     'name_en' => 'Majed Al-Omari',      'email' => 'majed@sarh.app',    'basic_salary' => 14000, 'branch' => 'JED-01'],
            ['name_ar' => 'حنان السلمي',     'name_en' => 'Hanan Al-Sulami',     'email' => 'hanan@sarh.app',    'basic_salary' => 10000, 'branch' => 'JED-01'],

            // ── DMM-01 (8 employees) ──────────────────────────────
            ['name_ar' => 'أحمد الشهراني',   'name_en' => 'Ahmed Al-Shahrani',   'email' => 'ahmed@sarh.app',    'basic_salary' => 12000, 'branch' => 'DMM-01'],
            ['name_ar' => 'فاطمة الخالدي',   'name_en' => 'Fatima Al-Khalidi',   'email' => 'fatima@sarh.app',   'basic_salary' => 10500, 'branch' => 'DMM-01'],
            ['name_ar' => 'نايف العجمي',     'name_en' => 'Naif Al-Ajmi',        'email' => 'naif@sarh.app',     'basic_salary' => 11000, 'branch' => 'DMM-01'],
            ['name_ar' => 'مريم البقمي',     'name_en' => 'Maryam Al-Bugami',    'email' => 'maryam@sarh.app',   'basic_salary' => 9500,  'branch' => 'DMM-01'],
            ['name_ar' => 'عادل الحارثي',    'name_en' => 'Adel Al-Harthi',      'email' => 'adel@sarh.app',     'basic_salary' => 13500, 'branch' => 'DMM-01'],
            ['name_ar' => 'خلود الرحيلي',    'name_en' => 'Kholoud Al-Ruhaili',  'email' => 'kholoud@sarh.app',  'basic_salary' => 10000, 'branch' => 'DMM-01'],
            ['name_ar' => 'راشد المري',      'name_en' => 'Rashed Al-Marri',     'email' => 'rashed@sarh.app',   'basic_salary' => 11500, 'branch' => 'DMM-01'],
            ['name_ar' => 'بشاير الهاجري',   'name_en' => 'Bashaier Al-Hajri',   'email' => 'bashaier@sarh.app', 'basic_salary' => 9000,  'branch' => 'DMM-01'],

            // ── MED-01 (6 employees) ──────────────────────────────
            ['name_ar' => 'عبدالعزيز اليامي','name_en' => 'Abdulaziz Al-Yami',   'email' => 'abdulaziz@sarh.app','basic_salary' => 11000, 'branch' => 'MED-01'],
            ['name_ar' => 'جواهر النمري',    'name_en' => 'Jawaher Al-Namri',    'email' => 'jawaher@sarh.app',  'basic_salary' => 10000, 'branch' => 'MED-01'],
            ['name_ar' => 'وليد الغامدي',    'name_en' => 'Waleed Al-Ghamdi',    'email' => 'waleed@sarh.app',   'basic_salary' => 12000, 'branch' => 'MED-01'],
            ['name_ar' => 'شهد العسيري',     'name_en' => 'Shahd Al-Asiri',      'email' => 'shahd@sarh.app',    'basic_salary' => 9500,  'branch' => 'MED-01'],
            ['name_ar' => 'سعود المغربي',    'name_en' => 'Saud Al-Maghrabi',    'email' => 'saud@sarh.app',     'basic_salary' => 13000, 'branch' => 'MED-01'],
            ['name_ar' => 'ألماس الشريف',    'name_en' => 'Almas Al-Sharif',     'email' => 'almas@sarh.app',    'basic_salary' => 10500, 'branch' => 'MED-01'],

            // ── ABH-01 (5 employees) ──────────────────────────────
            ['name_ar' => 'حسن الشعبي',      'name_en' => 'Hassan Al-Sha\'bi',   'email' => 'hassan@sarh.app',   'basic_salary' => 10000, 'branch' => 'ABH-01'],
            ['name_ar' => 'سمية القحطاني',   'name_en' => 'Sumaya Al-Qahtani',   'email' => 'sumaya@sarh.app',   'basic_salary' => 9500,  'branch' => 'ABH-01'],
            ['name_ar' => 'طلال الشهري',     'name_en' => 'Talal Al-Shahri',     'email' => 'talal@sarh.app',    'basic_salary' => 11000, 'branch' => 'ABH-01'],
            ['name_ar' => 'ريان الدوسري',    'name_en' => 'Rayan Al-Dosari',     'email' => 'rayan@sarh.app',    'basic_salary' => 12000, 'branch' => 'ABH-01'],
            ['name_ar' => 'لمى الغامدي',     'name_en' => 'Lama Al-Ghamdi',      'email' => 'lama@sarh.app',     'basic_salary' => 9000,  'branch' => 'ABH-01'],
        ];

        foreach ($employees as $emp) {
            User::updateOrCreate(
                ['email' => $emp['email']],
                [
                    'name_ar'                => $emp['name_ar'],
                    'name_en'                => $emp['name_en'],
                    'password'               => $password,
                    'basic_salary'           => $emp['basic_salary'],
                    'housing_allowance'      => round($emp['basic_salary'] * 0.25),
                    'transport_allowance'    => 1500,
                    'security_level'         => 1,
                    'is_super_admin'         => false,
                    'branch_id'              => $branchModels[$emp['branch']]->id,
                    'working_days_per_month' => 22,
                    'working_hours_per_day'  => 8,
                    'status'                 => 'active',
                    'locale'                 => 'ar',
                    'timezone'               => 'Asia/Riyadh',
                    'total_points'           => 0,
                ]
            );
        }

        $this->command->info('✅ ProjectDataSeeder: 5 branches + 42 employees seeded.');
    }
}
