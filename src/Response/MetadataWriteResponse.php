<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Response;

/**
 * Represents a successful Metadata Write API response.
 *
 * On success the MDAPI returns:
 * {
 *   "success": true,
 *   "task_id": 114350522,
 *   "log": "https://www.us.archive.org/log_show.php?task_id=114350522"
 * }
 */
final class MetadataWriteResponse
{
    public function __construct(
        private readonly bool $success,
        private readonly int $taskId,
        private readonly string $log,
    ) {
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['success'], $data['task_id'], $data['log'])) {
            throw new \InvalidArgumentException('Invalid API response: missing required fields (success, task_id, log).');
        }

        return new self(
            success: (bool) $data['success'],
            taskId: (int) $data['task_id'],
            log: (string) $data['log'],
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    /**
     * URL of the catalog task log on archive.org.
     */
    public function getLog(): string
    {
        return $this->log;
    }
}
