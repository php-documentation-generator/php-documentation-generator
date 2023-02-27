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

use PhpDocumentGenerator\Services\PhpStanTypeHelper;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Symfony\Component\PropertyInfo\PhpStan\NameScopeFactory;
use Symfony\Component\PropertyInfo\Type;

trait ParserUtilsTrait
{
    private ?PhpStanTypeHelper $helper = null;
    private ?NameScopeFactory $namedFactory = null;

    /**
     * @param PhpDocTagValueNode[] $nodes
     */
    private function replaceTag(string $class, array $nodes, string $tag, string $docComment): string
    {
        if (!$this->helper) {
            $this->helper = new PhpStanTypeHelper();
        }

        if (!$this->namedFactory) {
            $this->namedFactory = new NameScopeFactory();
        }

        foreach ($nodes as $node) {
            $types = $this->helper->getTypes($node, $this->namedFactory->create($class));

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
    private function uncomment(string $string): string
    {
        return trim(preg_replace('/^ *\/\*\*| *\*[ \/]?/m', '', $string));
    }
}
