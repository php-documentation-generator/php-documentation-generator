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

namespace PhpDocumentGenerator\Twig;

use PhpDocumentGenerator\Parser\ClassParser;
use PhpDocumentGenerator\Parser\MethodParser;
use PhpDocumentGenerator\Parser\ParameterParser;
use PhpDocumentGenerator\Parser\PropertyParser;
use PhpDocumentGenerator\Twig\View\ClassView;
use PhpDocumentGenerator\Twig\View\ConstantView;
use PhpDocumentGenerator\Twig\View\MethodParameterView;
use PhpDocumentGenerator\Twig\View\MethodView;
use PhpDocumentGenerator\Twig\View\PropertyView;
use PhpDocumentGenerator\Twig\View\TypeView;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

final class ClassViewFactory
{
    use CodeSelectorTrait;

    public function __construct(private readonly LinkFactory $linkFactory)
    {
    }

    public function create(ClassParser $classParser, LinkContext $linkContext): ClassView
    {
        $classRefl = $classParser->getReflection();
        [$phpDoc, $links] = $this->linkFactory->getLinksFromPHPDoc($classParser->getDocComment());
        $description = $this->handleCodeSelector($phpDoc);
        $parentClass = $classParser->getParentClass() ? $this->create($classParser->getParentClass(), $linkContext) : null;
        $link = $this->linkFactory->createClassLink($classParser->getReflection(), $linkContext);
        $interfaces = [];
        foreach ($classParser->getInterfaces() as $interface) {
            $interfaces[] = $this->create($interface, $linkContext);
        }

        $methods = [];

        foreach ($classParser->getMethods() as $methodParser) {
            $refl = $methodParser->getReflection();

            $parameters = [];
            foreach ($methodParser->getParameters() as $parameter) {
                $parameterRefl = $parameter->getReflection();
                $parameters[] = new MethodParameterView(name: $parameterRefl->getName(), types: $this->getTypes($parameter, $linkContext), isReference: $parameterRefl->isPassedByReference(), defaultValue: $parameterRefl->isDefaultValueAvailable() ? $this->valueToString($parameterRefl->getDefaultValue()) : null);
            }

            $throws = [];
            foreach ($methodParser->getThrowTags() as $node) {
                $name = (string) $node->type;
                $throws[$name] = new TypeView(name: $name, link: $this->linkFactory->createNodeLink($node->type, $linkContext), description: $node->description);
            }

            $methods[] = new MethodView(name: $refl->getName(), modifier: $methodParser->getModifier(), parameters: $parameters, returnTypes: $this->getTypes($methodParser, $linkContext), throws: $throws);
        }

        return new ClassView(name: $classRefl->getName(), description: $description, link: $link, links: $links, parentClass: $parentClass, interfaces: $interfaces, constants: $this->getConstants($classParser), methods: $methods, properties: $this->getProperties($classParser, $linkContext));
    }

    private function valueToString(mixed $v): string
    {
        return \is_array($v) ? json_encode($v) : (string) $v;
    }

    /**
     * @return TypeView[]
     */
    private function getTypes(PropertyParser|ParameterParser|MethodParser $parser, LinkContext $linkContext): array
    {
        /** @var ReflectionProperty|ReflectionParameter|ReflectionMethod $refl */
        $refl = $parser->getReflection();

        // Doc types take precedence
        if ($nodes = (array) ($parser instanceof MethodParser ? $parser->getDocReturnTypes() : $parser->getDocTypes())) {
            $types = [];
            foreach ($nodes as $node) {
                $nodeType = $node->type;

                if ($nodeType instanceof UnionTypeNode || $nodeType instanceof IntersectionTypeNode) {
                    /** @var TypeNode $node */
                    foreach ($nodeType->types as $node) {
                        $name = trim((string) $node);
                        if (!isset($types[$name])) {
                            $types[$name] = new TypeView(name: $name, link: $this->linkFactory->createNodeLink($node, $linkContext), type: $nodeType instanceof UnionTypeNode ? '|' : '&');
                        }
                    }

                    continue;
                }

                $name = trim((string) $nodeType);
                $types[$name] = new TypeView(name: $name, link: $this->linkFactory->createNodeLink($nodeType, $linkContext));
            }

            return $types;
        }

        $hasType = false;
        if ($refl instanceof ReflectionMethod) {
            $hasType = $refl->hasReturnType();
        } elseif ($refl instanceof ReflectionProperty || $refl instanceof ReflectionParameter) {
            $hasType = $refl->hasType();
        }

        if ($hasType) {
            $typeParser = $parser->getType();
            $reflType = $typeParser->getReflection();
            if ($reflType instanceof ReflectionNamedType) {
                $link = $this->linkFactory->createTypeLink($reflType, $linkContext);

                return [new TypeView(name: sprintf('%s%s', $reflType->allowsNull() ? '?' : '', $reflType->getName()), link: $link)];
            }
        }

        return [];
    }

    /**
     * @return ConstantView[]
     */
    private function getConstants(ClassParser $classParser): array
    {
        $constants = [];
        foreach ($classParser->getConstants() as $constant) {
            [$phpDoc, $links] = $this->linkFactory->getLinksFromPHPDoc($constant->getDocComment());
            $description = $this->handleCodeSelector($phpDoc);
            $refl = $constant->getReflection();
            $constants[] = new ConstantView(name: $refl->getName(), value: $this->valueToString($refl->getValue()), description: $description, links: $links);
        }

        return $constants;
    }

    /**
     * @return PropertyView[]
     */
    private function getProperties(ClassParser $classParser, LinkContext $linkContext): array
    {
        $properties = [];
        foreach ($classParser->getProperties() as $property) {
            $refl = $property->getReflection();
            [$phpDoc, $links] = $this->linkFactory->getLinksFromPHPDoc($property->getDocComment());
            $description = $this->handleCodeSelector($phpDoc);

            // TODO: accessor view ? method ?
            $accessors = [];
            foreach ($property->getAccessors() as $accessor) {
                $accessors[] = $accessor->getReflection()->getName();
            }

            $properties[] = new PropertyView(
                name: $refl->getName(),
                description: $description,
                modifier: $property->getModifier(),
                defaultValue: $refl->hasDefaultValue() ? $this->valueToString($refl->getDefaultValue()) : null,
                types: $this->getTypes($property, $linkContext),
                accessors: $accessors
            );
        }

        return $properties;
    }
}
