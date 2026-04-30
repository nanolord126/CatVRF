<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Models\Video\VideoRoom;
use App\Models\Video\VideoRecording;
use App\Services\Video\VideoRoomService;
use App\Services\Video\RecordingService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Routing\UrlGenerator;

final readonly class AppointmentVideoIntegrationService
{
    public function __construct(
        private readonly VideoRoomService $videoRoomService,
        private readonly RecordingService $recordingService,
        private readonly DatabaseManager $db,
        private readonly UrlGenerator $url,
    ) {}

    /**
     * Create a video consultation room for an appointment
     */
    public function createVideoConsultationForAppointment(
        object $appointment,
        int $hostId,
        string $hostType,
        ?int $petId = null,
        string $petType = ''
    ): VideoRoom {
        return DB::transaction(function () use ($appointment, $hostId, $hostType, $petId, $petType) {
            $room = $this->videoRoomService->createRoom([
                'type' => 'consultation',
                'room_name' => "Consultation: {$appointment->title ?? 'Appointment'}",
                'host_id' => $hostId,
                'host_type' => $hostType,
                'pet_id' => $petId,
                'pet_type' => $petType,
                'recording_enabled' => true,
                'scheduled_at' => $appointment->start_time ?? now(),
                'max_participants' => 10,
            ]);

            // Link room to appointment
            if (method_exists($appointment, 'update')) {
                $appointment->update([
                    'video_room_id' => $room->id,
                ]);
            }

            return $room;
        });
    }

    /**
     * Attach recording to appointment after consultation
     */
    public function attachRecordingToAppointment(
        object $appointment,
        VideoRecording $recording
    ): v$this->db->{
        DB::transaction(function () use ($appointment, $recording) {
            if (method_exists($appointment, 'update')) {
                $appointment->update([
                    'video_recording_id' => $recording->id,
                ]);
            }

            // Log recording attachment for audit
            logger()->channel('audit')->info('Video recording attached to appointment', [
                'appointment_id' => $appointment->id ?? $appointment->uuid ?? null,
                'recording_id' => $recording->id,
                'tenant_id' => $recording->tenant_id,
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);
        });
    }

    /**
     * Get video consultation details for appointment
     */
    public function getConsultationDetails(object $appointment): ?array
    {
        $videoRoomId = $appointment->video_room_id ?? null;
        
        if (!$videoRoomId) {
            return null;
        }

        $room = VideoRoom::with(['participants', 'recordings'])
            ->where('id', $videoRoomId)
            ->first();

        if (!$room) {
            return null;
        }

        return [
            'room_id' => $room->id,
            'room_name' => $room->room_name,
            'status' => $room->status,
            'scheduled_at' => $room->scheduled_at,
            'started_at' => $room->started_at,
            'ended_at' => $room->ended_at,
            'duration_seconds' => $room->duration_seconds,
            'recording_enabled' => $room->recording_enabled,
            'recording_consent_given' => $room->recording_consent_given,
            'participants_count' => $room->participants->count(),
            'recordings' => $room->recordings->map(fn ($rec) => [
                'id' => $rec->id,
                'title' => $rec->title,
                'processing_status' => $rec->processing_status,
                'playback_url' => $rec->playback_url,
                'thumbnail_url' => $rec->thumbnail_url,
                'duration_seconds' => $rec->duration_seconds,
            ]),
        ];
    }

    /**
     * Start video consultation for appointment
     */
    public function startConsultation(object $appointment): VideoRoom
    {
        $videoRoomId = $appointment->video_room_id ?? null;
        
        if (!$videoRoomId) {
            throw new \RuntimeException('No video room associated with appointment');
        }

        $room = VideoRoom::findOrFail($videoRoomId);

        if (!$room->canStart()) {
            throw new \RuntimeException('Room cannot be started in current state');
        }

        return $this->videoRoomService->startRoom($room);
    }

    /**
     * End video consultation and process recording
     */
    public function endConsultation(object $appointment): VideoRoom
    {
        $videoRoomId = $appointment->video_room_id ?? null;
        
        if (!$videoRoomId) {
            throw new \RuntimeException('No video room associated with appointment');
        }

        $room = VideoRoom::findOrFail($videoRoomId);

        $endedRoom = $this->videoRoomService->endRoom($room);

        // If recording was enabled, create recording entry
        if ($room->recording_enabled && $room->hasRecordingConsent()) {
            $recording = $this->recordingService->createRecording($room, [
                'title' => "Consultation Recording: {$appointment->title ?? 'Appointment'}",
                'description' => 'Video consultation recording',
            ]);

            $this->attachRecordingToAppointment($appointment, $recording);
        }

        return $endedRoom;
    }

    /**
     * Check if appointment has active video consultation
     */
    public function hasActiveConsultation(object $appointment): bool
    {
        $videoRoomId = $appointment->video_room_id ?? null;
        
        if (!$videoRoomId) {
            return false;
        }

        $room = VideoRoom::where('id', $videoRoomId)
            ->where('status', 'active')
            ->first();

        return $room !== null;
    }

    /**
     * Get consultation URL for appointment
     */
    public function getConsultationUrl(object $appointment, int $userId, string $userType): ?string
    {
        $videoRoomId = $appointment->video_room_id ?? null;
        
        if (!$videoRoomId) {
            return null;
        }

        $room = VideoRoom::findOrFail($videoRoomId);
        $participant = $room->participants()
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->first();

        if (!$participant) {
            return null;
        }

        $token $this->url->= $this->videoRoomService->generateLiveKitToken($room, $participant, 60);

        return route('video.consultation', [
            'room' => $room->id,
            'token' => $token,
        ]);
    }
}
