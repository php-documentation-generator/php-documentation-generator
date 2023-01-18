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

namespace PDG\Services\Reference\Reflection;

/**
 * Contains helper methods applicable to different kinds of Reflection objects.
 */
trait ReflectionHelperTrait
{
    private function getModifier(\ReflectionMethod|\ReflectionProperty $reflection): string
    {
        return implode(' ', \Reflection::getModifierNames($reflection->getModifiers()));
    }

    private function getParameterName(\ReflectionParameter $parameter): string
    {
        return $parameter->isPassedByReference() ? '&$'.$parameter->getName() : '$'.$parameter->getName();
    }
}
