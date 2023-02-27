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

namespace PhpDocumentGenerator\Parser;

use PhpDocumentGenerator\Parser\Ast\Node;
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
     * @return Node[]
     */
    public function getAdditionalReturnTypes(): array
    {
        return array_map(fn (ReturnTagValueNode $node) => new Node($node), array_unique($this->getPhpDoc()->getReturnTagValues()));
    }

    /**
     * @return Node[]
     */
    public function getThrowTags(): array
    {
        return array_map(fn (ThrowsTagValueNode $node) => new Node($node), array_unique($this->getPhpDoc()->getThrowsTagValues()));
    }

    public function getReflection(): ReflectionMethod
    {
        return $this->reflection;
    }

    protected function getParentPhpDoc(): ?string
    {
        $reflection = $this->getReflection();

        // import and replace "inheritdoc"
        $name = $reflection->getName();
        $class = new ClassParser($reflection->getDeclaringClass());

        // import docComment from parent class first
        if (
            false !== ($parentClass = $class->getParentClass())
            && $parentClass->hasMethod($name)
            && ($parentPhpDoc = $parentClass->getMethod($name)->getPhpDoc()->__toString())
        ) {
            return $parentPhpDoc;
        }

        // import docComment from interfaces
        foreach ($class->getInterfaces() as $interface) {
            if (
                $interface->hasMethod($name)
                && ($interfacePhpDoc = $interface->getMethod($name)->getPhpDoc()->__toString())
            ) {
                return $interfacePhpDoc;
            }
        }

        return null;
    }

    protected function getParentDocComment(): ?string
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
            return $parentDocComment;
        }

        // import docComment from interfaces
        foreach ($class->getInterfaces() as $interface) {
            if (
                $interface->hasMethod($name)
                && ($interfaceDocComment = $interface->getMethod($name)->getDocComment())
            ) {
                return $interfaceDocComment;
            }
        }

        return null;
    }
}
