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

namespace PhpDocumentGenerator\Reflection;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PhpDocumentGenerator\Link\LinkContext;
use PhpDocumentGenerator\Link\LinkInterface;
use PhpDocumentGenerator\Link\LinkTrait;
use ReflectionClass as GlobalReflectionClass;
use Symfony\Component\Filesystem\Path;

final class ReflectionClass extends \ReflectionClass implements LinkInterface
{
    use PhpDocTrait;
    use LinkTrait;
    public readonly string $type;
    public readonly string $builtinType;

    public function __construct(private object|string $objectOrClass, private LinkContext $linkContext)
    {
        parent::__construct($objectOrClass);
        $this->linkContext = $linkContext;
        $this->type = $this->getClassType()->value;
        $this->builtinType = $this->getBuiltinType();
    }

    public function getInterfaces(): array
    {
        $interfaces = [];
        foreach (parent::getInterfaces() as $i) {
            $interfaces[] = new ReflectionClass($i->getName(), $this->linkContext);
        }

        return $interfaces;
    }

    public function getLink(): ?string
    {
        if ($this->isLocal($this->name)) {
            return Path::join($this->linkContext->baseUrl, str_replace('.php', '', Path::makeRelative($this->getFileName(), $this->linkContext->root)));
        }

        return null;
    }

    public function getMethods(?int $filter = null): array
    {
        $methods = [];
        foreach (parent::getMethods($filter) as $method) {
            $methods[] = new ReflectionMethod($method->class, $method->name, $this->linkContext);
        }

        return $methods;
    }

    public function getProperties(?int $filter = null): array
    {
        $properties = [];
        $class = $this->getName();
        foreach(parent::getProperties($filter) as $property) {
            $properties[] = new ReflectionProperty($class, $property->name, $this->linkContext);
        }

        return $properties;
    }

    public function getMethod(string $name): ReflectionMethod
    {
        return new ReflectionMethod($this->name, $name, $this->linkContext);
    }

    public function getBuiltinType(): string
    {
        if ($this->isInterface()) {
            return 'interface';
        }

        if ($this->isTrait()) {
            return 'trait';
        }

        if ($this->isEnum()) {
            return 'enum';
        }

        return 'class';
    }

    public function getDescription(): ?string
    {
        if (!$this->getDocComment()) {
            return null;
        }

        $text = [];
        foreach($this->getPhpDoc($this->getDocComment()) as $node) {
            foreach ($node as $t) {
                if(!$t instanceof PhpDocTextNode) {
                    continue;
                }

                if (str_contains($t->text, '{@inheritdoc}')) {
                    $text[] = $this->transformSeeTags(str_replace('{@inheritdoc}', '', $t->text));
                    $text[] = $this->getInterfaces()[0]->getDescription();
                } else {
                    $text[] = $this->transformSeeTags($t->text);
                }
            }
        }

        return implode(PHP_EOL, $text);
    }

    public function getDeclaringClass(): \ReflectionClass {
        return $this;
    }

    public function getParentClass(): GlobalReflectionClass|false
    {
        $t = parent::getParentClass();
        if (!$t) {
            return false;
        }

        return new ReflectionClass($t->getName(), $this->linkContext);
        
    }

    public function getClassType(): ReflectionClassType
    {
        if ($this->isInterface()) {
            return ReflectionClassType::InterfaceType;
        }

        if (\count($this->getAttributes('Attribute'))) {
            return ReflectionClassType::AttributeType;
        }

        if ($this->isEnum()) {
            return ReflectionClassType::EnumType;
        }

        if ($this->isTrait()) {
            return ReflectionClassType::TraitType;
        }

        return ReflectionClassType::ClassType;
    }

    public function __toString() {
        return $this->getName();
    }
}
