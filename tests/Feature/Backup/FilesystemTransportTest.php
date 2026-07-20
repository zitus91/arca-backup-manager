<?php

use App\Models\BackupHost;

it('defaults filesystem transport to ssh', function () {
    $host = BackupHost::factory()->withFilesystem()->create();
    expect($host->filesystemTransport())->toBe('ssh');
    expect($host->filesystemFtpConfig())->toBeNull();
});

it('reports ftp transport and returns its ftp config', function () {
    $host = BackupHost::factory()->withFtpFilesystem()->create();
    expect($host->filesystemTransport())->toBe('ftp');
    expect($host->filesystemFtpConfig())->toMatchArray(['host' => $host->config['filesystem']['ftp']['host']]);
    expect($host->offers('filesystem'))->toBeTrue();
});

it('treats a legacy filesystem service (no transport key) as ssh', function () {
    $host = BackupHost::factory()->create(['config' => ['filesystem' => ['enabled' => true]]]);
    expect($host->filesystemTransport())->toBe('ssh');
});
