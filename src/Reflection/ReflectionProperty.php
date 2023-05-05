<?php

namespace PhpDocumentGenerator\Reflection;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PhpDocumentGenerator\Link\LinkContext;
use PhpDocumentGenerator\Link\LinkTrait;
use PhpDocumentGenerator\Link\PhpStanLinkTypeHelper;
use Symfony\Component\PropertyInfo\PhpStan\NameScopeFactory;

class ReflectionProperty extends \ReflectionProperty
{
    use ReflectionTypeSeparatorTrait;
    use LinkTrait;
    use PhpDocTrait;
    private $phpStanLinkTypeHelper;

    public function __construct(object|string $class, string $property, LinkContext $linkContext) {
        parent::__construct($class, $property);
        $this->linkContext = $linkContext;
        $this->phpStanLinkTypeHelper = new PhpStanLinkTypeHelper($linkContext);
    }

    public function getDescription(): ?string
    {
        if (!$this->getDocComment()) {
            return null;
        }

        $text = '';
        foreach($this->getPhpDoc($this->getDocComment()) as $node) {
            foreach ($node as $t) {
                if($t instanceof PhpDocTextNode) {
                    $text .= $t->text . PHP_EOL;
                }
            }
        }

        return $text;
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
        $docComment = $this->getDocComment();
        $declaringClass = $this->getDeclaringClass();

        if ($docComment) {
            $phpDoc = $this->getPhpDoc($docComment);
            $namedFactory = new NameScopeFactory();
            $nameScope = $namedFactory->create($declaringClass->getName());

            foreach ($phpDoc->getVarTagValues() as $node) {
                return $this->phpStanLinkTypeHelper->getTypes($node, $nameScope);
            }
        }

        if ($type = parent::getType()) {
            return $this->extractFromReflectionType($type, $declaringClass);
        }

        return [];
    }

    // TODO: fix link for methods
    public function getLink(): ?string {
        return 'link prop';
        // return $this->getDeclaringClass()->getLink() . '#' . $this->name;
    }

    public function __toString() {
        return $this->getName();
    }
}
