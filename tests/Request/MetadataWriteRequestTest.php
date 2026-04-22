<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Tests\Request;

use Inwebo\ItemMetaData\Patch\JsonPatch;
use Inwebo\ItemMetaData\Request\MetadataWriteRequest;
use PHPUnit\Framework\TestCase;

final class MetadataWriteRequestTest extends TestCase
{
    private function validPatch(): JsonPatch
    {
        return JsonPatch::create()->add('/title', 'Test');
    }

    public function testConstructorStoresValues(): void
    {
        $patch = $this->validPatch();
        $request = new MetadataWriteRequest('@inwebo_veritas', 'web-archive', $patch, 5);

        $this->assertSame('@inwebo_veritas', $request->getIdentifier());
        $this->assertSame('web-archive', $request->getTarget());
        $this->assertSame($patch, $request->getPatch());
        $this->assertSame(5, $request->getPriority());
    }

    public function testDefaultPriorityIsZero(): void
    {
        $request = new MetadataWriteRequest('xfetch', 'metadata', $this->validPatch());

        $this->assertSame(0, $request->getPriority());
    }

    public function testEmptyIdentifierThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Item identifier must not be empty.');

        new MetadataWriteRequest('', 'metadata', $this->validPatch());
    }

    public function testEmptyTargetThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Write target must not be empty.');

        new MetadataWriteRequest('xfetch', '', $this->validPatch());
    }

    public function testEmptyPatchThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON Patch must contain at least one operation.');

        new MetadataWriteRequest('xfetch', 'metadata', JsonPatch::create());
    }

    public function testMetadataTarget(): void
    {
        $request = new MetadataWriteRequest('xfetch', 'metadata', $this->validPatch());

        $this->assertSame('metadata', $request->getTarget());
    }

    public function testFilesTarget(): void
    {
        $request = new MetadataWriteRequest('xfetch', 'files/xfetch.pdf', $this->validPatch());

        $this->assertSame('files/xfetch.pdf', $request->getTarget());
    }

    public function testUserJsonTarget(): void
    {
        $request = new MetadataWriteRequest('@inwebo_veritas', 'web-archive', $this->validPatch());

        $this->assertSame('web-archive', $request->getTarget());
    }

    public function testNegativePriorityIsAllowed(): void
    {
        $request = new MetadataWriteRequest('xfetch', 'metadata', $this->validPatch(), -5);

        $this->assertSame(-5, $request->getPriority());
    }
}
