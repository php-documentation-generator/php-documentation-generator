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

use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use ReflectionParameter;

final class ParameterParser extends AbstractParser
{
    public function __construct(private readonly ReflectionParameter $reflection)
    {
    }

    public function getType(): ?TypeParser
    {
        $reflection = $this->getReflection();

        if ($reflection->hasType()) {
            return new TypeParser($reflection->getType());
        }

        return null;
    }

    public function getAdditionalTypes(): ?ParamTagValueNode
    {
        $reflection = $this->getReflection();

        // retrieve additional types from method doc
        $phpDoc = (new MethodParser($reflection->getDeclaringFunction()))->getPhpDoc();
        foreach ($phpDoc->getParamTagValues() as $param) {
            if ($reflection->getName() === substr($param->parameterName, 1)) {
                return $param;
            }
        }

        return null;
    }

    public function getReflection(): ReflectionParameter
    {
        return $this->reflection;
    }
}
