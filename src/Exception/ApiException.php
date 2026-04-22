<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Exception;

/**
 * Thrown when the Internet Archive API returns a non-2xx HTTP status code
 * or an application-level error payload ({ "error": "..." }).
 */
final class ApiException extends ItemMetadataException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?string $apiError = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * The error string returned by the API, if available.
     */
    public function getApiError(): ?string
    {
        return $this->apiError;
    }

    public static function fromResponse(int $statusCode, string $body): self
    {
        $decoded = json_decode($body, true);
        $apiError = is_array($decoded) ? ($decoded['error'] ?? null) : null;

        $message = sprintf(
            'Internet Archive API error (HTTP %d)%s.',
            $statusCode,
            null !== $apiError ? ': '.$apiError : ''
        );

        return new self($message, $statusCode, $apiError);
    }
}
