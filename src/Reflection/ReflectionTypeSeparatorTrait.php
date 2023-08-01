<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpDocumentGenerator\Reflection;

trait ReflectionTypeSeparatorTrait
{
    private function getReflectionTypeSeparator(\ReflectionType $type = null): string
    {
        if ($type instanceof \ReflectionUnionType) {
            return '|';
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return '&';
        }

        return '|';
    }
}
