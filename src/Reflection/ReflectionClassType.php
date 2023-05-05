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

enum ReflectionClassType: string
{
    case InterfaceType = 'Interface';
    case AttributeType = 'Attribute';
    case TraitType = 'Trait';
    case ClassType = 'Class';
    case EnumType = 'Enum';
}
