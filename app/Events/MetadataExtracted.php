<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MetadataExtracted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;

    public $fileId;

    public $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $fileId, $metadata)
    {
        $this->userId = $userId;
        $this->fileId = $fileId;

        // Clean metadata of any debug fields starting with '_' to avoid Pusher payload size limits
        if (is_array($metadata)) {
            if (isset($metadata['metadata']) && is_array($metadata['metadata'])) {
                $metadata['metadata'] = array_filter($metadata['metadata'], function ($key) {
                    return ! str_starts_with($key, '_');
                }, ARRAY_FILTER_USE_KEY);
            } else {
                $metadata = array_filter($metadata, function ($key) {
                    return ! str_starts_with($key, '_');
                }, ARRAY_FILTER_USE_KEY);
            }
        }

        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->userId),
        ];
    }
}
