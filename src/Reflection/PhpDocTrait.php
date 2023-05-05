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

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser;
use PhpDocumentGenerator\Link\LinkInterface;

trait PhpDocTrait
{
    private $phpDoc;

    private function getPhpDoc(string $docComment): PhpDocNode
    {
        if ($this->phpDoc) {
            return $this->phpDoc;
        }

        $lexer = new Lexer();
        $parser = new Parser\PhpDocParser(new Parser\TypeParser(new Parser\ConstExprParser()), new Parser\ConstExprParser());
        $tokens = new Parser\TokenIterator($lexer->tokenize($docComment));

        return $this->phpDoc = $parser->parse($tokens);
    }

    private function transformSeeTags(string $text): string
    {
        if (!$text) {
            return '';
        }

        preg_match_all('|{@see ([^}]+)}|', $text, $matches);

        foreach($matches[0] ?? [] as $key => $match) {
            $text = str_replace($match, $this->transformToLink($matches[1][$key]), $text);
        }

        return $text;
    }

    private function transformToLink(string $v, bool $linkInterface = false): string|LinkInterface {
        if (class_exists($cl = $this->getDeclaringClass()->getNamespaceName() . '\\' . $v)) {
            $v = $cl;
        }

        if (class_exists($v)) {
            $c = new ReflectionClass($v, $this->linkContext);
            if ($linkInterface) {
                return $c;
            }

            if(!$c->getLink()) {
                return (string) $c;
            }

            return sprintf('<a href="%s">%s</a>', $c->getLink(), (string) $c);
        }

        if (strpos($v, '::')) {
            [$class, $method] = explode('::', $v);

            if (class_exists($cl = $this->getDeclaringClass()->getNamespaceName() . '\\' . $class)) {
                $class = $cl;
            }

            if (class_exists($class)) {
                $c =  new ReflectionMethod($class, $method, $this->linkContext);
                if ($linkInterface) {
                    return $c;
                }

                if(!$c->getLink()) {
                    return (string) $c;
                }

                return sprintf('<a href="%s">%s</a>', $c->getLink(), (string) $c);
            }
        }

        if ($linkInterface) {
            return new class($v) implements LinkInterface {
                public function __construct(private string $v) {}
                public function getLink(): ?string {
                    return $this->v;
                }

                public function __toString() {
                    return $this->v;
                }
            };
        }

        return (string) $v;
    }

    public function getSeeAlso(): array
    {
        if (!$this->getDocComment()) {
            return [];
        }

        $seeAlso = [];
        foreach($this->getPhpDoc($this->getDocComment()) as $node) {
            foreach ($node as $t) {
                if(!$t instanceof PhpDocTagNode || $t->name !== '@see') {
                    continue;
                }

                $v = (string) $t->value;
                $seeAlso[] = $this->transformToLink($v, true);
            }
        }

        return $seeAlso;
    }
}
