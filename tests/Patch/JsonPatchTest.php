<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Tests\Patch;

use Inwebo\ItemMetaData\Patch\JsonPatch;
use Inwebo\ItemMetaData\Patch\PatchOperation;
use PHPUnit\Framework\TestCase;

final class JsonPatchTest extends TestCase
{
    public function testCreateReturnsEmptyPatch(): void
    {
        $patch = JsonPatch::create();

        $this->assertTrue($patch->isEmpty());
        $this->assertSame([], $patch->getEntries());
    }

    public function testAddReturnsNewInstance(): void
    {
        $original = JsonPatch::create();
        $modified = $original->add('/title', 'Hello');

        $this->assertNotSame($original, $modified);
        $this->assertTrue($original->isEmpty(), 'Original must stay immutable.');
        $this->assertFalse($modified->isEmpty());
    }

    public function testAddOperation(): void
    {
        $patch = JsonPatch::create()->add('/title', 'My Archive');

        $entries = $patch->getEntries();
        $this->assertCount(1, $entries);
        $this->assertSame(PatchOperation::Add, $entries[0]->getOperation());
        $this->assertSame('/title', $entries[0]->getPath());
        $this->assertSame('My Archive', $entries[0]->getValue());
    }

    public function testRemoveOperation(): void
    {
        $patch = JsonPatch::create()->remove('/creator');

        $entries = $patch->getEntries();
        $this->assertCount(1, $entries);
        $this->assertSame(PatchOperation::Remove, $entries[0]->getOperation());
        $this->assertSame('/creator', $entries[0]->getPath());
    }

    public function testReplaceOperation(): void
    {
        $patch = JsonPatch::create()->replace('/title', 'New Title');

        $entries = $patch->getEntries();
        $this->assertSame(PatchOperation::Replace, $entries[0]->getOperation());
        $this->assertSame('New Title', $entries[0]->getValue());
    }

    public function testMoveOperation(): void
    {
        $patch = JsonPatch::create()->move('/old-key', '/new-key');

        $entries = $patch->getEntries();
        $this->assertSame(PatchOperation::Move, $entries[0]->getOperation());
        $this->assertSame('/old-key', $entries[0]->getFrom());
        $this->assertSame('/new-key', $entries[0]->getPath());
    }

    public function testCopyOperation(): void
    {
        $patch = JsonPatch::create()->copy('/source', '/destination');

        $entries = $patch->getEntries();
        $this->assertSame(PatchOperation::Copy, $entries[0]->getOperation());
        $this->assertSame('/source', $entries[0]->getFrom());
        $this->assertSame('/destination', $entries[0]->getPath());
    }

    public function testTestOperation(): void
    {
        $patch = JsonPatch::create()->test('/version', 5);

        $entries = $patch->getEntries();
        $this->assertSame(PatchOperation::Test, $entries[0]->getOperation());
        $this->assertSame(5, $entries[0]->getValue());
    }

    public function testChainedOperations(): void
    {
        $patch = JsonPatch::create()
            ->add('/title', 'Title')
            ->add('/collection/-', 'collection1')
            ->remove('/old-field')
            ->replace('/description', 'New desc');

        $this->assertCount(4, $patch->getEntries());
    }

    public function testToJsonProducesValidJson(): void
    {
        $patch = JsonPatch::create()
            ->add('/scan_sponsor', 'Starfleet')
            ->remove('/obsolete');

        $json = $patch->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);

        $this->assertSame('add', $decoded[0]['op']);
        $this->assertSame('/scan_sponsor', $decoded[0]['path']);
        $this->assertSame('Starfleet', $decoded[0]['value']);

        $this->assertSame('remove', $decoded[1]['op']);
        $this->assertSame('/obsolete', $decoded[1]['path']);
    }

    public function testToJsonForWebArchiveAppend(): void
    {
        $archiveUrl = 'https://web.archive.org/web/20260420083004/https://www.inwebo.net/';
        $patch = JsonPatch::create()->add('/-', $archiveUrl);

        $json = $patch->toJson();
        $decoded = json_decode($json, true);

        $this->assertCount(1, $decoded);
        $this->assertSame('add', $decoded[0]['op']);
        $this->assertSame('/-', $decoded[0]['path']);
        $this->assertSame($archiveUrl, $decoded[0]['value']);
    }

    public function testToJsonDoesNotEscapeSlashes(): void
    {
        $patch = JsonPatch::create()->add('/-', 'https://example.com/path/to/page');
        $json = $patch->toJson();

        // JSON_UNESCAPED_SLASHES: forward slashes must NOT be \/ escaped
        $this->assertStringNotContainsString('\/', $json);
    }

    public function testToJsonPreservesUnicode(): void
    {
        $patch = JsonPatch::create()->add('/title', 'Héllo wörld');
        $json = $patch->toJson();

        $this->assertStringContainsString('Héllo wörld', $json);
    }

    public function testImmutabilityAcrossMultipleCalls(): void
    {
        $base = JsonPatch::create()->add('/title', 'Base');

        $branchA = $base->add('/creator', 'Alice');
        $branchB = $base->add('/creator', 'Bob');

        $this->assertCount(1, $base->getEntries());
        $this->assertCount(2, $branchA->getEntries());
        $this->assertCount(2, $branchB->getEntries());

        // Branches must be independent
        $this->assertSame('Alice', $branchA->getEntries()[1]->getValue());
        $this->assertSame('Bob', $branchB->getEntries()[1]->getValue());
    }
}
