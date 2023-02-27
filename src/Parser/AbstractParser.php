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
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser;

abstract class AbstractParser implements ParserInterface
{
    use ParserUtilsTrait;

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
        if (str_contains($docComment, '@inheritdoc') && ($inheritdoc = $this->getParentDocComment())) {
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
        $docComment = $this->replaceTag($this->getClassName(), $phpDoc->getThrowsTagValues(), '@throws', $docComment);
        $docComment = $this->replaceTag($this->getClassName(), $phpDoc->getReturnTagValues(), '@return', $docComment);
        $docComment = $this->replaceTag($this->getClassName(), $phpDoc->getVarTagValues(), '@var', $docComment);
        $docComment = $this->replaceTag($this->getClassName(), $phpDoc->getParamTagValues(), '@param', $docComment);
        $docComment = $this->replaceTag($this->getClassName(), $phpDoc->getExtendsTagValues(), '@extends', $docComment);
        $docComment = $this->replaceTag($this->getClassName(), $phpDoc->getImplementsTagValues(), '@implements', $docComment);

        // seems duplicate from getDocComment, but it's not.
        // "getDocComment" calls "$this->getParentDocComment" to get the docComment only,
        // here "$this->getParentPhpDoc" is called to get a phpDoc string with inherited tags
        if (str_contains($docComment, '@inheritdoc') && ($inheritdoc = $this->getParentPhpDoc())) {
            $docComment = preg_replace('/{?@inheritdoc}?/', $this->uncomment($inheritdoc), $docComment);
        }

        // parse the updated docComment after its modifications to get the phpDoc object
        $tokens = new Parser\TokenIterator($this->lexer->tokenize($docComment));
        $phpDoc = $this->parser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        return $phpDoc;
    }

    protected function getParentPhpDoc(): ?string
    {
        return null;
    }

    protected function getParentDocComment(): ?string
    {
        return null;
    }

    protected function getClassName(): string
    {
        return $this->getReflection()->getDeclaringClass()->getName();
    }
}
