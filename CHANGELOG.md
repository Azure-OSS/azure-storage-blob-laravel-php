# Changelog

## Unreleased

### Changed

- The package now requires `azure-oss/storage-blob-flysystem:^2.2`.

## 2.1.0

Changes since `2.0.0`.

### Added

- Added ETag- and lease-aware `put()` operations through the `conditions` option.
- Added support for Laravel's `url` disk option when generating public blob URLs through a custom domain or Azure Front Door.
- Added support for Laravel's `temporary_url` disk option for serving signed download and upload URLs through a custom origin while preserving the Blob path and SAS query string.
