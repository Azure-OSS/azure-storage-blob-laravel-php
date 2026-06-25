# Azure Storage Blob filesystem driver for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/azure-oss/storage-blob-laravel.svg)](https://packagist.org/packages/azure-oss/storage-blob-laravel)
[![Packagist Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-blob-laravel)](https://packagist.org/packages/azure-oss/storage-blob-laravel)

Community-driven PHP SDKs for Azure, because Microsoft won't.

In November 2023, Microsoft officially archived their [Azure SDK for PHP](https://github.com/Azure/azure-sdk-for-php) and stopped maintaining PHP integrations for most Azure services. No migration path, no replacement — just a repository marked read-only.

We picked up where they left off.

<img src="https://azure-oss.github.io/img/logo.svg" width="150" alt="Screenshot">

Our other packages:

- **[azure-oss/storage](https://packagist.org/packages/azure-oss/storage)** – Meta package with Blob + Queue SDKs  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage)

- **[azure-oss/storage-blob](https://packagist.org/packages/azure-oss/storage-blob)** – Azure Blob Storage SDK  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-blob)

- **[azure-oss/storage-blob-flysystem](https://packagist.org/packages/azure-oss/storage-blob-flysystem)** – Flysystem adapter  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-blob-flysystem)

- **[azure-oss/storage-queue](https://packagist.org/packages/azure-oss/storage-queue)** – Azure Storage Queue SDK  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-queue)

- **[azure-oss/storage-queue-laravel](https://packagist.org/packages/azure-oss/storage-queue-laravel)** – Laravel Queue connector  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-queue-laravel)

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

Besides shared key via connection string, this driver supports additional authentication methods (like Entra ID / token-based credentials, managed identity, workload identity, and shared key via account key). See the docs for configuration examples: https://azure-oss.github.io/category/storage-blob-laravel/installation

## License

This project is released under the MIT License. See [LICENSE](https://github.com/Azure-OSS/azure-storage-monorepo/blob/main/LICENSE) for details.
