<?php

declare(strict_types=1);

namespace Inwebo\ItemMetaData\Patch;

/**
 * Supported JSON Patch operations as defined in RFC 6902.
 *
 * @see https://tools.ietf.org/html/rfc6902
 */
enum PatchOperation: string
{
    case Add = 'add';
    case Remove = 'remove';
    case Replace = 'replace';
    case Move = 'move';
    case Copy = 'copy';
    case Test = 'test';
}
