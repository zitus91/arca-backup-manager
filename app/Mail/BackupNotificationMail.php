<?php

namespace App\Mail;

use App\Models\BackupJob;
use App\Models\BackupLog;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupNotificationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly BackupJob $backupJob,
        public readonly BackupLog $log,
        public readonly string $status,
    ) {}

    public function envelope(): Envelope
    {
        $emoji = $this->status === 'success' ? '✅' : '❌';
        $label = $this->status === 'success' ? 'Success' : 'Failed';

        return new Envelope(
            subject: "[Backup Manager] {$emoji} {$label}: {$this->backupJob->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.backup-notification',
        );
    }
}
