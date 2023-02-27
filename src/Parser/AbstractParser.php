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

use LogicException;
use PhpDocumentGenerator\Services\PhpStanTypeHelper;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser;
use Symfony\Component\PropertyInfo\PhpStan\NameScopeFactory;
use Symfony\Component\PropertyInfo\Type;

abstract class AbstractParser implements ParserInterface
{
    protected ?Parser\PhpDocParser $parser = null;
    protected ?Lexer $lexer = null;

    abstract public function getReflection();

    public function __call(string $name, array $arguments)
    {
        $reflection = $this->getReflection();

        if (!\is_callable([$reflection, $name])) {
            foreach (['get'.ucfirst($name), 'is'.ucfirst($name), 'has'.ucfirst($name)] as $accessor) {
                if (\is_callable([$reflection, $accessor])) {
                    $name = $accessor;
                }
            }
        }

        if (\is_callable([$reflection, $name])) {
            return $reflection->{$name}(...$arguments);
        }

        throw new LogicException(sprintf('Method "%s::%s" does not exist.', static::class, $name));
    }

    public function __toString(): string
    {
        return $this->getReflection()->__toString();
    }

    public function getDocComment(): string|false
    {
        // cannot retrieve docComment from getPhpDoc because indent will be removed
        if (!$docComment = $this->getReflection()->getDocComment()) {
            return false;
        }

        // remove PHP comment syntax
        $docComment = $this->uncomment($docComment);

        // inheritdoc
        if (str_contains($docComment, '@inheritdoc') && ($inheritdoc = $this->getParentDoc())) {
            $docComment = preg_replace('/{?@inheritdoc}?/', $this->uncomment($inheritdoc), $docComment);
        }

        // remove tags (https://rubular.com/r/Fx0PuZ5d3DCLjU)
        // note: do not remove inline tags as they should be replaced in view
        return trim(preg_replace('/^@[a-zA-Z\-]+(?:\(".+"\)| .+)?$/m', '', $docComment));
    }

    public function getPhpDoc(): PhpDocNode
    {
        if (!$this->lexer) {
            $this->lexer = new Lexer();
        }

        if (!$this->parser) {
            $this->parser = new Parser\PhpDocParser(new Parser\TypeParser(new Parser\ConstExprParser()), new Parser\ConstExprParser());
        }

        $reflection = $this->getReflection();
        if (!$docComment = $reflection->getDocComment()) {
            return new PhpDocNode([]);
        }

        $tokens = new Parser\TokenIterator($this->lexer->tokenize($docComment));
        $phpDoc = $this->parser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);
        $docComment = $phpDoc->__toString();

        // replace tags
        $docComment = $this->replaceTag($phpDoc->getThrowsTagValues(), '@throws', $docComment);
        $docComment = $this->replaceTag($phpDoc->getReturnTagValues(), '@return', $docComment);
        $docComment = $this->replaceTag($phpDoc->getVarTagValues(), '@var', $docComment);
        $docComment = $this->replaceTag($phpDoc->getParamTagValues(), '@param', $docComment);
        $docComment = $this->replaceTag($phpDoc->getExtendsTagValues(), '@extends', $docComment);
        $docComment = $this->replaceTag($phpDoc->getImplementsTagValues(), '@implements', $docComment);

        // duplicate from getDocComment, but must inheritdoc here to inherit tags
        if (str_contains($docComment, '@inheritdoc') && ($inheritdoc = $this->getParentDoc(true))) {
            $docComment = preg_replace('/{?@inheritdoc}?/', $this->uncomment($inheritdoc), $docComment);
        }

        // Parse docComment after its modifications
        $tokens = new Parser\TokenIterator($this->lexer->tokenize($docComment));
        $phpDoc = $this->parser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        return $phpDoc;
    }

    protected function getParentDoc(bool $withTags = false): ?string
    {
        return null;
    }

    protected function getClassName(): string
    {
        return $this->getReflection()->getDeclaringClass()->getName();
    }

    /**
     * @param PhpDocTagValueNode[] $nodes
     */
    private function replaceTag(array $nodes, string $tag, string $docComment): string
    {
        $helper = new PhpStanTypeHelper();
        $namedFactory = new NameScopeFactory();
        $class = $this->getClassName();

        foreach ($nodes as $node) {
            $types = $helper->getTypes($node, $namedFactory->create($class));

            // no valid types found
            // node is not typed
            // node is generic
            if (!$types || !isset($node->type) || $node->type instanceof GenericTypeNode) {
                continue;
            }

            $nodeType = $node->type;

            if (1 === \count($types)) {
                $type = $types[0];
                $docComment = preg_replace(
                    sprintf('/%s %s/', $tag, preg_quote($nodeType->__toString(), '/')),
                    sprintf('%s %s', $tag, $type->getClassName() ?: $type->getBuiltinType()),
                    $docComment
                );
                continue;
            }

            // Foo|Bar => App\Foo|App\Bar
            // Foo&Bar => App\Foo&App\Bar
            $docComment = preg_replace(
                sprintf('/%s %s/', $tag, preg_quote($nodeType->__toString(), '/')),
                sprintf('%s %s', $tag, implode($nodeType instanceof UnionTypeNode ? '|' : '&', array_map(fn (Type $node) => $node->getClassName() ?: $node->getBuiltinType(), $types))),
                $docComment
            );
        }

        return $docComment;
    }

    /**
     * Uncomment a PHP comment string.
     *
     * @example https://rubular.com/r/CvFxBNzudoTZAl
     */
    protected function uncomment(string $string): string
    {
        return trim(preg_replace('/^ *\/\*\*| *\*[ \/]?/m', '', $string));
    }
}
