<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Tests\Response;

use Inwebo\ItemMetaData\Response\MetadataWriteResponse;
use PHPUnit\Framework\TestCase;

final class MetadataWriteResponseTest extends TestCase
{
    public function testFromArrayBuildsResponse(): void
    {
        $data = [
            'success' => true,
            'task_id' => 114350522,
            'log' => 'https://www.us.archive.org/log_show.php?task_id=114350522',
        ];

        $response = MetadataWriteResponse::fromArray($data);

        $this->assertTrue($response->isSuccess());
        $this->assertSame(114350522, $response->getTaskId());
        $this->assertSame('https://www.us.archive.org/log_show.php?task_id=114350522', $response->getLog());
    }

    public function testFromArrayWithStringTaskIdIsCoerced(): void
    {
        $data = [
            'success' => true,
            'task_id' => '999',
            'log' => 'https://www.us.archive.org/log_show.php?task_id=999',
        ];

        $response = MetadataWriteResponse::fromArray($data);

        $this->assertSame(999, $response->getTaskId());
    }

    public function testFromArrayWithSuccessFalse(): void
    {
        $data = [
            'success' => false,
            'task_id' => 0,
            'log' => '',
        ];

        $response = MetadataWriteResponse::fromArray($data);

        $this->assertFalse($response->isSuccess());
    }

    public function testFromArrayMissingSuccessThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('missing required fields');

        MetadataWriteResponse::fromArray(['task_id' => 1, 'log' => '']);
    }

    public function testFromArrayMissingTaskIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MetadataWriteResponse::fromArray(['success' => true, 'log' => '']);
    }

    public function testFromArrayMissingLogThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MetadataWriteResponse::fromArray(['success' => true, 'task_id' => 1]);
    }
}
