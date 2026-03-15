<?php

declare(strict_types=1);

namespace AzureOss\Storage\BlobLaravel;

use AzureOss\Identity\AzureAuthorityHosts;
use AzureOss\Identity\ClientCertificateCredential;
use AzureOss\Identity\ClientCertificateCredentialOptions;
use AzureOss\Identity\ClientSecretCredential;
use AzureOss\Identity\ManagedIdentityCredential;
use AzureOss\Identity\ManagedIdentityCredentialOptions;
use AzureOss\Identity\TokenCredential;
use AzureOss\Identity\TokenCredentialOptions;
use AzureOss\Identity\WorkloadIdentityCredential;
use AzureOss\Identity\WorkloadIdentityCredentialOptions;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\BlobFlysystem\AzureBlobStorageAdapter;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use Psr\Http\Message\UriInterface;

/**
 * @internal
 *
 * @property AzureBlobStorageAdapter $adapter
 */
final class AzureStorageBlobAdapter extends FilesystemAdapter
{
    /**
     * Whether the configuration of this adapter allows temporary URLs.
     */
    public bool $canProvideTemporaryUrls;

    /**
     * @param  array{
     *     connection_string?: string,
     *     endpoint?: string,
     *     account_name?: string,
     *     endpoint_suffix?: string,
     *     credential?: string,
     *     account_key?: string,
     *     authority_host?: string,
     *     tenant_id?: string,
     *     client_id?: string,
     *     client_secret?: string,
     *     client_certificate_path?: string,
     *     client_certificate_password?: string,
     *     federated_token_file?: string,
     *     container: string,
     *     prefix?: string,
     *     root?: string,
     *     is_public_container?: bool
     * }  $config
     */
    public function __construct(array $config)
    {
        $container = $config['container'];
        $prefix = $config['prefix'] ?? null;
        $root = $config['root'] ?? null;
        $isPublicContainer = $config['is_public_container'] ?? false;

        $serviceClient = self::createBlobServiceClient($config);
        $containerClient = $serviceClient->getContainerClient($container);
        $this->canProvideTemporaryUrls = $containerClient->canGenerateSasUri();
        $adapter = new AzureBlobStorageAdapter(
            $containerClient,
            $prefix ?? $root ?? '',
            isPublicContainer: $isPublicContainer,
        );

        parent::__construct(
            new Filesystem($adapter, $config),
            $adapter,
            $config,
        );
    }

    /**
     * @param  array{
     *     connection_string?: string,
     *     endpoint?: string,
     *     account_name?: string,
     *     endpoint_suffix?: string,
     *     credential?: string,
     *     account_key?: string,
     *     authority_host?: string,
     *     tenant_id?: string,
     *     client_id?: string,
     *     client_secret?: string,
     *     client_certificate_path?: string,
     *     client_certificate_password?: string,
     *     federated_token_file?: string,
     *     container: string,
     *     prefix?: string,
     *     root?: string,
     *     is_public_container?: bool
     * }  $config
     */
    private static function createBlobServiceClient(array $config): BlobServiceClient
    {
        $connectionString = $config['connection_string'] ?? null;
        if ($connectionString !== null && $connectionString !== '') {
            $hasEndpointOrAccountName = isset($config['endpoint']) || isset($config['account_name']);
            $hasAnyCredentialConfig =
                isset($config['credential'])
                || isset($config['account_key'])
                || isset($config['authority_host'])
                || isset($config['tenant_id'])
                || isset($config['client_id'])
                || isset($config['client_secret'])
                || isset($config['client_certificate_path'])
                || isset($config['client_certificate_password'])
                || isset($config['federated_token_file']);

            if ($hasEndpointOrAccountName || $hasAnyCredentialConfig) {
                throw new \InvalidArgumentException('Cannot use both [connection_string] and token-based credentials in the disk configuration.');
            }

            return BlobServiceClient::fromConnectionString($connectionString);
        }

        if (! isset($config['endpoint']) && ! isset($config['account_name'])) {
            throw new \InvalidArgumentException(
                'Either [connection_string] or [endpoint/account_name] must be provided in the disk configuration.'
            );
        }

        $uri = self::buildBlobEndpointUri($config);
        $credential = self::createCredential($config);

        return new BlobServiceClient($uri, $credential);
    }

    /**
     * @param  array{
     *     credential?: string,
     *     account_name?: string,
     *     account_key?: string,
     *     authority_host?: string,
     *     tenant_id?: string,
     *     client_id?: string,
     *     client_secret?: string,
     *     client_certificate_path?: string,
     *     client_certificate_password?: string,
     *     federated_token_file?: string
     * }  $config
     */
    private static function createCredential(array $config): StorageSharedKeyCredential|TokenCredential
    {
        $authorityHost = $config['authority_host'] ?? null;
        $credentialType = $config['credential'] ?? null;

        $tenantId = $config['tenant_id'] ?? null;
        $clientId = $config['client_id'] ?? null;
        $clientSecret = $config['client_secret'] ?? null;

        if ($credentialType === null) {
            if ($tenantId !== null && $clientId !== null && $clientSecret !== null) {
                return self::createClientSecretCredential($config, $authorityHost);
            }

            throw new \InvalidArgumentException('The [credential] must be provided in the disk configuration when not using [connection_string].');
        }

        return match (strtolower($credentialType)) {
            'shared_key' => self::createSharedKeyCredential($config),
            'client_secret' => self::createClientSecretCredential($config, $authorityHost),
            'client_certificate' => self::createClientCertificateCredential($config, $authorityHost),
            'workload_identity' => self::createWorkloadIdentityCredential($config, $authorityHost),
            'managed_identity' => self::createManagedIdentityCredential($config, $authorityHost),
            default => throw new \InvalidArgumentException(
                'Unsupported [credential]. Supported values: [shared_key, client_secret, client_certificate, workload_identity, managed_identity].',
            ),
        };
    }

