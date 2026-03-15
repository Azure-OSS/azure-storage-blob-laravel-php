<?php

declare(strict_types=1);

namespace AzureOss\Storage\BlobLaravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

/**
 * @internal
 */
final class AzureStorageBlobServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Storage::extend('azure-storage-blob', function (Application $app, array $config): FilesystemAdapter {
            /** @var array<string, mixed> $config */
            AzureStorageBlobDiskConfig::validate($config);

            return new AzureStorageBlobAdapter($config);
        });
    }
}
