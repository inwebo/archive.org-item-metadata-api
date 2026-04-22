<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Tests\Auth;

use Inwebo\ItemMetaData\Auth\Credentials;
use PHPUnit\Framework\TestCase;

final class CredentialsTest extends TestCase
{
    public function testConstructorStoresKeys(): void
    {
        $credentials = new Credentials('myAccess', 'mySecret');

        $this->assertSame('myAccess', $credentials->getAccessKey());
        $this->assertSame('mySecret', $credentials->getSecretKey());
    }

    public function testToAuthorizationHeader(): void
    {
        $credentials = new Credentials('ACC', 'SEC');

        $this->assertSame('LOW ACC:SEC', $credentials->toAuthorizationHeader());
    }

    public function testEmptyAccessKeyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access key must not be empty.');

        new Credentials('', 'secret');
    }

    public function testEmptySecretKeyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Secret key must not be empty.');

        new Credentials('access', '');
    }

    public function testWhitespaceOnlyAccessKeyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Credentials('   ', 'secret');
    }
}
