<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\PDGBundle\Services\Reference\Reflection;

use ApiPlatform\PDGBundle\Services\Reference\OutputFormatter;
use ApiPlatform\PDGBundle\Services\Reference\Parser\PromotedPropertyDefaultValueNodeVisitor;
use ApiPlatform\PDGBundle\Services\Reference\Parser\PropertyDefaultValueNodeVisitor;
use ApiPlatform\PDGBundle\Services\Reference\PhpDocHelper;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class ReflectionPropertyHelper
{
    use ReflectionHelperTrait;

    private readonly Parser $parser;

    public function __construct(
        private readonly OutputFormatter $outputFormatter,
        private readonly PhpDocHelper $phpDocHelper
    ) {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
    }

    public function propertyHasToBeSkipped(\ReflectionProperty $property): bool
    {
        return str_contains($this->getModifier($property), 'private') && !$this->getAccessors($property);
    }

    public function getAccessors(\ReflectionProperty $property): array
    {
        $propertyName = ucfirst($property->getName());
        $accessors = [];

        foreach ($property->getDeclaringClass()->getMethods() as $method) {
            switch ($method->getName()) {
                case 'get'.$propertyName:
                case 'set'.$propertyName:
                case 'is'.$propertyName:
                    $accessors[] = $method->getName();
                    break;
                default:
                    continue 2;
            }
        }

        return $accessors;
    }

    public function getPromotedPropertyDefaultValueString(\ReflectionProperty $reflection): string
    {
        $traverser = new NodeTraverser();
        $visitor = new PromotedPropertyDefaultValueNodeVisitor($reflection);
        $traverser->addVisitor($visitor);

        $stmts = $this->parser->parse(file_get_contents($reflection->getDeclaringClass()->getFileName()));
        $traverser->traverse($stmts);

        $defaultValue = $visitor->defaultValue;
        $prefix = ' = ';

        return match (true) {
            null === $defaultValue => '',
            $defaultValue instanceof Node\Scalar => $prefix.$defaultValue->getAttribute('rawValue'),
            $defaultValue instanceof Node\Expr\ConstFetch => $prefix.$defaultValue->name->parts[0],
            $defaultValue instanceof Node\Expr\New_ => sprintf('%s new %s()', $prefix, $defaultValue->class->parts[0]),
            $defaultValue instanceof Node\Expr\Array_ => $prefix.$this->outputFormatter->arrayNodeToString($defaultValue),
            $defaultValue instanceof Node\Expr\ClassConstFetch => $prefix.$defaultValue->class->parts[0].'::'.$defaultValue->name->name
        };
    }

    public function getTypeString(\ReflectionProperty $reflectionProperty): string
    {
        $type = $reflectionProperty->getType();

        if (!$type) {
            return '';
        }

        if ($type instanceof \ReflectionUnionType) {
            $namedTypes = array_map(function (\ReflectionNamedType $namedType) {
                return $this->outputFormatter->linkClasses($namedType);
            }, $type->getTypes());

            return implode('|', $namedTypes);
        }
        if ($type instanceof \ReflectionIntersectionType) {
            $namedTypes = array_map(function (\ReflectionNamedType $namedType) {
                return $this->outputFormatter->linkClasses($namedType);
            }, $type->getTypes());

            return implode('&', $namedTypes);
        }
        if ($type instanceof \ReflectionNamedType) {
            return $this->outputFormatter->linkClasses($type);
        }

        return sprintf('`%s`', $type);
    }

    public function getAdditionalTypeInfo(\ReflectionProperty $reflectionProperty, array $constructorDocumentation): string
    {
        // Read the php doc
        $propertyTypes = $this->phpDocHelper->getPhpDoc($reflectionProperty);
        if ($varTagValues = $propertyTypes->getVarTagValues()) {
            $type = $varTagValues[0]->type;

            return $this->outputFormatter->formatType((string) $type);
        }

        if (isset($constructorDocumentation[$reflectionProperty->getName()])) {
            return $this->outputFormatter->formatType((string) $constructorDocumentation[$reflectionProperty->getName()]->type);
        }

        return '';
    }

    public function getPropertyDefaultValueString(\ReflectionProperty $property): string
    {
        $traverser = new NodeTraverser();
        $visitor = new PropertyDefaultValueNodeVisitor($property);
        $traverser->addVisitor($visitor);

        $stmts = $this->parser->parse(file_get_contents($property->getDeclaringClass()->getFileName()));
        $traverser->traverse($stmts);

        $defaultValue = $visitor->defaultValue;
        $prefix = ' = ';

        return match (true) {
            null === $defaultValue => '',
            $defaultValue instanceof Node\Scalar => $prefix.$defaultValue->getAttribute('rawValue'),
            $defaultValue instanceof Node\Expr\ConstFetch => $prefix.$defaultValue->name->parts[0],
            $defaultValue instanceof Node\Expr\New_ => sprintf('%s new %s()', $prefix, $defaultValue->class->parts[0]),
            $defaultValue instanceof Node\Expr\Array_ => $prefix.$this->outputFormatter->arrayNodeToString($defaultValue),
            $defaultValue instanceof Node\Expr\ClassConstFetch => $prefix.$defaultValue->class->parts[0].'::'.$defaultValue->name->name
        };
    }
}