    /**
     * @param  array{account_name?: string, account_key?: string}  $config
     */
    private static function createSharedKeyCredential(array $config): StorageSharedKeyCredential
    {
        $accountName = $config['account_name'] ?? null;
        $accountKey = $config['account_key'] ?? null;

        if ($accountName === null || $accountKey === null) {
            throw new \InvalidArgumentException('The [shared_key] credential requires [account_name] and [account_key].');
        }

        return new StorageSharedKeyCredential($accountName, $accountKey);
    }

    /**
     * @param  array{tenant_id?: string, client_id?: string, client_secret?: string}  $config
     */
    private static function createClientSecretCredential(array $config, ?string $authorityHost): ClientSecretCredential
    {
        $tenantId = $config['tenant_id'] ?? null;
        $clientId = $config['client_id'] ?? null;
        $clientSecret = $config['client_secret'] ?? null;

        if ($tenantId === null || $clientId === null || $clientSecret === null) {
            throw new \InvalidArgumentException('The [client_secret] credential requires [tenant_id], [client_id], and [client_secret].');
        }

        return new ClientSecretCredential(
            $tenantId,
            $clientId,
            $clientSecret,
            new TokenCredentialOptions(authorityHost: $authorityHost ?? AzureAuthorityHosts::AZURE_PUBLIC_CLOUD),
        );
    }

    /**
     * @param  array{tenant_id?: string, client_id?: string, client_certificate_path?: string, client_certificate_password?: string}  $config
     */
    private static function createClientCertificateCredential(array $config, ?string $authorityHost): ClientCertificateCredential
    {
        $tenantId = $config['tenant_id'] ?? null;
        $clientId = $config['client_id'] ?? null;
        $certificatePath = $config['client_certificate_path'] ?? null;
        $certificatePassword = $config['client_certificate_password'] ?? null;

        if ($tenantId === null || $clientId === null || $certificatePath === null) {
            throw new \InvalidArgumentException('The [client_certificate] credential requires [tenant_id], [client_id], and [client_certificate_path].');
        }

        return new ClientCertificateCredential(
            $tenantId,
            $clientId,
            $certificatePath,
            $certificatePassword,
            new ClientCertificateCredentialOptions(authorityHost: $authorityHost ?? AzureAuthorityHosts::AZURE_PUBLIC_CLOUD),
        );
    }

    /**
     * @param  array{tenant_id?: string, client_id?: string, federated_token_file?: string}  $config
     */
    private static function createWorkloadIdentityCredential(array $config, ?string $authorityHost): WorkloadIdentityCredential
    {
        $tenantId = $config['tenant_id'] ?? null;
        $clientId = $config['client_id'] ?? null;
        $tokenFilePath = $config['federated_token_file'] ?? null;

        return new WorkloadIdentityCredential(
            new WorkloadIdentityCredentialOptions(
                authorityHost: $authorityHost ?? AzureAuthorityHosts::AZURE_PUBLIC_CLOUD,
                clientId: $clientId,
                tenantId: $tenantId,
                tokenFilePath: $tokenFilePath,
            )
        );
    }

    /**
     * @param  array{client_id?: string}  $config
     */
    private static function createManagedIdentityCredential(array $config, ?string $authorityHost): ManagedIdentityCredential
    {
        $clientId = $config['client_id'] ?? null;

        return new ManagedIdentityCredential(
            new ManagedIdentityCredentialOptions(
                authorityHost: $authorityHost ?? AzureAuthorityHosts::AZURE_PUBLIC_CLOUD,
                clientId: $clientId,
            )
        );
    }

    /**
     * @param  array{endpoint?: string, account_name?: string, endpoint_suffix?: string}  $config
     */
    private static function buildBlobEndpointUri(array $config): UriInterface
    {
        $endpoint = $config['endpoint'] ?? null;
        if ($endpoint !== null && $endpoint !== '') {
            return new Uri(rtrim($endpoint, '/').'/');
        }

        $accountName = $config['account_name'] ?? null;
        if ($accountName === null || $accountName === '') {
            throw new \InvalidArgumentException('Either [endpoint] or [account_name] must be provided for token-based credentials.');
        }

        $endpointSuffix = $config['endpoint_suffix'] ?? 'core.windows.net';
        $endpoint = sprintf('https://%s.blob.%s', $accountName, $endpointSuffix);

        return new Uri($endpoint.'/');
    }

    public function url($path)
    {
        return $this->adapter->publicUrl($path, new Config);
    }

    /**
     * Determine if temporary URLs can be generated.
     *
     * @return bool
     */
    public function providesTemporaryUrls()
    {
        return $this->canProvideTemporaryUrls;
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @return string
     */
    /** @phpstan-ignore-next-line */
    public function temporaryUrl($path, $expiration, array $options = [])
    {
        return $this->adapter->temporaryUrl(
            $path,
            $expiration,
            new Config(array_merge(['permissions' => 'r'], $options)),
        );
    }

    /**
     * Get a temporary upload URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @return array{url: string, headers: array<string, string>}
     */
    /** @phpstan-ignore-next-line */
    public function temporaryUploadUrl($path, $expiration, array $options = [])
    {
        $url = $this->adapter->temporaryUrl(
            $path,
            $expiration,
            new Config(array_merge(['permissions' => 'cw'], $options)),
        );
        $contentType = isset($options['content-type']) && is_string($options['content-type'])
            ? $options['content-type']
            : 'application/octet-stream';

        return [
            'url' => $url,
            'headers' => [
                'x-ms-blob-type' => 'BlockBlob',
                'Content-Type' => $contentType,
            ],
        ];
    }
}
