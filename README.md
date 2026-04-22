# Internet Archive Item Metadata API Client

A PHP client library for the Internet Archive Item Metadata Write API. It allows you to programmatically update item metadata using JSON Patch (RFC 6902).

## Overview

This library provides a clean, object-oriented interface to interact with the Internet Archive (IA) Metadata API. It simplifies the process of creating JSON Patch documents and sending them to the IA servers with proper authentication and formatting.

Key features:
- Fluent builder for JSON Patch operations (add, remove, replace, move, copy, test).
- Support for IA S3 authentication (Access Key and Secret Key).
- Handles double-encoding requirements of the Metadata API.
- Integration with Symfony HttpClient for robust networking.
- Specific helper for adding Wayback Machine URLs to user items.

## Installation

Install the package via Composer:

```bash
composer require inwebo/archive.org-item-metadata-api
```

## API Documentation

### ItemMetadataClient

The main entry point for the library.

- `__construct(Credentials $credentials, HttpClientInterface $httpClient)`: Initializes the client.
- `patch(MetadataWriteRequest $request): MetadataWriteResponse`: Sends a JSON Patch request to the API.
- `addWebArchiveUrl(string $userIdentifier, string $archiveUrl, string $target = 'web-archive'): MetadataWriteResponse`: Convenience method to append a Wayback Machine URL to a specific target (usually for user-owned items).

### Credentials

Used to hold your Internet Archive S3 credentials.

- `__construct(string $accessKey, string $secretKey)`: Requires keys obtained from [archive.org/account/s3.php](https://archive.org/account/s3.php).

### JsonPatch

A fluent builder to create RFC 6902 JSON Patch documents.

- `JsonPatch::create()`: Starts a new patch document.
- `add(string $path, mixed $value)`: Appends an "add" operation. Use `/-` to append to an array.
- `remove(string $path)`: Appends a "remove" operation.
- `replace(string $path, mixed $value)`: Appends a "replace" operation.
- `move(string $from, string $path)`: Appends a "move" operation.
- `copy(string $from, string $path)`: Appends a "copy" operation.
- `test(string $path, mixed $value)`: Appends a "test" operation.

### MetadataWriteRequest

Encapsulates the data needed for a write operation.

- `__construct(string $identifier, string $target, JsonPatch $patch, int $priority = 0)`:
    - `identifier`: The IA item identifier (e.g., `my-item` or `@my-user`).
    - `target`: The metadata target (e.g., `metadata`, `files/picture.jpg`, or a custom user JSON target).
    - `patch`: The `JsonPatch` object containing operations.
    - `priority`: Optional task priority (defaults to 0).

### MetadataWriteResponse

The object returned after a successful API call.

- `isSuccess(): bool`: Returns true if the operation was successful.
- `getTaskId(): int`: Returns the IA task ID created for this update.
- `getLog(): string`: Returns the URL to the task log on archive.org.

## Usage Examples

### Basic Metadata Update

Updating the title and adding a collection to an item:

```php
use Inwebo\ItemMetaData\ItemMetadataClient;
use Inwebo\ItemMetaData\Auth\Credentials;
use Inwebo\ItemMetaData\Patch\JsonPatch;
use Inwebo\ItemMetaData\Request\MetadataWriteRequest;
use Symfony\Component\HttpClient\HttpClient;

$client = new ItemMetadataClient(
    new Credentials('your_access_key', 'your_secret_key'),
    HttpClient::create()
);

$patch = JsonPatch::create()
    ->replace('/title', 'New Improved Title')
    ->add('/collection/-', 'opensource_movies');

$request = new MetadataWriteRequest(
    identifier: 'my-archive-item-id',
    target: 'metadata',
    patch: $patch
);

try {
    $response = $client->patch($request);
    echo "Success! Task ID: " . $response->getTaskId();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Adding a Wayback Machine URL

A common use case for specific user JSON targets:

```php
$client->addWebArchiveUrl(
    userIdentifier: '@my_username',
    archiveUrl: 'https://web.archive.org/web/20240101000000/https://example.com'
);
```

## License

This project is licensed under the MIT License.
