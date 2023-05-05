<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpDocumentGenerator\Reflection;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PhpDocumentGenerator\Link\LinkContext;
use PhpDocumentGenerator\Link\LinkInterface;
use PhpDocumentGenerator\Link\LinkTrait;
use PhpDocumentGenerator\Link\PhpStanLinkTypeHelper;
use Symfony\Component\PropertyInfo\PhpStan\NameScopeFactory;

class ReflectionMethod extends \ReflectionMethod implements LinkInterface
{
    use ReflectionTypeSeparatorTrait;
    use PhpDocTrait;
    use LinkTrait;
    public readonly string $visibility;
    private $phpStanLinkTypeHelper;

    public function __construct(object|string $objectOrMethod, string $method, LinkContext $linkContext)
    {
        parent::__construct($objectOrMethod, $method);
        $this->linkContext = $linkContext;
        $this->visibility = $this->getVisibility();
        $this->phpStanLinkTypeHelper = new PhpStanLinkTypeHelper($linkContext);
    }

    private function getVisibility(): string
    {
        if ($this->isPublic()) {
            return 'public';
        }

        if ($this->isProtected()) {
            return 'protected';
        }

        return 'private';
    }

    public function getDeclaringClass(): ReflectionClass
    {
        $declaringClass = parent::getDeclaringClass();

        return new ReflectionClass($declaringClass->name, $this->linkContext);
    }

    public function getDescription(): ?string
    {
        $text = '';
        if (!$this->getDocComment()) {
            return null;
        }
        
        $declaringClass = $this->getDeclaringClass();
        foreach($this->getPhpDoc($this->getDocComment()) as $node) {
            foreach ($node as $t) {
                if(!$t instanceof PhpDocTextNode) {
                    continue;
                }

                if (!str_contains($t->text, '{@inheritdoc}')) {
                    $text .= $this->transformSeeTags($t->text);
                    continue;
                }

                foreach($declaringClass->getInterfaces() as $i) {
                    if ($i->hasMethod($this->getName()) && $m = $i->getMethod($this->getName())) {
                        $text .= $m->getDescription();
                    }
                }

                if (($parentClass = $declaringClass->getParentClass()) && $parentClass->hasMethod($this->getName()) && $m = $parentClass->getMethod($this->getName())) {
                    $text .= $m->getDescription();
                }
            }
        }

        return $text;
    }

    public function getReturnTypes(): array
    {
        $docComment = $this->getDocComment();
        $declaringClass = $this->getDeclaringClass();

        if ($docComment) {
            $phpDoc = $this->getPhpDoc($docComment);
            $name = $this->getName();
            $namedFactory = new NameScopeFactory();
            $nameScope = $namedFactory->create($declaringClass->getName());

            // In a method we try to find the  parameter matching self
            foreach ($phpDoc->getReturnTagValues() as $node) {
                return $this->phpStanLinkTypeHelper->getTypes($node, $nameScope);
            }
        }

        if ($type = parent::getReturnType()) {
            return $this->extractFromReflectionType($type, $declaringClass);
        }

        return [];
    }

    public function getReturnTypeSeparator(): string {
        return $this->getReflectionTypeSeparator($this->getReturnType());
    }

    public function getLink(): ?string
    {
        return $this->getDeclaringClass()->getLink().'#'.$this->getName();
    }

    public function getParameters(): array
    {
        $parameters = [];
        foreach (parent::getParameters() as $parameter) {
            $parameters[] = new ReflectionParameter([parent::getDeclaringClass()->getName(), $this->name], $parameter->getName(), $this->linkContext);
        }

        return $parameters;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
