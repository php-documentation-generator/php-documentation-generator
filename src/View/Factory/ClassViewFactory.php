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

namespace PhpDocumentGenerator\View\Factory;

use PhpDocumentGenerator\Link\LinkContext;
use PhpDocumentGenerator\Parser\ClassParser;
use PhpDocumentGenerator\Parser\MethodParser;
use PhpDocumentGenerator\Parser\ParameterParser;
use PhpDocumentGenerator\Parser\PropertyParser;
use PhpDocumentGenerator\View\ClassView;
use PhpDocumentGenerator\View\ConstantView;
use PhpDocumentGenerator\View\MethodParameterView;
use PhpDocumentGenerator\View\MethodView;
use PhpDocumentGenerator\View\PropertyView;
use PhpDocumentGenerator\View\TypeView;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

final class ClassViewFactory
{
    public function __construct(private readonly LinkFactory $linkFactory)
    {
    }

    public function create(ClassParser $classParser, LinkContext $linkContext): ClassView
    {
        $classRefl = $classParser->getReflection();
        $deprecated = str_contains($classRefl->getDocComment(), '@deprecated');
        [$description, $links] = $this->linkFactory->getLinksFromPHPDoc($classParser->getDocComment());
        $parentClass = $classParser->getParentClass() ? $this->create($classParser->getParentClass(), $linkContext) : null;
        $link = $this->linkFactory->createClassLink($classParser->getReflection(), $linkContext);
        $interfaces = [];
        foreach ($classParser->getInterfaces() as $interface) {
            $interfaces[] = $this->create($interface, $linkContext);
        }

        $methods = [];

        foreach ($classParser->getMethods() as $methodParser) {
            $refl = $methodParser->getReflection();

            [$methodDescription, $methodLinks] = $this->linkFactory->getLinksFromPHPDoc($methodParser->getDocComment());

            $parameters = [];
            foreach ($methodParser->getParameters() as $parameterParser) {
                $parameterRefl = $parameterParser->getReflection();
                $types = $this->getTypes($parameterParser, $linkContext);
                [$parameterDescription, $parameterLinks] = $this->linkFactory->getLinksFromPHPDoc(array_values($types)[0]->description ?? '');
                $parameters[] = new MethodParameterView(name: $parameterRefl->getName(), types: $types, isReference: $parameterRefl->isPassedByReference(), defaultValue: $parameterRefl->isDefaultValueAvailable() ? $this->valueToString($parameterRefl->getDefaultValue()) : null, description: $parameterDescription);
            }

            $throws = [];
            foreach ($methodParser->getThrowTags() as $node) {
                $name = (string) $node->type;
                $throws[$name] = new TypeView(name: $name, link: $this->linkFactory->createNodeLink($node->type, $linkContext), description: $node->description);
            }

            $methods[] = new MethodView(name: $refl->getName(), modifier: $methodParser->getModifier(), parameters: $parameters, returnTypes: $this->getTypes($methodParser, $linkContext), throws: $throws, description: $methodDescription);
        }

        return new ClassView(name: $classRefl->getName(), description: $description, link: $link, links: $links, parentClass: $parentClass, interfaces: $interfaces, constants: $this->getConstants($classParser), methods: $methods, properties: $this->getProperties($classParser, $linkContext), type: $this->getClassType($classRefl), deprecated: $deprecated, final: $classRefl->isFinal());
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
        /** @var \ReflectionProperty|\ReflectionParameter|\ReflectionMethod $refl */
        $refl = $parser->getReflection();

        // Doc types take precedence
        if ($nodes = (array) ($parser instanceof MethodParser ? $parser->getDocReturnTypes() : $parser->getDocTypes())) {
            $types = [];
            foreach ($nodes as $node) {
                $nodeType = $node->type;

                if ($nodeType instanceof UnionTypeNode || $nodeType instanceof IntersectionTypeNode) {
                    /** @var TypeNode $node */
                    foreach ($nodeType->types as $n) {
                        $name = trim((string) $n);
                        if (!isset($types[$name])) {
                            $types[$name] = new TypeView(name: $name, link: $this->linkFactory->createNodeLink($n, $linkContext), type: $nodeType instanceof UnionTypeNode ? '|' : '&', description: $node->description);
                        }
                    }

                    continue;
                }

                $name = trim((string) $nodeType);
                $types[$name] = new TypeView(name: $name, link: $this->linkFactory->createNodeLink($nodeType, $linkContext), description: $node->description);
            }

            return $types;
        }

        $hasType = false;
        if ($refl instanceof \ReflectionMethod) {
            $hasType = $refl->hasReturnType();
        } elseif ($refl instanceof \ReflectionProperty || $refl instanceof \ReflectionParameter) {
            $hasType = $refl->hasType();
        }

        if ($hasType) {
            $typeParser = $parser->getType();
            $reflType = $typeParser->getReflection();
            if ($reflType instanceof \ReflectionNamedType) {
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
            [$description, $links] = $this->linkFactory->getLinksFromPHPDoc($constant->getDocComment());
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
            [$description, $links] = $this->linkFactory->getLinksFromPHPDoc($property->getDocComment());

            // TODO: accessor view ? method ?
            // $accessors = [];
            // foreach ($property->getAccessors() as $accessor) {
            //     $accessors[] = $accessor->getReflection()->getName();
            // }

            dd($linkContext);
            $properties[] = new PropertyView(
                name: $refl->getName(),
                description: $description,
                modifier: $property->getModifier(),
                defaultValue: $refl->hasDefaultValue() ? $this->valueToString($refl->getDefaultValue()) : null,
                types: $this->getTypes($property, $linkContext),
            );
        }

        return $properties;
    }

    private function getClassType(\ReflectionClass $refl): string
    {
        if ($refl->isInterface()) {
            return 'Interface';
        }

        if (\count($refl->getAttributes('Attribute'))) {
            return 'Attribute';
        }

        if ($refl->isTrait()) {
            return 'Trait';
        }

        return 'Class';
    }
}
