<?php

declare(strict_types=1);

namespace AzureOss\Storage\BlobLaravel;

/**
 * @internal
 */
final class AzureStorageBlobDiskConfig
{
    private function __construct() {}

    /**
     * @param  array<string, mixed>  $config
     *
     * @phpstan-assert array{
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
     *     url?: string,
     *     temporary_url?: string,
     *     is_public_container?: bool,
     *     timeout?: int,
     *     connect_timeout?: int,
     *     verify_ssl?: bool
     * } $config
     */
    public static function validate(array &$config): void
    {
        self::assertString($config, 'container', required: true);
        self::assertString($config, 'prefix');
        self::assertString($config, 'root');
        self::assertString($config, 'url');
        self::assertString($config, 'temporary_url');
        self::assertString($config, 'credential');
        self::assertString($config, 'account_key');
        self::assertString($config, 'authority_host');
        self::assertString($config, 'tenant_id');
        self::assertString($config, 'client_id');
        self::assertString($config, 'client_secret');
        self::assertString($config, 'client_certificate_path');
        self::assertString($config, 'client_certificate_password');
        self::assertString($config, 'federated_token_file');
        self::assertString($config, 'endpoint');
        self::assertString($config, 'endpoint_suffix');
        self::assertString($config, 'account_name');
        self::assertString($config, 'connection_string');
        self::assertBool($config, 'is_public_container');
        self::assertInt($config, 'timeout');
        self::assertInt($config, 'connect_timeout');
        self::assertBool($config, 'verify_ssl');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function assertString(array $config, string $key, bool $required = false): void
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            if ($required) {
                throw new \InvalidArgumentException("The [{$key}] must be a string in the disk configuration.");
            }

            return;
        }

        if (! is_string($config[$key])) {
            throw new \InvalidArgumentException("The [{$key}] must be a string in the disk configuration.");
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function assertBool(array $config, string $key, bool $required = false): void
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            if ($required) {
                throw new \InvalidArgumentException("The [{$key}] must be a boolean in the disk configuration.");
            }

            return;
        }

        if (! is_bool($config[$key])) {
            throw new \InvalidArgumentException("The [{$key}] must be a boolean in the disk configuration.");
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function assertInt(array $config, string $key, bool $required = false): void
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            if ($required) {
                throw new \InvalidArgumentException("The [{$key}] must be an integer in the disk configuration.");
            }

            return;
        }

        if (! is_int($config[$key])) {
            throw new \InvalidArgumentException("The [{$key}] must be an integer in the disk configuration.");
        }
    }
}
