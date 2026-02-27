<?php

namespace App\Jobs;

use App\Models\Circular;
use App\Models\CircularDelivery;
use App\Models\PerformanceAlert;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCircularJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        protected Circular $circular,
    ) {}

    public function handle(): void
    {
        $circular = $this->circular;

        Log::info("SendCircularJob: بدء إرسال تعميم #{$circular->id} «{$circular->title_ar}»");

        // بناء الاستعلام حسب نطاق التعميم
        $query = User::query()
            ->where('is_active', true)
            ->select(['id', 'branch_id', 'department_id', 'role_id']);

        // تطبيق فلتر النطاق
        $query = match ($circular->target_scope) {
            'branch'     => $query->where('branch_id',     $circular->target_branch_id),
            'department' => $query->where('department_id', $circular->target_department_id),
            'role'       => $query->where('role_id',       $circular->target_role_id),
            default      => $query, // 'all' — كل المستخدمين النشطين
        };

        $totalSent  = 0;
        $totalFails = 0;

        // chunkById يمنع تحميل كل المستخدمين في الذاكرة دفعة واحدة
        $query->chunkById(100, function ($users) use ($circular, &$totalSent, &$totalFails) {
            foreach ($users as $user) {
                // تجنب الإرسال المكرر
                $alreadySent = CircularDelivery::where('circular_id', $circular->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                try {
                    // إشعار داخلي
                    PerformanceAlert::create([
                        'user_id'      => $user->id,
                        'alert_type'   => 'circular',
                        'severity'     => $circular->priority === 'urgent' ? 'warning' : 'info',
                        'title_ar'     => $circular->title_ar,
                        'title_en'     => $circular->title_en ?? $circular->title_ar,
                        'message_ar'   => mb_substr(strip_tags($circular->body_ar), 0, 500),
                        'message_en'   => mb_substr(strip_tags($circular->body_en ?? $circular->body_ar), 0, 500),
                        'trigger_data' => [
                            'circular_id' => $circular->id,
                            'priority'    => $circular->priority,
                        ],
                    ]);

                    // تسجيل الإرسال
                    CircularDelivery::create([
                        'circular_id' => $circular->id,
                        'user_id'     => $user->id,
                        'delivered_at'=> now(),
                    ]);

                    $totalSent++;
                } catch (\Exception $e) {
                    $totalFails++;
                    Log::warning("SendCircularJob: فشل إرسال إلى المستخدم #{$user->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // تحديث العداد كل chunk
            $circular->increment('processed_count', $users->count());

            // تخفيف الضغط على DB في الاستضافة المشتركة
            usleep(50_000); // 50ms
        });

        // تحديث الحالة النهائية
        $circular->update(['status' => 'completed']);

        Log::info("SendCircularJob: انتهى تعميم #{$circular->id}", [
            'sent'   => $totalSent,
            'failed' => $totalFails,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendCircularJob: فشل نهائي لتعميم #{$this->circular->id}", [
            'error' => $exception->getMessage(),
        ]);

        $this->circular->update(['status' => 'failed']);
    }
}

    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(
        protected Circular $circular,
        protected array $userIds,
    ) {}

    public function handle(): void
    {
        Log::info("SendCircularJob: إرسال تعميم #{$this->circular->id} إلى " . count($this->userIds) . ' موظف');

        $users = User::whereIn('id', $this->userIds)->get();

        foreach ($users->chunk(100) as $chunk) {
            foreach ($chunk as $user) {
                try {
                    // إنشاء إشعار داخلي (PerformanceAlert) كبديل عملي
                    \App\Models\PerformanceAlert::create([
                        'user_id'    => $user->id,
                        'alert_type' => 'circular',
                        'severity'   => $this->circular->priority === 'urgent' ? 'warning' : 'info',
                        'title_ar'   => $this->circular->title_ar,
                        'title_en'   => $this->circular->title_en ?? $this->circular->title_ar,
                        'message_ar' => mb_substr(strip_tags($this->circular->body_ar), 0, 500),
                        'message_en' => mb_substr(strip_tags($this->circular->body_en ?? $this->circular->body_ar), 0, 500),
                        'trigger_data' => [
                            'circular_id' => $this->circular->id,
                            'priority'    => $this->circular->priority,
                        ],
                    ]);
                } catch (\Exception $e) {
                    Log::warning("SendCircularJob: فشل إرسال إلى المستخدم {$user->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // تجنب الضغط على الـ DB
            if ($users->count() > 100) {
                sleep(1);
            }
        }

        Log::info("SendCircularJob: اكتمل إرسال تعميم #{$this->circular->id}");
    }
}
