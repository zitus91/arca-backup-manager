<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup {{ ucfirst($status) }}: {{ $backupJob->name }}</title>
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
        .header-success { background: #16a34a; }
        .header-failed  { background: #dc2626; }
        .header {
            padding: 28px 32px;
        }
        .header-inner {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .header-icon {
            font-size: 32px;
            line-height: 1;
            flex-shrink: 0;
        }
        .header h1 {
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
            letter-spacing: -0.01em;
        }
        .header p {
            color: rgba(255,255,255,0.8);
            font-size: 13px;
            margin: 0;
        }
        .body {
            padding: 28px 32px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table tr {
            border-bottom: 1px solid #f0f0f0;
        }
        .info-table tr:last-child {
            border-bottom: none;
        }
        .info-table td {
            padding: 10px 0;
            font-size: 13px;
            line-height: 1.5;
            vertical-align: top;
        }
        .info-table td.label {
            font-weight: 600;
            color: #52525b;
            width: 40%;
            padding-right: 12px;
        }
        .info-table td.value {
            color: #18181b;
        }
        .badge-success {
            display: inline-block;
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 2px 10px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .badge-failed {
            display: inline-block;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 2px 10px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .error-block {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin-top: 4px;
            font-size: 12px;
            color: #b91c1c;
            font-family: monospace;
            word-break: break-word;
            line-height: 1.6;
        }
        .footer {
            padding: 18px 32px;
            border-top: 1px solid #f4f4f5;
            background: #fafafa;
        }
        .footer p {
            margin: 0;
            font-size: 11px;
            color: #a1a1aa;
            text-align: center;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $status === 'success' ? 'header-success' : 'header-failed' }}">
            <div class="header-inner">
                <span class="header-icon">{{ $status === 'success' ? '✅' : '❌' }}</span>
                <div>
                    <h1>{{ $backupJob->name }}</h1>
                    <p>{{ $status === 'success' ? 'Backup completed successfully' : 'Backup failed' }}</p>
                </div>
            </div>
        </div>

        <div class="body">
            <table class="info-table">
                <tr>
                    <td class="label">Status</td>
                    <td class="value">
                        <span class="{{ $status === 'success' ? 'badge-success' : 'badge-failed' }}">
                            {{ strtoupper($status) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Source</td>
                    <td class="value">{{ $backupJob->source->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Destination</td>
                    <td class="value">{{ ($backupJob->destination->name ?? '—') . ' (' . ($backupJob->destination->type ?? '') . ')' }}</td>
                </tr>
                <tr>
                    <td class="label">Started</td>
                    <td class="value">{{ $log->started_at ? $log->started_at->format('d/m/Y H:i:s') : '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Finished</td>
                    <td class="value">{{ $log->finished_at ? $log->finished_at->format('d/m/Y H:i:s') : '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Duration</td>
                    <td class="value">{{ $log->formatted_duration ?? '—' }}</td>
                </tr>
                @if ($status === 'success')
                <tr>
                    <td class="label">File</td>
                    <td class="value">{{ $log->file_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Size</td>
                    <td class="value">{{ $log->formatted_size ?? '—' }}</td>
                </tr>
                @endif
            </table>

            @if ($status !== 'success' && $log->error_message)
                <div class="error-block">{{ $log->error_message }}</div>
            @endif
        </div>

        <div class="footer">
            <p>Automated notification sent by <strong>Backup Manager</strong></p>
        </div>
    </div>
</body>
</html>
