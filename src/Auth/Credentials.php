<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Auth;

/**
 * Holds Internet Archive S3 credentials (access key + secret key).
 *
 * Credentials can be retrieved from https://archive.org/account/s3.php
 */
final class Credentials
{
    public function __construct(
        private readonly string $accessKey,
        private readonly string $secretKey,
    ) {
        if ('' === trim($this->accessKey)) {
            throw new \InvalidArgumentException('Access key must not be empty.');
        }
        if ('' === trim($this->secretKey)) {
            throw new \InvalidArgumentException('Secret key must not be empty.');
        }
    }

    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * Returns the value for the "Authorization: LOW access:secret" header.
     */
    public function toAuthorizationHeader(): string
    {
        return sprintf('LOW %s:%s', $this->accessKey, $this->secretKey);
    }
}
