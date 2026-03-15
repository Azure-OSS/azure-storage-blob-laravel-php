# Azure Storage Blob filesystem driver for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/azure-oss/storage-blob-laravel.svg)](https://packagist.org/packages/azure-oss/storage-blob-laravel)
[![Packagist Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-blob-laravel)](https://packagist.org/packages/azure-oss/storage-blob-laravel)

Community-driven PHP SDKs for Azure, because Microsoft won't.

In November 2023, Microsoft officially archived their [Azure SDK for PHP](https://github.com/Azure/azure-sdk-for-php) and stopped maintaining PHP integrations for most Azure services. No migration path, no replacement — just a repository marked read-only.

We picked up where they left off.

<img src="https://azure-oss.github.io/img/logo.svg" width="150" alt="Screenshot">

Our other packages:

- **[azure-oss/storage](https://packagist.org/packages/azure-oss/storage)** – Azure Blob Storage SDK  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage)

- **[azure-oss/storage-blob-flysystem](https://packagist.org/packages/azure-oss/storage-blob-flysystem)** – Flysystem adapter  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-blob-flysystem)

## Install

```shell
composer require azure-oss/storage-blob-laravel
```

## Documentation

You can read the documentation [here](https://azure-oss.github.io/category/storage-blob-laravel).

## Quickstart

```php
# config/filesystems.php


'azure' => [
    'driver' => 'azure-storage-blob',
    'connection_string' => env('AZURE_STORAGE_CONNECTION_STRING'),
    'container' => env('AZURE_STORAGE_CONTAINER'),
],
```

## Authentication

Besides shared key via connection string, this driver supports additional authentication methods (like Entra ID / token-based credentials, managed identity, workload identity, and shared key via account key). See the docs for configuration examples: https://azure-oss.github.io/category/storage-blob-laravel/installation

```php
use Illuminate\Support\Facades\Storage;

$disk = Storage::disk('azure');

// Write
$disk->put('docs/hello.txt', 'Hello from Laravel');

// Read
$content = $disk->get('docs/hello.txt');

// Exists
$exists = $disk->exists('docs/hello.txt');

// Copy / move
$disk->copy('docs/hello.txt', 'docs/hello-copy.txt');
$disk->move('docs/hello-copy.txt', 'docs/hello-moved.txt');

// List
$files = $disk->allFiles('docs');

// Delete
$disk->delete('docs/hello-moved.txt');
```

## License

This project is released under the MIT License. See [LICENSE](https://github.com/Azure-OSS/azure-storage-php-monorepo/blob/02759360186be8d2d04bd1e9b2aba3839b6d39dc/LICENSE) for details.
