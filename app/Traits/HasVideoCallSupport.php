<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Video\VideoRoom;
use App\Services\Video\VideoRoomService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasVideoCallSupport
{
    /**
     * Get video rooms for this model
     */
    public function videoRooms(): MorphMany
    {
        return $this->morphMany(VideoRoom::class, 'host');
    }

    /**
     * Create a video consultation room
     */
    public function createVideoConsultation(array $data = []): VideoRoom
    {
        $videoRoomService = app(VideoRoomService::class);

        return $videoRoomService->createRoom(array_merge($data, [
            'type' => 'consultation',
            'host_id' => $this->id,
            'host_type' => get_class($this),
        ]));
    }

    /**
     * Create a live streaming room
     */
    public function createLiveStream(array $data = []): VideoRoom
    {
        $videoRoomService = app(VideoRoomService::class);

        return $videoRoomService->createRoom(array_merge($data, [
            'type' => 'grooming_demo',
            'host_id' => $this->id,
            'host_type' => get_class($this),
            'is_public' => true,
        ]));
    }

    /**
     * Get active video rooms
     */
    public function getActiveVideoRooms()
    {
        return $this->videoRooms()->active()->get();
    }

    /**
     * Get scheduled video rooms
     */
    public function getScheduledVideoRooms()
    {
        return $this->videoRooms()->scheduled()->get();
    }
}
