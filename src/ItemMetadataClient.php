<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData;

use Inwebo\ItemMetaData\Auth\Credentials;
use Inwebo\ItemMetaData\Exception\ApiException;
use Inwebo\ItemMetaData\Exception\AuthenticationException;
use Inwebo\ItemMetaData\Exception\ItemMetadataException;
use Inwebo\ItemMetaData\Patch\JsonPatch;
use Inwebo\ItemMetaData\Request\MetadataWriteRequest;
use Inwebo\ItemMetaData\Response\MetadataWriteResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client for the Internet Archive Item Metadata Write API.
 *
 * Instantiate with your IA S3 credentials and a Symfony HttpClient instance:
 *
 *   $client = new ItemMetadataClient(
 *       credentials: new Credentials('myAccessKey', 'mySecretKey'),
 *       httpClient:  HttpClient::create(),
 *   );
 *
 * Then patch an item's metadata:
 *
 *   $response = $client->patch(
 *       new MetadataWriteRequest(
 *           identifier: '@inwebo_veritas',
 *           target:     'web-archive',
 *           patch:      JsonPatch::create()->add('/-', 'https://web.archive.org/web/20260420083004/https://www.inwebo.net/'),
 *       )
 *   );
 *
 * @see https://archive.org/developers/md-write.html
 */
final class ItemMetadataClient
{
    private const BASE_URL = 'https://archive.org/metadata';

    public function __construct(
        private readonly Credentials $credentials,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Executes a single-target Metadata Write (JSON Patch) request.
     *
     * @throws AuthenticationException when the API returns HTTP 401 or 403
     * @throws ApiException            when the API returns any other error response
     * @throws ItemMetadataException   on transport or serialisation errors
     */
    public function patch(MetadataWriteRequest $request): MetadataWriteResponse
    {
        $url = sprintf('%s/%s', self::BASE_URL, rawurlencode($request->getIdentifier()));

        try {
            $patchJson = $request->getPatch()->toJson();
        } catch (\JsonException $e) {
            throw new ItemMetadataException('Failed to serialise JSON Patch: '.$e->getMessage(), 0, $e);
        }

        /*
         * The MDAPI requires URL-encoded form data. The "-patch" value is itself
         * a JSON string, so the payload is double-encoded: first as JSON, then
         * percent-encoded by the HTTP client.
         *
         * @see https://archive.org/developers/md-write.html#single-target-writes
         */
        $body = [
            '-target' => $request->getTarget(),
            '-patch' => $patchJson,
            'priority' => (string) $request->getPriority(),
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => $this->credentials->toAuthorizationHeader(),
                    'Accept' => 'application/json',
                ],
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false); // false = do not throw on 4xx/5xx
        } catch (TransportExceptionInterface $e) {
            throw new ItemMetadataException('HTTP transport error: '.$e->getMessage(), 0, $e);
        }

        return $this->handleResponse($statusCode, $content);
    }

    // -------------------------------------------------------------------------
    // Convenience helpers
    // -------------------------------------------------------------------------

    /**
     * Adds a single web-archive URL to a user's web-archive item.
     *
     * Example:
     *   $client->addWebArchiveUrl(
     *       userIdentifier: '@inwebo_veritas',
     *       archiveUrl:     'https://web.archive.org/web/20260420083004/https://www.inwebo.net/',
     *   );
     *
     * The URL is appended to the root array of the "web-archive" user JSON
     * target using the JSON Pointer append token "/-".
     *
     * @param string $userIdentifier Archive.org user item identifier (e.g. "@inwebo_veritas").
     * @param string $archiveUrl     the Wayback Machine URL to add
     * @param string $target         the user-JSON target name (default: "web-archive")
     *
     * @throws AuthenticationException
     * @throws ApiException
     * @throws ItemMetadataException
     */
    public function addWebArchiveUrl(
        string $userIdentifier,
        string $archiveUrl,
        string $target = 'web-archive',
    ): MetadataWriteResponse {
        if (!str_starts_with($userIdentifier, '@')) {
            throw new \InvalidArgumentException(sprintf('User identifier must start with "@", got: "%s".', $userIdentifier));
        }

        if (!filter_var($archiveUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(sprintf('Invalid archive URL: "%s".', $archiveUrl));
        }

        $patch = JsonPatch::create()->add('/-', $archiveUrl);

        return $this->patch(new MetadataWriteRequest($userIdentifier, $target, $patch));
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @throws AuthenticationException
     * @throws ApiException
     */
    private function handleResponse(int $statusCode, string $body): MetadataWriteResponse
    {
        if (401 === $statusCode || 403 === $statusCode) {
            throw new AuthenticationException(sprintf('Authentication failed (HTTP %d). Check your IA S3 access/secret keys.', $statusCode));
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw ApiException::fromResponse($statusCode, $body);
        }

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ItemMetadataException('Failed to decode API response JSON: '.$e->getMessage(), 0, $e);
        }

        // The API may return HTTP 200 but with an error payload.
        if (isset($data['error'])) {
            throw ApiException::fromResponse($statusCode, $body);
        }

        return MetadataWriteResponse::fromArray($data);
    }
}
