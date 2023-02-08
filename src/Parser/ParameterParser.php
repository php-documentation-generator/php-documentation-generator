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

use PhpDocumentGenerator\Parser\Ast\Node;
use ReflectionParameter;

final class ParameterParser extends AbstractParser
{
    public function __construct(private readonly ReflectionParameter $reflection)
    {
    }

    public function getType(): ?TypeParser
    {
        $reflection = $this->getReflection();

        return $reflection->hasType() ? new TypeParser($reflection->getType()) : null;
    }

    public function getAdditionalTypes(): ?Node
    {
        $reflection = $this->getReflection();

        // retrieve additional types from method doc
        foreach ((new MethodParser($reflection->getDeclaringFunction()))->getPhpDoc()->getParamTagValues() as $node) {
            if ($reflection->getName() === substr($node->parameterName, 1)) {
                return new Node($node);
            }
        }

        return null;
    }

    public function getReflection(): ReflectionParameter
    {
        return $this->reflection;
    }
}
