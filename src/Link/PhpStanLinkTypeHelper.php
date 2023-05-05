<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpDocumentGenerator\Link;

use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Symfony\Component\PropertyInfo\PhpStan\NameScope;

/**
 * @copyright Copyright (c) Symfony https://github.com/symfony/symfony
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class PhpStanLinkTypeHelper
{
    public function __construct(private readonly LinkContext $linkContext) {}

    /**
     * Creates a {@see LinkType} from a PhpDocTagValueNode type.
     *
     * @return LinkType[]
     */
    public function getTypes(PhpDocTagValueNode $node, NameScope $nameScope): array
    {
        if ($node instanceof ParamTagValueNode || $node instanceof ThrowsTagValueNode || $node instanceof ReturnTagValueNode || $node instanceof VarTagValueNode) {
            return $this->compressNullableType($this->extractTypes($node->type, $nameScope));
        }

        return [];
    }

    /**
     * Because PhpStan extract null as a separated type when Symfony / PHP compress it in the first available type we
     * need this method to mimic how Symfony want null types.
     *
     * @param LinkType[] $types
     *
     * @return LinkType[]
     */
    private function compressNullableType(array $types): array
    {
        $firstTypeIndex = null;
        $nullableTypeIndex = null;

        foreach ($types as $k => $type) {
            if (null === $firstTypeIndex && LinkType::BUILTIN_TYPE_NULL !== $type->getBuiltinType() && !$type->isNullable()) {
                $firstTypeIndex = $k;
            }

            if (null === $nullableTypeIndex && LinkType::BUILTIN_TYPE_NULL === $type->getBuiltinType()) {
                $nullableTypeIndex = $k;
            }

            if (null !== $firstTypeIndex && null !== $nullableTypeIndex) {
                break;
            }
        }

        if (null !== $firstTypeIndex && null !== $nullableTypeIndex) {
            $firstType = $types[$firstTypeIndex];
            $types[$firstTypeIndex] = new LinkType(
                $firstType->getBuiltinType(),
                true,
                $firstType->getClassName(),
                $firstType->isCollection(),
                $firstType->getCollectionKeyTypes(),
                $firstType->getCollectionValueTypes(),
                linkContext: $this->linkContext
            );
            unset($types[$nullableTypeIndex]);
        }

        return array_values($types);
    }

    /**
     * @return Type[]
     */
    private function extractTypes(TypeNode $node, NameScope $nameScope): array
    {
        if ($node instanceof UnionTypeNode) {
            $types = [];
            foreach ($node->types as $type) {
                if ($type instanceof ConstTypeNode) {
                    // It's safer to fall back to other extractors here, as resolving const types correctly is not easy at the moment
                    return [];
                }
                foreach ($this->extractTypes($type, $nameScope) as $subType) {
                    $types[] = $subType;
                }
            }

            return $this->compressNullableType($types);
        }

        if ($node instanceof GenericTypeNode) {
            if ('class-string' === $node->type->name) {
                return [new LinkType(LinkType::BUILTIN_TYPE_STRING, linkContext: $this->linkContext)];
            }

            [$mainType] = $this->extractTypes($node->type, $nameScope);

            if (LinkType::BUILTIN_TYPE_INT === $mainType->getBuiltinType()) {
                return [$mainType];
            }

            $collectionKeyTypes = $mainType->getCollectionKeyTypes();
            $collectionKeyValues = [];
            if (1 === \count($node->genericTypes)) {
                foreach ($this->extractTypes($node->genericTypes[0], $nameScope) as $subType) {
                    $collectionKeyValues[] = $subType;
                }
            } elseif (2 === \count($node->genericTypes)) {
                foreach ($this->extractTypes($node->genericTypes[0], $nameScope) as $keySubType) {
                    $collectionKeyTypes[] = $keySubType;
                }
                foreach ($this->extractTypes($node->genericTypes[1], $nameScope) as $valueSubType) {
                    $collectionKeyValues[] = $valueSubType;
                }
            }

            return [new LinkType($mainType->getBuiltinType(), $mainType->isNullable(), $mainType->getClassName(), true, $collectionKeyTypes, $collectionKeyValues, linkContext: $this->linkContext)];
        }
        if ($node instanceof ArrayShapeNode) {
            return [new LinkType(LinkType::BUILTIN_TYPE_ARRAY, false, null, true, linkContext: $this->linkContext)];
        }
        if ($node instanceof ArrayTypeNode) {
            return [new LinkType(LinkType::BUILTIN_TYPE_ARRAY, false, null, true, [new LinkType(LinkType::BUILTIN_TYPE_INT, linkContext: $this->linkContext)], $this->extractTypes($node->type, $nameScope), linkContext: $this->linkContext)];
        }
        if ($node instanceof CallableTypeNode || $node instanceof CallableTypeParameterNode) {
            return [new LinkType(LinkType::BUILTIN_TYPE_CALLABLE, linkContext: $this->linkContext)];
        }
        if ($node instanceof NullableTypeNode) {
            $subTypes = $this->extractTypes($node->type, $nameScope);
            if (\count($subTypes) > 1) {
                $subTypes[] = new LinkType(LinkType::BUILTIN_TYPE_NULL, linkContext: $this->linkContext);

                return $subTypes;
            }

            return [new LinkType($subTypes[0]->getBuiltinType(), true, $subTypes[0]->getClassName(), $subTypes[0]->isCollection(), $subTypes[0]->getCollectionKeyTypes(), $subTypes[0]->getCollectionValueTypes(), linkContext: $this->linkContext)];
        }
        if ($node instanceof ThisTypeNode) {
            return [new LinkType(LinkType::BUILTIN_TYPE_OBJECT, false, $nameScope->resolveRootClass(), linkContext: $this->linkContext)];
        }
        if ($node instanceof IdentifierTypeNode) {
            if (\in_array($node->name, LinkType::$builtinTypes, true)) {
                return [new LinkType($node->name, false, null, \in_array($node->name, LinkType::$builtinCollectionTypes, true), linkContext: $this->linkContext)];
            }

            return match ($node->name) {
                'integer',
                'positive-int',
                'negative-int' => [new LinkType(LinkType::BUILTIN_TYPE_INT, linkContext: $this->linkContext)],
                'double' => [new LinkType(LinkType::BUILTIN_TYPE_FLOAT, linkContext: $this->linkContext)],
                'list',
                'non-empty-list' => [new LinkType(LinkType::BUILTIN_TYPE_ARRAY, false, null, true, new LinkType(LinkType::BUILTIN_TYPE_INT, linkContext: $this->linkContext), linkContext: $this->linkContext)],
                'non-empty-array' => [new LinkType(LinkType::BUILTIN_TYPE_ARRAY, false, null, true, linkContext: $this->linkContext)],
                'mixed' => [], // mixed seems to be ignored in all other extractors
                'parent' => [new LinkType(LinkType::BUILTIN_TYPE_OBJECT, false, $node->name, linkContext: $this->linkContext)],
                'static',
                'self' => [new LinkType(LinkType::BUILTIN_TYPE_OBJECT, false, $nameScope->resolveRootClass(), linkContext: $this->linkContext)],
                'class-string',
                'html-escaped-string',
                'lowercase-string',
                'non-empty-lowercase-string',
                'non-empty-string',
                'numeric-string',
                'trait-string',
                'interface-string',
                'literal-string' => [new LinkType(LinkType::BUILTIN_TYPE_STRING, linkContext: $this->linkContext)],
                'void' => [new LinkType(LinkType::BUILTIN_TYPE_NULL, linkContext: $this->linkContext)],
                'scalar' => [new LinkType(LinkType::BUILTIN_TYPE_INT, linkContext: $this->linkContext), new LinkType(LinkType::BUILTIN_TYPE_FLOAT, linkContext: $this->linkContext), new LinkType(LinkType::BUILTIN_TYPE_STRING, linkContext: $this->linkContext), new LinkType(LinkType::BUILTIN_TYPE_BOOL, linkContext: $this->linkContext)],
                'number' => [new LinkType(LinkType::BUILTIN_TYPE_INT, linkContext: $this->linkContext), new LinkType(LinkType::BUILTIN_TYPE_FLOAT, linkContext: $this->linkContext)],
                'numeric' => [new LinkType(LinkType::BUILTIN_TYPE_INT, linkContext: $this->linkContext), new LinkType(LinkType::BUILTIN_TYPE_FLOAT, linkContext: $this->linkContext), new LinkType(LinkType::BUILTIN_TYPE_STRING, linkContext: $this->linkContext)],
                'array-key' => [new LinkType(LinkType::BUILTIN_TYPE_STRING, linkContext: $this->linkContext), new LinkType(LinkType::BUILTIN_TYPE_INT, linkContext: $this->linkContext)],
                default => [new LinkType(LinkType::BUILTIN_TYPE_OBJECT, false, $nameScope->resolveStringName($node->name), linkContext: $this->linkContext)],
            };
        }

        return [];
    }
}
