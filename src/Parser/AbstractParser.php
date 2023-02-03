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

use LogicException;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser;

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
        $reflection = $this->getReflection();

        if (!\is_callable([$reflection, 'getDocComment'])) {
            throw new LogicException(sprintf('Method "%s::getDocComment" is not callable.', $reflection::class));
        }

        if (false === ($docComment = $reflection->getDocComment())) {
            return false;
        }

        // inheritdoc
        if ($docComment && str_contains($docComment, '@inheritdoc')) {
            $docComment = $this->inheritDoc($docComment);
        }

        // todo check "@see" tags to import absolute namespace if available (/!\ PHPStan does not resolve imports)

        return $docComment;
    }

    public function getSummary(): string|false
    {
        if (!$docComment = $this->getDocComment()) {
            return false;
        }

        // remove tags (including "@SuppressWarnings(...)")
        $docComment = preg_replace('/@[a-zA-Z]+(?:(?:\s+.+)|(?:\(".+"\)))/', '', $docComment);

        // remove PHP comment syntax
        return trim(preg_replace('#[\/ ]{0,}\*{1,2} ?\/?#i', '', $docComment));
    }

    public function getPhpDoc(): PhpDocNode
    {
        if (!$this->lexer) {
            $this->lexer = new Lexer();
        }

        if (!$this->parser) {
            $this->parser = new Parser\PhpDocParser(new Parser\TypeParser(new Parser\ConstExprParser()), new Parser\ConstExprParser());
        }

        $docComment = $this->getDocComment();
        if (!$docComment) {
            return new PhpDocNode([]);
        }

        $tokens = new Parser\TokenIterator($this->lexer->tokenize($docComment));
        $phpDoc = $this->parser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        return $phpDoc;
    }

    protected function inheritDoc(string $docComment): string
    {
        return $docComment;
    }
}
