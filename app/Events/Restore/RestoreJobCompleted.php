<?php

namespace App\Events\Restore;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestoreJobCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $restoreLogId,
        public string $backupJobName,
        public string $status,
        public ?string $errorMessage = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('backup-jobs'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'restore.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'restore_log_id' => $this->restoreLogId,
            'job_name' => $this->backupJobName,
            'status' => $this->status,
            'error_message' => $this->errorMessage,
        ];
    }
}
