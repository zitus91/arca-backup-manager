<?php

namespace App\Events\Backup;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupJobStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $jobId,
        public int $logId,
        public string $jobName,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('backup-jobs'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'backup.started';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'log_id' => $this->logId,
            'job_name' => $this->jobName,
            'status' => 'running',
        ];
    }
}
