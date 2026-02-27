<?php

namespace App\Events\Restore;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestoreJobStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $restoreLogId,
        public int $backupLogId,
        public string $backupJobName,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('backup-jobs'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'restore.started';
    }

    public function broadcastWith(): array
    {
        return [
            'restore_log_id' => $this->restoreLogId,
            'backup_log_id' => $this->backupLogId,
            'job_name' => $this->backupJobName,
            'status' => 'running',
        ];
    }
}
