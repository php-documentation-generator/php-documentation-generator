<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpDocumentGenerator\Link;

/**
 * Code adapted from https://github.com/symfony/property-info/blob/6.2/Extractor/ReflectionExtractor.php
 * (c) Fabien Potencier <fabien@symfony.com>.
 */
trait LinkTrait
{
    private LinkContext $linkContext;

    public function getLinkContext(): LinkContext
    {
        return $this->linkContext;
    }

    private function isLocal(string $name): bool
    {
        return str_starts_with($name, $this->linkContext->namespace.'\\');
    }

    /**
     * @return LinkType[]
     */
    private function extractFromReflectionType(\ReflectionType $reflectionType, \ReflectionClass $declaringClass): array
    {
        $types = [];
        $nullable = $reflectionType->allowsNull();

        foreach (($reflectionType instanceof \ReflectionUnionType || $reflectionType instanceof \ReflectionIntersectionType) ? $reflectionType->getTypes() : [$reflectionType] as $type) {
            if (!$type instanceof \ReflectionNamedType) {
                // Nested composite types are not supported yet.
                return [];
            }

            $phpTypeOrClass = $type->getName();
            if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass) {
                continue;
            }

            if (LinkType::BUILTIN_TYPE_ARRAY === $phpTypeOrClass) {
                $types[] = new LinkType(LinkType::BUILTIN_TYPE_ARRAY, $nullable, null, true, linkContext: $this->linkContext);
            } elseif ('void' === $phpTypeOrClass) {
                $types[] = new LinkType(LinkType::BUILTIN_TYPE_NULL, $nullable, linkContext: $this->linkContext);
            } elseif ($type->isBuiltin()) {
                $types[] = new LinkType($phpTypeOrClass, $nullable, linkContext: $this->linkContext);
            } else {
                $types[] = new LinkType(LinkType::BUILTIN_TYPE_OBJECT, $nullable, $this->resolveTypeName($phpTypeOrClass, $declaringClass), linkContext: $this->linkContext);
            }
        }

        return $types;
    }

    private function resolveTypeName(string $name, \ReflectionClass $declaringClass): string
    {
        if ('self' === $lcName = strtolower($name)) {
            return $declaringClass->name;
        }
        if ('parent' === $lcName && $parent = $declaringClass->getParentClass()) {
            return $parent->name;
        }

        return $name;
    }
}
