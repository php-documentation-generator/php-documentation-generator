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

namespace ApiPlatform\PDGBundle\Parser;

use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use Reflection;
use ReflectionMethod;
use ReflectionParameter;

final class MethodParser extends AbstractParser
{
    public function __construct(private readonly ReflectionMethod $reflection)
    {
    }

    public function getModifier(): string
    {
        return implode(' ', Reflection::getModifierNames($this->getReflection()->getModifiers()));
    }

    /**
     * @return ParameterParser[]
     */
    public function getParameters(): array
    {
        return array_map(
            fn (ReflectionParameter $parameter) => new ParameterParser($parameter),
            $this->getReflection()->getParameters()
        );
    }

    public function getReturnType(): ?TypeParser
    {
        $reflection = $this->getReflection();

        return $reflection->hasReturnType() ? new TypeParser($reflection->getReturnType()) : null;
    }

    /**
     * @return ReturnTagValueNode[]
     */
    public function getAdditionalReturnTypes(): array
    {
        // retrieve additional return types from doc
        // todo is it possible to detect a class and convert it to ReflectionClass? (/!\ PHPStan does not resolve imports)
        return $this->getPhpDoc()->getReturnTagValues();
    }

    /**
     * @return ThrowsTagValueNode[]
     */
    public function getThrowTags(): array
    {
        // todo is it possible to detect a class and convert it to ReflectionClass? (/!\ PHPStan does not resolve imports)
        return $this->getPhpDoc()->getThrowsTagValues();
    }

    public function getReflection(): ReflectionMethod
    {
        return $this->reflection;
    }

    protected function inheritDoc(string $docComment): string
    {
        $reflection = $this->getReflection();

        // import and replace "inheritdoc"
        $name = $reflection->getName();
        $class = new ClassParser($reflection->getDeclaringClass());

        // import docComment from parent class first
        if (
            false !== ($parentClass = $class->getParentClass())
            && $parentClass->hasMethod($name)
            && ($parentDocComment = $parentClass->getMethod($name)->getDocComment())
        ) {
            return preg_replace('/{?@inheritdoc}?/', preg_replace('/(?:\/\*\*(?:\n *\*)? )|(\n? *\*\/)/', '', $parentDocComment), $docComment);
        }

        // import docComment from interfaces
        foreach ($class->getInterfaces() as $interface) {
            if (
                $interface->hasMethod($name)
                && ($interfaceDocComment = $interface->getMethod($name)->getDocComment())
            ) {
                return preg_replace('/{?@inheritdoc}?/', preg_replace('/(?:\/\*\*(?:\n *\*)? )|(\n? *\*\/)/', '', $interfaceDocComment), $docComment);
            }
        }

        return $docComment;
    }
}
