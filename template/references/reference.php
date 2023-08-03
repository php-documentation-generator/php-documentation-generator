<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpDocumentGenerator\Link\LinkInterface;
use PhpDocumentGenerator\Reflection\ReflectionClass;
use PhpDocumentGenerator\Reflection\ReflectionMethod;
use PhpDocumentGenerator\Reflection\ReflectionProperty;

if (!function_exists('href')) {
    function href(LinkInterface $c): string
    {
        if (!$c->getLink()) {
            return (string) $c;
        }

        return sprintf('`<a href="%s">%s</a>`', $c->getLink(), (string) $c);
    }
}

if (!function_exists('mdLink')) {
    function mdLink(LinkInterface $c): string
    {
        if (!$c->getLink()) {
            return (string) $c;
        }

        return sprintf('[%s](%s)', (string) $c, $c->getLink());
    }
}

if (!function_exists('typesToString')) {
    function typesToString(array $types, string $separator = '|', bool $inCode = false): array
    {
        $strTypes = [];

        foreach ($types as $type) {
            if ($type->isCollection() && $type->getCollectionKeyTypes()) {
                $values = $type->getCollectionValueTypes() ? ', '.implode(\PHP_EOL, typesToString($type->getCollectionValueTypes(), $separator)) : ', mixed';
                $strTypes[] = sprintf('array%s%s%s%s', $inCode ? '<' : '&lt;', implode(\PHP_EOL, typesToString($type->getCollectionKeyTypes(), $separator)), $values, $inCode ? '>' : '&gt;');
                continue;
            }

            $strTypes[] = href($type);
        }

        return $strTypes;
    }
}

if (!function_exists('getMethodParameters')) {
    function getMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $separator = $parameter->getTypeSeparator();
            $types = typesToString($parameter->getTypes(), $separator, inCode: true);
            $prefix = $parameter->getType()?->allowsNull() ? 'null'.$separator : '';
            $parameters[] = sprintf('%s$%s', $types ? $prefix.implode($separator, $types).' ' : '', $parameter->name);
        }

        return $parameters;
    }
}

if (!function_exists('getPropertyDoc')) {
    function getPropertyDoc(ReflectionClass $c): string
    {
        $mdx = '';

        foreach ($c->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $property) {
            $t = typesToString($property->getTypes());
            $type = $t ? sprintf('%s ', implode('|', $t)) : '';

            $mdx .= <<<MDX

    ### {$property->getName()}

    ```php
    {$type}\${$property->getName()}
    ```

    {$property->getDescription()}

    MDX;
        }

        return $mdx;
    }
}

if (!function_exists('getMethodDoc')) {
    function getMethodDoc(ReflectionClass $c): string
    {
        $mdx = '';

        foreach ($c->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_ABSTRACT) as $method) {
            if ($method->getDeclaringClass()->getName() !== $c->getName()) {
                continue;
            }

            $returnTypes = $method->getReturnTypes();
            $returnType = $returnTypes ? ': '.implode('|', typesToString($returnTypes)) : '';
            $methodParameters = implode(', ', getMethodParameters($method));

            $mdx .= <<<MDX

    ### {$method->getName()}

    {$method->getDescription()}

    ```php
    {$method->visibility} {$method->name}({$methodParameters}){$returnType}
    ```

    MDX;

            if ($method->getParameters()) {
                $mdx .= <<<MDX

    #### Parameters

    <table><tbody>

    MDX;

                foreach ($method->getParameters() as $parameter) {
                    $mdx .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>', $parameter->getName(), implode('<br />', typesToString($parameter->getTypes(), '|')), $parameter->getDescription());
                }

                $mdx .= <<<MDX

    </tbody>
    </table>

    MDX;
            }

            if ($method->getReturnType()) {
                $str = implode('<br />', typesToString($method->getReturnTypes()));

                $mdx .= <<<MDX

    #### Returns

    {$str}

    MDX;
            }

            if ($seeAlso = $method->getSeeAlso()) {
                $mdx .= <<<MDX

    #### See also

    MDX;

                foreach ($seeAlso as $link) {
                    $mdx .= sprintf('- %s%s%2$s', mdLink($link), \PHP_EOL);
                }
            }
        }

        return $mdx;
    }
}

$attributeTemplate = static function (ReflectionClass $c) {
    $mdx = <<<MDX

<table>
    <thead><tr><td>Option</td><td>Types</td><td>Description</td></tr></thead>
    <tbody>
MDX;

    foreach ($c->getMethod('__construct')->getParameters() as $parameter) {
        $separator = $parameter->getTypeSeparator();
        $types = typesToString($parameter->getTypes(), '<br />');
        $mdx .= sprintf('%s<tr><td>%s</td><td>%s</td><td>%s</td></tr>%1$s', \PHP_EOL, href($parameter), implode('<br />', $types), $parameter->getDescription());
    }

    $mdx .= <<<MDX

</tbody></table>


## Description


{$c->getDescription()}


## Options


MDX;

    $mdx .= getPropertyDoc($c);

    return $mdx;
};

$classTemplate = static function (ReflectionClass $c) {
    $s = $c->builtinType.' '.$c->name;

    if ($c->getParentClass()) {
        $s .= ' extends '.$c->getParentClass()->name;
    }

    if ($c->getInterfaces()) {
        $s .= ' implements '.implode(', ', array_map(fn ($i): string => href($i), $c->getInterfaces()));
    }

    $methods = [];

    foreach ($c->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_ABSTRACT) as $method) {
        if ($method->isPrivate()) {
            continue;
        }

        $returnTypes = $method->getReturnTypes();
        $returnType = $returnTypes ? ': '.implode('|', typesToString($returnTypes)) : '';
        $methodParameters = implode(', ', getMethodParameters($method));
        $methods[] = "    {$method->visibility} {$method->name}({$methodParameters}){$returnType}";
    }

    $classMethods = implode(\PHP_EOL, $methods);

    $mdx = <<<MDX
{$c->getDescription()}

```php
{$s}
{
{$classMethods}
}
```
MDX;

    // TODO: find a way to find the class that implements this interface
    // if ($c->isInterface()) {
    //     $mdx .= implode('<br />', getClassImplements($c));
    // }

    if ($seeAlso = $c->getSeeAlso()) {
        $mdx = <<<MDX
## See also

MDX;

        foreach ($seeAlso as $link) {
            $mdx .= sprintf('- %s%s%2$s', mdLink($link), \PHP_EOL);
        }
    }

    if ($c->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED)) {
        $mdx .= <<<MDX

## Properties
MDX;
        $mdx .= getPropertyDoc($c);
    }

    if ($c->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_ABSTRACT | ReflectionMethod::IS_PROTECTED)) {
        $mdx .= <<<MDX

## Methods

MDX;

        $mdx .= getMethodDoc($c);
    }

    return $mdx;
};

return static function (ReflectionClass $c) use ($attributeTemplate, $classTemplate): string {
    $mdx = <<<MDX
---
type: {$c->type}
---

# [{$c->name}]({$c->getLink()})


MDX;

    $mdx .= match ($c->type) {
        'Attribute' => $attributeTemplate($c),
        'Class' => $classTemplate($c),
        'Trait' => $classTemplate($c),
        'Interface' => $classTemplate($c),
        default => throw new RuntimeException('Unsupported '.$c->type)
    };

    return $mdx;
};
