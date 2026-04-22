<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Tests;

use Inwebo\ItemMetaData\Auth\Credentials;
use Inwebo\ItemMetaData\Exception\ApiException;
use Inwebo\ItemMetaData\Exception\AuthenticationException;
use Inwebo\ItemMetaData\Exception\ItemMetadataException;
use Inwebo\ItemMetaData\ItemMetadataClient;
use Inwebo\ItemMetaData\Patch\JsonPatch;
use Inwebo\ItemMetaData\Request\MetadataWriteRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ItemMetadataClientTest extends TestCase
{
    private const ACCESS = 'testAccess';
    private const SECRET = 'testSecret';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeClient(MockResponse ...$responses): ItemMetadataClient
    {
        $httpClient = new MockHttpClient($responses);

        return new ItemMetadataClient(
            credentials: new Credentials(self::ACCESS, self::SECRET),
            httpClient: $httpClient,
        );
    }

    private function successResponse(): MockResponse
    {
        return new MockResponse(
            json_encode([
                'success' => true,
                'task_id' => 114350522,
                'log' => 'https://www.us.archive.org/log_show.php?task_id=114350522',
            ]),
            ['http_code' => 200, 'response_headers' => ['Content-Type: application/json']]
        );
    }

    private function simplePatch(): JsonPatch
    {
        return JsonPatch::create()->add('/title', 'Test');
    }

    // -------------------------------------------------------------------------
    // patch() — happy path
    // -------------------------------------------------------------------------

    public function testPatchReturnsSuccessResponse(): void
    {
        $client = $this->makeClient($this->successResponse());

        $response = $client->patch(
            new MetadataWriteRequest('xfetch', 'metadata', $this->simplePatch())
        );

        $this->assertTrue($response->isSuccess());
        $this->assertSame(114350522, $response->getTaskId());
        $this->assertStringContainsString('114350522', $response->getLog());
    }

    public function testPatchSendsCorrectAuthorizationHeader(): void
    {
        $capturedOptions = [];

        $mockResponse = new MockResponse(
            json_encode(['success' => true, 'task_id' => 1, 'log' => '']),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions, $mockResponse) {
            $capturedOptions = $options;

            return $mockResponse;
        });

        $client = new ItemMetadataClient(new Credentials(self::ACCESS, self::SECRET), $httpClient);
        $client->patch(new MetadataWriteRequest('xfetch', 'metadata', $this->simplePatch()));

        $headers = array_change_key_case(
            array_combine(
                array_map(fn ($h) => explode(':', $h, 2)[0], $capturedOptions['headers'] ?? []),
                array_map(fn ($h) => trim(explode(':', $h, 2)[1] ?? ''), $capturedOptions['headers'] ?? [])
            ),
            CASE_LOWER
        );

        $this->assertArrayHasKey('authorization', $headers);
        $this->assertSame(sprintf('LOW %s:%s', self::ACCESS, self::SECRET), $headers['authorization']);
    }

    public function testPatchSendsCorrectFormBody(): void
    {
        $capturedBody = null;

        $mockResponse = new MockResponse(
            json_encode(['success' => true, 'task_id' => 1, 'log' => '']),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedBody, $mockResponse) {
            $capturedBody = $options['body'] ?? null;
            if (is_string($capturedBody)) {
                parse_str($capturedBody, $capturedBody);
            }

            return $mockResponse;
        });

        $patch = JsonPatch::create()->add('/scan_sponsor', 'Starfleet');
        $client = new ItemMetadataClient(new Credentials(self::ACCESS, self::SECRET), $httpClient);
        $client->patch(new MetadataWriteRequest('metadata_test_item', 'metadata', $patch));

        // body is an associative array at this stage (before encoding)
        $this->assertIsArray($capturedBody);
        $this->assertSame('metadata', $capturedBody['-target']);
        $this->assertStringContainsString('scan_sponsor', $capturedBody['-patch']);
        $this->assertStringContainsString('Starfleet', $capturedBody['-patch']);
        $this->assertSame('0', $capturedBody['priority']);
    }

    public function testPatchSendsItemIdentifierInUrl(): void
    {
        $capturedUrl = null;

        $mockResponse = new MockResponse(
            json_encode(['success' => true, 'task_id' => 1, 'log' => '']),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedUrl, $mockResponse) {
            $capturedUrl = $url;

            return $mockResponse;
        });

        $client = new ItemMetadataClient(new Credentials(self::ACCESS, self::SECRET), $httpClient);
        $client->patch(new MetadataWriteRequest('@inwebo_veritas', 'web-archive', $this->simplePatch()));

        $this->assertStringContainsString('%40inwebo_veritas', $capturedUrl);
    }

    public function testPatchWithPriority(): void
    {
        $capturedBody = null;

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedBody) {
            $capturedBody = $options['body'] ?? null;
            if (is_string($capturedBody)) {
                parse_str($capturedBody, $capturedBody);
            }

            return new MockResponse(
                json_encode(['success' => true, 'task_id' => 1, 'log' => '']),
                ['http_code' => 200]
            );
        });

        $client = new ItemMetadataClient(new Credentials(self::ACCESS, self::SECRET), $httpClient);
        $client->patch(new MetadataWriteRequest('xfetch', 'metadata', $this->simplePatch(), -5));

        $this->assertSame('-5', $capturedBody['priority']);
    }

    // -------------------------------------------------------------------------
    // patch() — error handling
    // -------------------------------------------------------------------------

    public function testPatchThrowsAuthenticationExceptionOn401(): void
    {
        $this->expectException(AuthenticationException::class);

        $client = $this->makeClient(new MockResponse('Unauthorized', ['http_code' => 401]));
        $client->patch(new MetadataWriteRequest('xfetch', 'metadata', $this->simplePatch()));
    }

    public function testPatchThrowsAuthenticationExceptionOn403(): void
    {
        $this->expectException(AuthenticationException::class);

        $client = $this->makeClient(new MockResponse('Forbidden', ['http_code' => 403]));
        $client->patch(new MetadataWriteRequest('xfetch', 'metadata', $this->simplePatch()));
    }

    public function testPatchThrowsApiExceptionOn404(): void
    {
        $this->expectException(ApiException::class);

        $body = json_encode(['error' => 'item not found']);
        $client = $this->makeClient(new MockResponse($body, ['http_code' => 404]));
        $client->patch(new MetadataWriteRequest('no-such-item', 'metadata', $this->simplePatch()));
    }

    public function testPatchThrowsApiExceptionOn429(): void
    {
        $this->expectException(ApiException::class);

        $client = $this->makeClient(new MockResponse('Too Many Requests', ['http_code' => 429]));
        $client->patch(new MetadataWriteRequest('xfetch', 'metadata', $this->simplePatch()));
    }

    public function testPatchThrowsApiExceptionOn500(): void
    {
        $this->expectException(ApiException::class);

        $client = $this->makeClient(new MockResponse('Internal Server Error', ['http_code' => 500]));
        $client->patch(new MetadataWriteRequest('xfetch', 'metadata', $this->simplePatch()));
    }

    public function testPatchThrowsApiExceptionForHttp200WithErrorField(): void
    {
        $this->expectException(ApiException::class);

        $body = json_encode(['error' => 'patch failed: bad JSON pointer']);
        $client = $this->makeClient(new MockResponse($body, ['http_code' => 200]));
        $client->patch(new MetadataWriteRequest('xfetch', 'metadata', $this->simplePatch()));
    }

    public function testApiExceptionContainsStatusCode(): void
    {
        $body = json_encode(['error' => 'item not found']);
        $client = $this->makeClient(new MockResponse($body, ['http_code' => 404]));

        try {
            $client->patch(new MetadataWriteRequest('no-such-item', 'metadata', $this->simplePatch()));
            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $this->assertSame(404, $e->getStatusCode());
            $this->assertSame('item not found', $e->getApiError());
        }
    }

    public function testPatchThrowsItemMetadataExceptionOnInvalidResponseJson(): void
    {
        $this->expectException(ItemMetadataException::class);

        $client = $this->makeClient(new MockResponse('not-valid-json', ['http_code' => 200]));
        $client->patch(new MetadataWriteRequest('xfetch', 'metadata', $this->simplePatch()));
    }

    // -------------------------------------------------------------------------
    // addWebArchiveUrl() — happy path
    // -------------------------------------------------------------------------

    public function testAddWebArchiveUrlSendsCorrectPatch(): void
    {
        $capturedBody = null;
        $archiveUrl = 'https://web.archive.org/web/20260420083004/https://www.inwebo.net/';

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedBody) {
            $capturedBody = $options['body'] ?? null;
            if (is_string($capturedBody)) {
                parse_str($capturedBody, $capturedBody);
            }

            return new MockResponse(
                json_encode(['success' => true, 'task_id' => 42, 'log' => '']),
                ['http_code' => 200]
            );
        });

        $client = new ItemMetadataClient(new Credentials(self::ACCESS, self::SECRET), $httpClient);
        $client->addWebArchiveUrl('@inwebo_veritas', $archiveUrl);

        $this->assertSame('web-archive', $capturedBody['-target']);

        $patch = json_decode($capturedBody['-patch'], true);
        $this->assertCount(1, $patch);
        $this->assertSame('add', $patch[0]['op']);
        $this->assertSame('/-', $patch[0]['path']);
        $this->assertSame($archiveUrl, $patch[0]['value']);
    }

    public function testAddWebArchiveUrlWithCustomTarget(): void
    {
        $capturedBody = null;

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedBody) {
            $capturedBody = $options['body'] ?? null;
            if (is_string($capturedBody)) {
                parse_str($capturedBody, $capturedBody);
            }

            return new MockResponse(
                json_encode(['success' => true, 'task_id' => 1, 'log' => '']),
                ['http_code' => 200]
            );
        });

        $client = new ItemMetadataClient(new Credentials(self::ACCESS, self::SECRET), $httpClient);
        $client->addWebArchiveUrl('@inwebo_veritas', 'https://web.archive.org/web/1/', 'my-custom-target');

        $this->assertSame('my-custom-target', $capturedBody['-target']);
    }

    // -------------------------------------------------------------------------
    // addWebArchiveUrl() — input validation
    // -------------------------------------------------------------------------

    public function testAddWebArchiveUrlWithoutAtPrefixThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User identifier must start with "@"');

        $client = $this->makeClient();
        $client->addWebArchiveUrl('inwebo_veritas', 'https://web.archive.org/web/1/');
    }

    public function testAddWebArchiveUrlWithInvalidUrlThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid archive URL');

        $client = $this->makeClient();
        $client->addWebArchiveUrl('@inwebo_veritas', 'not-a-url');
    }

    public function testAddWebArchiveUrlReturnsSuccessResponse(): void
    {
        $client = $this->makeClient($this->successResponse());

        $response = $client->addWebArchiveUrl(
            '@inwebo_veritas',
            'https://web.archive.org/web/20260420083004/https://www.inwebo.net/'
        );

        $this->assertTrue($response->isSuccess());
        $this->assertSame(114350522, $response->getTaskId());
    }
}
