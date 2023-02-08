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

namespace PhpDocumentGenerator\Twig;

use PhpDocumentGenerator\Parser\Ast\Node;
use PhpDocumentGenerator\Parser\ClassParser;
use PhpDocumentGenerator\Parser\ParserInterface;
use PhpDocumentGenerator\Parser\TypeParser;
use PhpDocumentGenerator\Services\ConfigurationHandler;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use SplFileInfo;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    protected readonly Lexer $lexer;
    protected readonly Parser\PhpDocParser $parser;

    public function __construct(protected readonly ConfigurationHandler $configuration)
    {
        $this->lexer = new Lexer();
        $this->parser = new Parser\PhpDocParser(new Parser\TypeParser(new Parser\ConstExprParser()), new Parser\ConstExprParser());
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('md_sanitize', [$this, 'sanitize']),
            new TwigFilter('md_link', [$this, 'getLink']),
            new TwigFilter('md_url', [$this, 'getUrl']),
            new TwigFilter('md_value', [$this, 'formatValue']),
        ];
    }

    public function getLink(ParserInterface|Node|string $data, string $extension = 'md'): string
    {
        if ($data instanceof Node) {
            $nodeType = $data->getNode()->type;

            if ($nodeType instanceof UnionTypeNode || $nodeType instanceof IntersectionTypeNode) {
                return implode(
                    $nodeType instanceof UnionTypeNode ? '|' : '&',
                    array_map(fn (TypeNode $node) => $this->getLink($node->__toString()), $nodeType->types)
                );
            }

            return $this->getLink($nodeType->__toString());
        }

        $name = $data;

        if (\is_object($data)) {
            // class name
            $name = $data->getName();
        } elseif (file_exists($data)) {
            // reference or guide
            $name = pathinfo($data, \PATHINFO_FILENAME);
        }

        if (!\is_string($data) && !$data instanceof ClassParser && !$data instanceof TypeParser) {
            return sprintf('`%s`', $name);
        }

        $url = $this->getUrl($data, $extension);

        return $url ? sprintf('[`%s`](%s)', $name, $url) : sprintf('`%s`', $name);
    }

    public function getUrl(ClassParser|TypeParser|Node|string $data, string $extension = 'md'): ?string
    {
        // reference or guide
        if (\is_string($data) && file_exists($data)) {
            return str_replace([
                $this->configuration->get('references.output'),
                $this->configuration->get('guides.output'),
            ], [
                $this->configuration->get('references.base_url'),
                $this->configuration->get('guides.base_url'),
            ], $data);
        }

        // try to convert $data to ClassParser
        if (\is_string($data)) {
            try {
                $data = new ClassParser(new ReflectionClass($data));
            } catch (ReflectionException) {
            }
        }

        $name = \is_object($data) ? $data->getName() : $data;

        if ($data instanceof Node) {
            $nodeType = $data->getNode()->type;

            if ($nodeType instanceof UnionTypeNode || $nodeType instanceof IntersectionTypeNode) {
                throw new RuntimeException(sprintf('Unable to get a single url of multiple types for type "%s".', $data::class));
            }

            return $this->getUrl($nodeType->__toString(), $extension);
        }

        if ($data instanceof TypeParser && $data->isClass()) {
            $data = $data->getClass();
        }

        if ($data instanceof ClassParser) {
            // PHP
            if (!$data->isUserDefined()) {
                return sprintf('https://php.net/class.%s', strtolower($name));
            }

            // internal
            if (str_starts_with($name, $this->configuration->get('references.namespace').'\\')) {
                // calling ConfigurationHandler::isExcluded to ensure the target class is not ignored
                // from references generation because the target reference file may not exist yet
                if (!$this->configuration->isExcluded($data)) {
                    $file = new SplFileInfo($data->getFileName());

                    // get relative file path without extension (e.g.: Entity/Book)
                    $fileName = trim(sprintf('%s/%s', str_replace(sprintf('%s/%s', getcwd(), $this->configuration->get('references.src')), '', $file->getPath()), $file->getBasename('.'.$file->getExtension())), '/');

                    // get reference file path (e.g.: pages/references/Entity/Book.md)
                    $filePath = sprintf('%s/%s.%s', $this->configuration->get('references.output'), $fileName, $extension);

                    return $this->getUrl($filePath, $extension);
                }
            }
        }

        return null;
    }

    public function sanitize(string $string): string
    {
        // convert "@see" tags to link when possible
        if (preg_match_all('/{@see ([^}]+)}/', $string, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $value = $matches[1][$i];

                // HTTP link
                if (str_starts_with($value, 'https://') || str_starts_with($value, 'http://')) {
                    $value = sprintf('<%s>', $value);
                } else {
                    $value = $this->getLink($value);
                }

                $string = str_replace($match, sprintf('see %s', $value), $string);
            }
        }

        return $string;
    }

    public function formatValue($value)
    {
        return \is_array($value) ? json_encode($value) : $value;
    }
}
