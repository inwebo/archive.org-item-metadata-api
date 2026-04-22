<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Tests\Patch;

use Inwebo\ItemMetaData\Patch\PatchEntry;
use Inwebo\ItemMetaData\Patch\PatchOperation;
use PHPUnit\Framework\TestCase;

final class PatchEntryTest extends TestCase
{
    public function testAddOperationToArray(): void
    {
        $entry = new PatchEntry(PatchOperation::Add, '/collection/-', 'my-collection');

        $this->assertSame(PatchOperation::Add, $entry->getOperation());
        $this->assertSame('/collection/-', $entry->getPath());
        $this->assertSame('my-collection', $entry->getValue());
        $this->assertNull($entry->getFrom());
    }

    public function testToArrayForAddOperation(): void
    {
        $entry = new PatchEntry(PatchOperation::Add, '/title', 'My Title');

        $this->assertSame([
            'op' => 'add',
            'path' => '/title',
            'value' => 'My Title',
        ], $entry->toArray());
    }

    public function testToArrayForRemoveOperation(): void
    {
        $entry = new PatchEntry(PatchOperation::Remove, '/creator');

        $this->assertSame([
            'op' => 'remove',
            'path' => '/creator',
        ], $entry->toArray());
    }

    public function testToArrayForReplaceOperation(): void
    {
        $entry = new PatchEntry(PatchOperation::Replace, '/title', 'New Title');

        $this->assertSame([
            'op' => 'replace',
            'path' => '/title',
            'value' => 'New Title',
        ], $entry->toArray());
    }

    public function testToArrayForMoveOperation(): void
    {
        $entry = new PatchEntry(PatchOperation::Move, '/new-key', null, '/old-key');

        $array = $entry->toArray();

        $this->assertSame('move', $array['op']);
        $this->assertSame('/new-key', $array['path']);
        $this->assertSame('/old-key', $array['from']);
    }

    public function testToArrayForCopyOperation(): void
    {
        $entry = new PatchEntry(PatchOperation::Copy, '/destination', null, '/source');

        $array = $entry->toArray();

        $this->assertSame('copy', $array['op']);
        $this->assertSame('/destination', $array['path']);
        $this->assertSame('/source', $array['from']);
    }

    public function testToArrayForTestOperation(): void
    {
        $entry = new PatchEntry(PatchOperation::Test, '/version', 42);

        $this->assertSame([
            'op' => 'test',
            'path' => '/version',
            'value' => 42,
        ], $entry->toArray());
    }

    public function testEmptyPathThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Patch path must not be empty.');

        new PatchEntry(PatchOperation::Add, '', 'value');
    }

    public function testPathWithoutLeadingSlashThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Patch path must start with "/"');

        new PatchEntry(PatchOperation::Add, 'no-leading-slash', 'value');
    }

    public function testMoveWithoutFromThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Operation "move" requires a "from" path.');

        new PatchEntry(PatchOperation::Move, '/destination');
    }

    public function testCopyWithoutFromThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Operation "copy" requires a "from" path.');

        new PatchEntry(PatchOperation::Copy, '/destination');
    }

    public function testAppendTokenIsAccepted(): void
    {
        // "/-" is a valid JSON Pointer append token
        $entry = new PatchEntry(PatchOperation::Add, '/-', 'newItem');

        $this->assertSame('/-', $entry->getPath());
    }

    public function testArrayValueIsSerialised(): void
    {
        $value = ['sub-key' => 'sub-value'];
        $entry = new PatchEntry(PatchOperation::Add, '/complex', $value);

        $this->assertSame($value, $entry->toArray()['value']);
    }
}
