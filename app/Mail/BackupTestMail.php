<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $jobName,
        public readonly string $recipientEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Backup Manager] ' . __('backup-job.test_email_subject', ['name' => $this->jobName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.backup-test',
        );
    }
}
