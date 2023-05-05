<?php

namespace PhpDocumentGenerator\Reflection;

trait ReflectionTypeSeparatorTrait
{
    private function getReflectionTypeSeparator(?\ReflectionType $type = null): string {
        if ($type instanceof \ReflectionUnionType) {
            return '|';
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return '&';
        }

        return '|';
    }
}
