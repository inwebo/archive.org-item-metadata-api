<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Exception;

/**
 * Thrown when the API rejects the supplied credentials (HTTP 401 / 403).
 */
final class AuthenticationException extends ItemMetadataException
{
    public function __construct(string $message = 'Authentication failed: invalid or missing IA S3 credentials.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
