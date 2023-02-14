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

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;

final class ClassParser extends AbstractParser
{
    public function __construct(private readonly ReflectionClass $reflection, public readonly ?string $url = null, public ?string $type = null)
    {
        $this->type = $type ?: $this->getClassType($reflection);
    }

    public function hasTag(string $searchedTag): bool
    {
        // class has no doc (only search for class doc without inheritance)
        if (!$this->getReflection()->getDocComment()) {
            return false;
        }

        foreach ($this->getPhpDoc()->getTags() as $tag) {
            if ($searchedTag === $tag->name) {
                return true;
            }
        }

        return false;
    }

    public function getParentClass(): self|false
    {
        if ($parentClass = $this->getReflection()->getParentClass()) {
            return new self($parentClass);
        }

        return false;
    }

    /**
     * @return self[]
     */
    public function getInterfaces(): array
    {
        return array_map(fn (ReflectionClass $class) => new self($class), $this->getReflection()->getInterfaces());
    }

    public function getTraits(): array
    {
        return array_map(fn (ReflectionClass $class) => new self($class), $this->getReflection()->getTraits());
    }

    /**
     * Get public and protected constants.
     *
     * @return ConstantParser[]
     */
    public function getConstants(): array
    {
        $reflection = $this->getReflection();
        $traitsName = array_map(fn (ClassParser|ReflectionClass $c) => $c->getName(), $reflection->getTraits());

        $constants = [];
        foreach ($reflection->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC | ReflectionClassConstant::IS_PROTECTED) as $constant) {
            $constant = new ConstantParser($constant);

            // ignore from external class (e.g.: parent class)
            // if it is from a trait, ignore if it is imported in an external class (e.g.: parent class)
            if (
                $reflection->getName() !== ($class = $constant->getDeclaringClass())->getName()
                && !\in_array($class->getName(), $traitsName, true)
            ) {
                continue;
            }

            $constants[] = $constant;
        }

        return $constants;
    }

    /**
     * Get public and protected properties, and private ones with accessors.
     *
     * @return PropertyParser[]
     */
    public function getProperties(): array
    {
        $reflection = $this->getReflection();
        $traitsName = array_map(fn (ClassParser|ReflectionClass $c) => $c->getName(), $reflection->getTraits());

        $properties = [];
        foreach ($reflection->getProperties() as $property) {
            $property = new PropertyParser($property);

            // ignore private properties without accessors
            // ignore from external class (e.g.: parent class)
            // if it is from a trait, ignore if it is imported in an external class (e.g.: parent class)
            if (
                $property->isPrivate() && !$property->getAccessors()
                || (
                    $reflection->getName() !== ($class = $property->getDeclaringClass())->getName()
                    && !\in_array($class->getName(), $traitsName, true)
                )
            ) {
                continue;
            }

            $properties[] = $property;
        }

        return $properties;
    }

    /**
     * Get public and protected methods, except for constructor, external ones and accessors.
     *
     * @return MethodParser[]
     */
    public function getMethods(): array
    {
        $reflection = $this->getReflection();
        $traitsName = array_map(fn (ClassParser|ReflectionClass $c) => $c->getName(), $reflection->getTraits());

        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            $method = new MethodParser($method);

            // ignore constructor
            // ignore from external class (e.g.: parent class)
            // if it is from a trait, ignore if it is imported in an external class (e.g.: parent class)
            if (
                '__construct' === $method->getName()
                || (
                    $reflection->getName() !== ($class = $method->getDeclaringClass())->getName()
                    && !\in_array($class->getName(), $traitsName, true)
                )
            ) {
                continue;
            }

            // ignore accessors
            foreach ($method->getDeclaringClass()->getProperties() as $property) {
                if (\in_array($method->getName(), (new PropertyParser($property))->getAccessors(), true)) {
                    continue 2;
                }
            }

            $methods[] = $method;
        }

        return $methods;
    }

    public function getMethod(string $name): MethodParser
    {
        return new MethodParser($this->getReflection()->getMethod($name));
    }

    public function getReflection(): ReflectionClass
    {
        return $this->reflection;
    }

    protected function getClassName(): string
    {
        return $this->getReflection()->getName();
    }

    /**
     * Import docComment from parent class (not from interfaces).
     */
    protected function getParentDoc(): ?string
    {
        // ignore from traits
        if (
            $this->getReflection()->isTrait()
            || !($parentClass = $this->getParentClass())
            || !($parentDocComment = $parentClass->getPhpDoc()->__toString())
        ) {
            return null;
        }

        return $parentDocComment;
    }

    private function getClassType(ReflectionClass $refl): string
    {
        if ($refl->isInterface()) {
            return 'I';
        }

        if (\count($refl->getAttributes('Attribute'))) {
            return 'A';
        }

        if ($refl->isTrait()) {
            return 'T';
        }

        return 'C';
    }
}
