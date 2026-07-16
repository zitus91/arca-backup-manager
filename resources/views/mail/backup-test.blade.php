<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('backup-job.test_email_subject', ['name' => $jobName]) }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f4f4f5;
            margin: 0;
            padding: 32px 16px;
            color: #18181b;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header {
            background: #4f46e5;
            padding: 28px 32px;
        }
        .header h1 {
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.01em;
        }
        .header p {
            color: rgba(255,255,255,0.75);
            font-size: 13px;
            margin: 6px 0 0;
        }
        .body {
            padding: 32px;
        }
        .check-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            width: 100%;
            box-sizing: border-box;
        }
        .check-badge .icon {
            font-size: 20px;
            line-height: 1;
        }
        .check-badge .text {
            color: #15803d;
            font-size: 14px;
            font-weight: 600;
        }
        .info-block {
            background: #fafafa;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .info-block p {
            margin: 0 0 8px;
            font-size: 13px;
            color: #52525b;
            line-height: 1.5;
        }
        .info-block p:last-child {
            margin-bottom: 0;
        }
        .info-block .label {
            font-weight: 600;
            color: #27272a;
        }
        .footer {
            padding: 20px 32px;
            border-top: 1px solid #f4f4f5;
            background: #fafafa;
        }
        .footer p {
            margin: 0;
            font-size: 12px;
            color: #a1a1aa;
            text-align: center;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Arca</h1>
            <p>{{ __('backup-job.test_email_header_sub') }}</p>
        </div>

        <div class="body">
            <div class="check-badge">
                <span class="icon">✅</span>
                <span class="text">{{ __('backup-job.test_email_check_ok') }}</span>
            </div>

            <div class="info-block">
                <p>
                    <span class="label">{{ __('backup-job.test_email_job_label') }}:</span>
                    {{ $jobName }}
                </p>
                <p>
                    <span class="label">{{ __('backup-job.test_email_recipient_label') }}:</span>
                    {{ $recipientEmail }}
                </p>
                <p>
                    <span class="label">{{ __('backup-job.test_email_sent_at_label') }}:</span>
                    {{ now()->format('d/m/Y H:i:s') }}
                </p>
            </div>

            <p style="font-size: 13px; color: #52525b; line-height: 1.7; margin: 0;">
                {{ __('backup-job.test_email_body') }}
            </p>
        </div>

        <div class="footer">
            <p>
                {{ __('backup-job.test_email_footer') }}<br>
                <strong>Arca</strong>
            </p>
        </div>
    </div>
</body>
</html>
