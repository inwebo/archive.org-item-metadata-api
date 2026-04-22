<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Patch;

/**
 * Represents a single JSON Patch operation (RFC 6902).
 *
 * The "path" must be a JSON Pointer (RFC 6901) but must NOT include
 * the write target prefix. For example, to patch "/creator" in the
 * "metadata" target, the path is simply "/creator".
 *
 * Use "-" as the last path token to append to an array (e.g. "/-").
 */
final class PatchEntry
{
    /**
     * @param PatchOperation $operation the patch operation
     * @param string         $path      JSON Pointer path (RFC 6901), without the target prefix
     * @param mixed          $value     the value for "add", "replace", and "test" operations
     * @param string|null    $from      source path for "move" and "copy" operations
     */
    public function __construct(
        private readonly PatchOperation $operation,
        private readonly string $path,
        private readonly mixed $value = null,
        private readonly ?string $from = null,
    ) {
        if ('' === $this->path) {
            throw new \InvalidArgumentException('Patch path must not be empty.');
        }

        if (!str_starts_with($this->path, '/') && '-' !== $this->path) {
            throw new \InvalidArgumentException(sprintf('Patch path must start with "/" (JSON Pointer), got: "%s".', $this->path));
        }

        if (in_array($this->operation, [PatchOperation::Move, PatchOperation::Copy], true)
            && null === $this->from
        ) {
            throw new \InvalidArgumentException(sprintf('Operation "%s" requires a "from" path.', $this->operation->value));
        }
    }

    public function getOperation(): PatchOperation
    {
        return $this->operation;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Serialises this entry to an associative array suitable for json_encode().
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'op' => $this->operation->value,
            'path' => $this->path,
        ];

        if (null !== $this->from) {
            $data['from'] = $this->from;
        }

        if (null !== $this->value) {
            $data['value'] = $this->value;
        }

        return $data;
    }
}
