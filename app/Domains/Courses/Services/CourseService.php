<?php declare(strict_types=1);

namespace App\Domains\Courses\Services;

use App\Services\{FraudControlService, WalletService, PaymentService};
use App\Domains\Courses\Models\{Course, Enrollment, Certificate};
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Support\Str;

final class CourseService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly PaymentService $payment,
    ) {}

    public function enroll(array $data, bool $isB2B): array
    {
        $cid = Str::uuid()->toString();
        Log::channel('audit')->info('Course enrollment', compact('cid', 'isB2B'));
        $this->fraud->check(0, 'course_enroll', 0, null, null, $cid);

        return DB::transaction(function () use ($data, $isB2B, $cid) {
            $course = Course::findOrFail($data['course_id']);
            $price = $isB2B ? $course->price * 0.80 : $course->price;

            $enrollment = Enrollment::create([
                'tenant_id' => tenant()->id,
                'course_id' => $course->id,
                'user_id' => $data['user_id'] ?? null,
                'inn' => $data['inn'] ?? null,
                'business_card_id' => $data['business_card_id'] ?? null,
                'price_paid' => $price,
                'status' => 'active',
                'progress_percent' => 0,
                'correlation_id' => $cid,
            ]);

            return ['enrollment' => $enrollment, 'correlation_id' => $cid];
        });
    }

    public function generateWebRTCLink(int $lessonId): string
    {
        return 'https://meet.example.com/lesson-' . $lessonId . '-' . Str::random(12);
    }
}
