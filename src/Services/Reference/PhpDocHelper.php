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

namespace PDG\Services\Reference;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ReflectionException;

class PhpDocHelper
{
    private readonly PhpDocParser $parser;
    private readonly Lexer $lexer;

    public function __construct(
        private readonly OutputFormatter $outputFormatter
    ) {
        $this->lexer = new Lexer();
        $this->parser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
    }

    public function getPropertiesConstructorDocumentation(\ReflectionClass $reflectionClass): array
    {
        $propertiesConstructorDocumentation = [];
        if ($reflectionClass->hasMethod('__construct')) {
            $constructorDocumentation = $this->getPhpDoc($reflectionClass->getMethod('__construct'));
            foreach ($constructorDocumentation->getParamTagValues() as $paramTagValueNode) {
                $propertiesConstructorDocumentation[substr($paramTagValueNode->parameterName, 1)] = $paramTagValueNode;
            }
        }

        return $propertiesConstructorDocumentation;
    }

    public function handleClassDoc(\ReflectionClass $class, string $content): string
    {
        $rawDocNode = $class->getDocComment();

        if (!$rawDocNode) {
            return $content;
        }
        $tokens = new TokenIterator($this->lexer->tokenize($rawDocNode));
        $phpDocNode = $this->parser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);
        $text = array_filter($phpDocNode->children, static function (PhpDocChildNode $child): bool {
            return $child instanceof PhpDocTextNode;
        });

        /** @var PhpDocTextNode $t */
        foreach ($text as $t) {
            // @TODO: this should be handled by the Javascript using `md` files as `mdx` we should not need this here
            // indeed {@see} breaks mdx as it thinks that its a React component
            if (str_contains($t->text, '@see')) {
                $t = str_replace(['{@see', '}'], ['see', ''], $t->text);
            }
            $content .= $t.\PHP_EOL;
        }

        return $content;
    }

    public function getPhpDoc(\ReflectionMethod|\ReflectionProperty|\ReflectionClassConstant $reflection): PhpDocNode
    {
        if (!($docComment = $reflection->getDocComment())) {
            return new PhpDocNode([]);
        }

        $tokens = new TokenIterator($this->lexer->tokenize($docComment));
        $v = $this->parser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        return $v;
    }

    public function containsInheritDoc(PhpDocTextNode $textNode): bool
    {
        return str_contains($textNode->text, '{@inheritdoc}');
    }

    public function getInheritedDoc(\ReflectionMethod $method): string
    {
        $content = '';
        try {
            $parent = $method->getPrototype();
            // if it is a core php method, no need to look for phpdoc
            if (!$parent->isUserDefined()) {
                return $content;
            }
            $parentDoc = $this->getPhpDoc($parent);

            return $this->outputFormatter->printTextNodes($parentDoc, $this->outputFormatter->printThrowTags($parentDoc, $content));
        } catch (ReflectionException) {
            return $content;
        }
    }

    public function classDocContainsTag(\ReflectionClass $class, string $searchedTag): bool
    {
        $doc = $class->getDocComment();
        if (!$doc) {
            return false;
        }
        $tokens = new TokenIterator($this->lexer->tokenize($doc));
        $phpDocNode = $this->parser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);
        $tags = array_filter($phpDocNode->children, static function (PhpDocChildNode $childNode): bool {
            return $childNode instanceof PhpDocTagNode;
        });
        /** @var PhpDocTagNode $tag */
        foreach ($tags as $tag) {
            if ($searchedTag === $tag->name) {
                return true;
            }
        }

        return false;
    }
}
