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

use PhpDocumentGenerator\Link\LinkContext;
use PhpDocumentGenerator\Link\LinkInterface;
use PhpDocumentGenerator\Link\LinkTrait;
use PhpDocumentGenerator\Link\LinkType;
use PhpDocumentGenerator\Link\PhpStanLinkTypeHelper;
use Symfony\Component\PropertyInfo\PhpStan\NameScopeFactory;

class ReflectionParameter extends \ReflectionParameter implements LinkInterface
{
    use LinkTrait;
    use PhpDocTrait;
    use ReflectionTypeSeparatorTrait;
    private $phpStanLinkTypeHelper;

    public function __construct(string|array|object $function, int|string $param, LinkContext $linkContext)
    {
        parent::__construct($function, $param);
        $this->linkContext = $linkContext;
        $this->phpStanLinkTypeHelper = new PhpStanLinkTypeHelper($linkContext);
    }

    public function getType(): ?\ReflectionType
    {
        return parent::getType();
    }

    public function getTypeSeparator(): string
    {
        return $this->getReflectionTypeSeparator($this->getType());
    }

    public function getDescription(): string
    {
        $docComment = $this->getDeclaringFunction()->getDocComment();

        if (!$docComment) {
            return '';
        }

        $phpDoc = $this->getPhpDoc($docComment);
        $name = '$'.$this->getName();

        // In a method we try to find the  parameter matching self
        foreach ($phpDoc->getParamTagValues() as $node) {
            if ($node->parameterName === $name) {
                return $this->transformSeeTags($node->description);
            }
        }

        return '';
    }

    public function getDeclaringClass(): ReflectionClass
    {
        $declaringClass = parent::getDeclaringClass();

        return new ReflectionClass($declaringClass->name, $this->linkContext);
    }

    /**
     * @return LinkType[]
     */
    public function getTypes(): array
    {
        $docComment = $this->getDeclaringFunction()->getDocComment();
        $declaringClass = $this->getDeclaringClass();

        if ($docComment) {
            $phpDoc = $this->getPhpDoc($docComment);
            $name = '$'.$this->getName();
            $namedFactory = new NameScopeFactory();
            $nameScope = $namedFactory->create($declaringClass->getName());

            // In a method we try to find the  parameter matching self
            foreach ($phpDoc->getParamTagValues() as $node) {
                if ($node->parameterName === $name) {
                    return $this->phpStanLinkTypeHelper->getTypes($node, $nameScope);
                }
            }
        }

        if ($type = parent::getType()) {
            return $this->extractFromReflectionType($type, $declaringClass);
        }

        return [];
    }

    // TODO: fix link for methods
    public function getLink(): ?string
    {
        return $this->getDeclaringClass()->getLink().'#'.$this->name;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getDocComment()
    {
        return $this->getDeclaringFunction()->getDocComment();
    }
}
