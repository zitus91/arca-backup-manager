<?php

use App\Services\Backup\FilesystemBackupService;
use Illuminate\Support\Facades\Process;

function runArchive(FilesystemBackupService $service, string $cmd, string $prefix): ?string
{
    return (fn () => $this->runArchive($cmd, $prefix))->call($service);
}

it('treats tar exit code 1 as a warning', function () {
    Process::fake([
        '*' => Process::result(output: '', errorOutput: 'tar: foo.jpg: file changed as we read it', exitCode: 1),
    ]);

    $warnings = runArchive(new FilesystemBackupService(), 'tar -czf /tmp/a.tar.gz -C /tmp x', 'archiving failed');

    expect($warnings)->toBe('tar: foo.jpg: file changed as we read it');
});

it('fails on tar exit codes other than 1', function () {
    Process::fake([
        '*' => Process::result(output: '', errorOutput: 'tar: cannot write', exitCode: 2),
    ]);

    runArchive(new FilesystemBackupService(), 'tar -czf /tmp/a.tar.gz -C /tmp x', 'archiving failed');
})->throws(RuntimeException::class, 'archiving failed: tar: cannot write');

it('fails on zip exit code 1', function () {
    Process::fake([
        '*' => Process::result(output: '', errorOutput: 'zip error', exitCode: 1),
    ]);

    runArchive(new FilesystemBackupService(), 'cd /tmp && zip -r /tmp/a.zip x', 'archiving failed');
})->throws(RuntimeException::class);
