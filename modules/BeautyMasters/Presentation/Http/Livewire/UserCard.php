<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Livewire;

use Livewire\Component;
use Modules\BeautyMasters\Infrastructure\Models\ClientModel;
use Modules\BeautyMasters\Infrastructure\Models\AppointmentModel;
use Modules\BeautyMasters\Infrastructure\Models\AppointmentPhotoModel;
use Modules\BeautyMasters\Infrastructure\Models\LoyaltyProfileModel;
use Illuminate\Support\Facades\Cache;

final class UserCard extends Component
{
    public int $userId;
    public int $venueId;
    public array $client = [];
    public array $loyaltyProfile = [];
    public array $recentAppointments = [];
    public array $photos = [];
    public bool $showAllAppointments = false;

    public function mount(int $userId, int $venueId): void
    {
        $this->userId = $userId;
        $this->venueId = $venueId;
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loadClient();
        $this->loadLoyaltyProfile();
        $this->loadRecentAppointments();
        $this->loadPhotos();
    }

    private function loadClient(): void
    {
        $cacheKey = "beauty:user_card:{$this->userId}:{$this->venueId}";

        $this->client = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $client = ClientModel::where('user_id', $this->userId)
                ->where('venue_id', $this->venueId)
                ->first();

            if (!$client) {
                return [];
            }

            return [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'phone' => $client->phone,
                'email' => $client->email,
                'avatar' => $client->avatar,
                'is_vip' => $client->is_vip,
                'total_visits' => $client->total_visits,
                'total_spent' => $client->total_spent,
                'average_check' => $client->average_check,
                'first_visit_at' => $client->first_visit_at?->format('d.m.Y'),
                'last_visit_at' => $client->last_visit_at?->format('d.m.Y'),
                'allergies' => $client->allergies,
                'preferences' => $client->preferences,
                'notes' => $client->notes,
            ];
        });
    }

    private function loadLoyaltyProfile(): void
    {
        $profile = LoyaltyProfileModel::where('client_id', $this->userId)
            ->where('venue_id', $this->venueId)
            ->first();

        if (!$profile) {
            $this->loyaltyProfile = [];
            return;
        }

        $this->loyaltyProfile = [
            'id' => $profile->id,
            'points_balance' => $profile->points_balance,
            'points_earned' => $profile->points_earned,
            'points_redeemed' => $profile->points_redeemed,
            'tier' => $profile->tier,
            'tier_label' => $this->getTierLabel($profile->tier),
            'total_spent' => $profile->total_spent,
            'total_visits' => $profile->total_visits,
            'next_tier' => $this->getNextTier($profile->tier),
            'next_tier_threshold' => $this->getNextTierThreshold($profile->tier),
        ];
    }

    private function getTierLabel(string $tier): string
    {
        return match ($tier) {
            'bronze' => 'Бронза',
            'silver' => 'Серебро',
            'gold' => 'Золото',
            'platinum' => 'Платина',
            default => 'Без уровня',
        };
    }

    private function getNextTier(string $currentTier): ?string
    {
        return match ($currentTier) {
            'bronze' => 'Серебро',
            'silver' => 'Золото',
            'gold' => 'Платина',
            'platinum' => null,
            default => 'Бронза',
        };
    }

    private function getNextTierThreshold(string $currentTier): ?float
    {
        return match ($currentTier) {
            'bronze' => 10000,
            'silver' => 50000,
            'gold' => 150000,
            'platinum' => null,
            default => 10000,
        };
    }

    private function loadRecentAppointments(): void
    {
        $limit = $this->showAllAppointments ? 50 : 5;

        $this->recentAppointments = AppointmentModel::where('client_id', $this->userId)
            ->where('venue_id', $this->venueId)
            ->with(['master', 'service', 'photos'])
            ->orderBy('start_time', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($appointment) => [
                'id' => $appointment->id,
                'date' => $appointment->start_time->format('d.m.Y H:i'),
                'service_name' => $appointment->service->name,
                'master_name' => $appointment->master->full_name,
                'status' => $appointment->status,
                'status_label' => $this->getStatusLabel($appointment->status),
                'price' => $appointment->final_price,
                'has_photos' => $appointment->photos->isNotEmpty(),
                'photo_count' => $appointment->photos->count(),
            ])
            ->toArray();
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Ожидает',
            'confirmed' => 'Подтверждена',
            'in_progress' => 'В процессе',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена',
            'no_show' => 'Неявка',
            'paid' => 'Оплачена',
            default => 'Неизвестно',
        };
    }

    private function loadPhotos(): void
    {
        $appointmentIds = AppointmentModel::where('client_id', $this->userId)
            ->where('venue_id', $this->venueId)
            ->pluck('id');

        $this->photos = AppointmentPhotoModel::whereIn('appointment_id', $appointmentIds)
            ->where('is_public', true)
            ->with(['appointment.service'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($photo) => [
                'id' => $photo->id,
                'image_path' => $photo->image_path,
                'thumbnail_path' => $photo->thumbnail_path,
                'type' => $photo->type,
                'appointment_date' => $photo->appointment->start_time->format('d.m.Y'),
                'service_name' => $photo->appointment->service->name,
            ])
            ->toArray();
    }

    public function toggleShowAll(): void
    {
        $this->showAllAppointments = !$this->showAllAppointments;
        $this->loadRecentAppointments();
    }

    public function render()
    {
        return view('beauty-masters::livewire.user-card');
    }
}
