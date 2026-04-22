<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Request;

use Inwebo\ItemMetaData\Patch\JsonPatch;

/**
 * Encapsulates a single-target Metadata Write request.
 *
 * @see https://archive.org/developers/md-write.html#single-target-writes
 */
final class MetadataWriteRequest
{
    /**
     * @param string    $identifier The item identifier (e.g. "xfetch", "@inwebo_veritas").
     * @param string    $target     the write target: "metadata", "files/{filename}", or a user JSON name
     * @param JsonPatch $patch      the JSON Patch document to apply
     * @param int       $priority   task execution priority (default 0)
     */
    public function __construct(
        private readonly string $identifier,
        private readonly string $target,
        private readonly JsonPatch $patch,
        private readonly int $priority = 0,
    ) {
        if ('' === trim($this->identifier)) {
            throw new \InvalidArgumentException('Item identifier must not be empty.');
        }
        if ('' === trim($this->target)) {
            throw new \InvalidArgumentException('Write target must not be empty.');
        }
        if ($this->patch->isEmpty()) {
            throw new \InvalidArgumentException('JSON Patch must contain at least one operation.');
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getPatch(): JsonPatch
    {
        return $this->patch;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
