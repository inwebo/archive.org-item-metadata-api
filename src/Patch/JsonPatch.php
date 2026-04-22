<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Patch;

/**
 * Fluent builder for a JSON Patch document (RFC 6902).
 *
 * The MDAPI expects the entire patch as a JSON-encoded array of operations,
 * which is itself then percent-encoded inside the form payload.
 *
 * Example:
 *   $patch = JsonPatch::create()
 *       ->add('/collection/-', 'my-collection')
 *       ->add('/title', 'My Title');
 */
final class JsonPatch
{
    /** @var PatchEntry[] */
    private array $entries = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    // -------------------------------------------------------------------------
    // Factory helpers
    // -------------------------------------------------------------------------

    /**
     * Appends an "add" operation.
     *
     * Use "/-" as path suffix to append to an existing array:
     *   ->add('/collection/-', 'my-new-collection')
     */
    public function add(string $path, mixed $value): self
    {
        $clone = clone $this;
        $clone->entries[] = new PatchEntry(PatchOperation::Add, $path, $value);

        return $clone;
    }

    /**
     * Appends a "remove" operation.
     */
    public function remove(string $path): self
    {
        $clone = clone $this;
        $clone->entries[] = new PatchEntry(PatchOperation::Remove, $path);

        return $clone;
    }

    /**
     * Appends a "replace" operation.
     */
    public function replace(string $path, mixed $value): self
    {
        $clone = clone $this;
        $clone->entries[] = new PatchEntry(PatchOperation::Replace, $path, $value);

        return $clone;
    }

    /**
     * Appends a "move" operation.
     */
    public function move(string $from, string $path): self
    {
        $clone = clone $this;
        $clone->entries[] = new PatchEntry(PatchOperation::Move, $path, null, $from);

        return $clone;
    }

    /**
     * Appends a "copy" operation.
     */
    public function copy(string $from, string $path): self
    {
        $clone = clone $this;
        $clone->entries[] = new PatchEntry(PatchOperation::Copy, $path, null, $from);

        return $clone;
    }

    /**
     * Appends a "test" operation.
     */
    public function test(string $path, mixed $value): self
    {
        $clone = clone $this;
        $clone->entries[] = new PatchEntry(PatchOperation::Test, $path, $value);

        return $clone;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /** @return PatchEntry[] */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function isEmpty(): bool
    {
        return [] === $this->entries;
    }

    /**
     * Serialises the patch to a JSON string, as expected by the MDAPI "-patch" field.
     *
     * @throws \JsonException
     */
    public function toJson(): string
    {
        $operations = array_map(
            static fn (PatchEntry $entry): array => $entry->toArray(),
            $this->entries
        );

        return json_encode($operations, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
