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
use ApiPlatform\PDGBundle\Services\Reference\PhpDocHelper;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use Reflection;
use ReflectionClass;
use ReflectionClassConstant;

class ReflectionHelper
{
    use ReflectionHelperTrait;

    public function __construct(
        private readonly PhpDocHelper $phpDocHelper,
        private readonly OutputFormatter $outputFormatter,
        private readonly ReflectionPropertyHelper $propertyHelper,
        private readonly ReflectionMethodHelper $methodHelper
    ) {
    }

    public function handleParent(ReflectionClass $reflectionClass, string $content): string
    {
        if (!$parent = $reflectionClass->getParentClass()) {
            return $content;
        }
        $content .= '### Extends: '.\PHP_EOL;
        $content .= '> '.$this->outputFormatter->addLink($parent).\PHP_EOL;

        return $content;
    }

    public function handleImplementations(ReflectionClass $reflectionClass, string $content): string
    {
        if (!$interfaces = $reflectionClass->getInterfaces()) {
            return $content;
        }

        $content .= '### Implements '.\PHP_EOL;

        foreach ($interfaces as $interface) {
            $content .= '> '.$this->outputFormatter->addLink($interface).\PHP_EOL.'> '.\PHP_EOL;
        }

        return $content;
    }

    public function handleClassConstants(ReflectionClass $reflectionClass, string $content): string
    {
        if (!$constants = $reflectionClass->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC)) {
            return $content;
        }

        $content .= '## Constants: '.\PHP_EOL;

        foreach ($constants as $constant) {
            $content .=
                '### '
                .$this->outputFormatter->addCssClasses($constant->getName(), ['token', 'keyword'])
                .' = ';
            if (!\is_array($constant->getValue())) {
                $content .= $constant->getValue().\PHP_EOL;
            } else {
                $content .= \PHP_EOL.'```php'.\PHP_EOL.print_r($constant->getValue(), true).'```'.\PHP_EOL;
            }

            $constantDoc = $this->phpDocHelper->getPhpDoc($constant);
            $constantText = array_filter($constantDoc->children, static function (PhpDocChildNode $constantDocNode): bool {
                return $constantDocNode instanceof PhpDocTextNode;
            });

            foreach ($constantText as $text) {
                $content .= $text.\PHP_EOL;
            }
        }

        return $content;
    }

    public function handleProperties(ReflectionClass $reflectionClass, string $content): string
    {
        $classProperties = [];
        foreach ($reflectionClass->getProperties() as $property) {
            if (!$this->propertyHelper->propertyHasToBeSkipped($property)) {
                $classProperties[] = $property;
            }
        }

        if (!$classProperties) {
            return $content;
        }
        $content .= '## Properties: '.\PHP_EOL;

        foreach ($classProperties as $property) {
            if ($property->isPromoted()) {
                $defaultValue = $this->propertyHelper->getPromotedPropertyDefaultValueString($property);
            } else {
                $defaultValue = $this->propertyHelper->getPropertyDefaultValueString($property);
            }
            $modifier = $this->getModifier($property);
            $accessors = $this->propertyHelper->getAccessors($property);

            $propertiesConstructorDocumentation = $this->phpDocHelper->getPropertiesConstructorDocumentation($reflectionClass);
            $type = $this->propertyHelper->getTypeString($property);
            $additionalTypeInfo = $this->propertyHelper->getAdditionalTypeInfo($property, $propertiesConstructorDocumentation);
            $content .= "<a className=\"anchor\" href=\"#{$property->getName()}\" id=\"{$property->getName()}\">§</a>".\PHP_EOL;
            $content .= "### {$modifier} {$type} {$this->outputFormatter->addCssClasses('$'.$property->getName(), ['token', 'keyword'])}";
            $content .= $defaultValue.\PHP_EOL;
            if ($additionalTypeInfo) {
                $content .= '> '.$additionalTypeInfo.\PHP_EOL.\PHP_EOL;
            }
            if (!empty($accessors)) {
                $content .= '**Accessors**: '.implode(', ', $accessors).\PHP_EOL;
            }
            $content .= \PHP_EOL;

            $doc = $this->phpDocHelper->getPhpDoc($property);
            $content = $this->outputFormatter->printTextNodes($doc, $content);

            $content .= \PHP_EOL.'---'.\PHP_EOL;
        }

        return $content;
    }

    public function handleMethods(ReflectionClass $reflectionClass, string $content): string
    {
        $methods = [];
        foreach ($reflectionClass->getMethods() as $method) {
            if (!$this->methodHelper->methodHasToBeSkipped($method, $reflectionClass)) {
                $methods[] = $method;
            }
        }

        if (!$methods) {
            return $content;
        }
        $content .= '## Methods: '.\PHP_EOL;

        foreach ($methods as $method) {
            $typedParameters = $this->methodHelper->getParametersWithType($method);

            $content .= "<a className=\"anchor\" href=\"#{$method->getName()}\" id=\"{$method->getName()}\">§</a>".\PHP_EOL;

            $content .= '### '
                .$this->getModifier($method)
                .' '
                .$this->outputFormatter->addCssClasses($method->getName(), ['token', 'function'])
                .'( '
                .implode(', ', $typedParameters)
                .' ): '
                .$this->outputFormatter->addCssClasses($this->methodHelper->getReturnType($method), ['token', 'keyword'])
                .\PHP_EOL;

            $phpDoc = $this->phpDocHelper->getPhpDoc($method);
            $text = array_filter($phpDoc->children, static function (PhpDocChildNode $child): bool {
                return $child instanceof PhpDocTextNode;
            });
            $content = $this->outputFormatter->printThrowTags($phpDoc, $content);

            /** @var PhpDocTextNode $t */
            foreach ($text as $t) {
                if ($this->phpDocHelper->containsInheritDoc($t)) {
                    // Imo Trait method should not have @inheritdoc as they might not "inherit" depending
                    // on the using class
                    if ($reflectionClass->isTrait()) {
                        continue;
                    }
                    $t = $this->phpDocHelper->getInheritedDoc($method);
                }
                if (!empty((string) $t)) {
                    $content .= $t.\PHP_EOL;
                }
            }

            $content .= \PHP_EOL.'---'.\PHP_EOL;
        }

        return $content;
    }

    public function containsOnlyPrivateMethods(ReflectionClass $reflectionClass): bool
    {
        // Do not skip empty interfaces
        if (interface_exists($reflectionClass->getName()) || trait_exists($reflectionClass->getName())) {
            return false;
        }

        if ($reflectionClass->getProperties()) {
            return false;
        }

        foreach ($reflectionClass->getMethods() as $method) {
            if (!\in_array('private', Reflection::getModifierNames($method->getModifiers()), true)) {
                return false;
            }
        }

        return true;
    }
}
