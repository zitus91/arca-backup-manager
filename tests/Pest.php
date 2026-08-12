<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(
    Tests\TestCase::class,
    RefreshDatabase::class,
)->in('Feature');

uses(
    Tests\TestCase::class,
    RefreshDatabase::class,
)->in('Unit');

/**
 * A dump result whose file exists on disk: the backup job refuses to publish an
 * artifact it cannot read.
 */
function fakeDumpFile(string $fileName, int $size = 2048): array
{
    $path = sys_get_temp_dir().'/pbj_dump_'.uniqid().'/'.$fileName;
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, gzencode(str_repeat('dump', 32)));

    return ['file_name' => $fileName, 'file_path' => $path, 'file_size' => $size];
}
