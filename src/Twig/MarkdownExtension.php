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

use PhpDocumentGenerator\Parser\ClassParser;
use PhpDocumentGenerator\Parser\ParserInterface;
use PhpDocumentGenerator\Services\ConfigurationHandler;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser;
use ReflectionClass;
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

    public function getLink(ParserInterface|PhpDocTagValueNode|string $data, string $referenceExtension = 'md'): string
    {
        $name = $data;

        if ($data instanceof PhpDocTagValueNode) {
            $name = $data->type->__toString();
        } elseif ($data instanceof ParserInterface) {
            $name = $data->getName();
        }

        $url = $this->getUrl($data, $referenceExtension);

        return $url ? sprintf('[`\%s`](%s)', ltrim($name, '\\'), $url) : sprintf('`%s`', $name);
    }

    public function getUrl(ParserInterface|PhpDocTagValueNode|string $data, string $referenceExtension = 'md'): ?string
    {
        if ($data instanceof PhpDocTagValueNode) {
            // todo is it possible to detect a class and convert it to ReflectionClass? (/!\ PHPStan does not resolve imports)
            $data = $data->type->__toString();
        }

        // try to convert $data to \ReflectionClass
        if (\is_string($data)) {
            try {
                $data = new ClassParser(new ReflectionClass($data));
            } catch (\ReflectionException) {
            }
        }

        if ($data instanceof ParserInterface) {
            $name = $data->getName();

            // Internal
            if (str_starts_with($name, $this->configuration->get('reference.namespace').'\\')) {
                // calling ConfigurationHandler::isExcluded to ensure the target class is not ignored
                // from references generation because the target reference file may not exist yet
                if (!$this->configuration->isExcluded($data)) {
                    $file = new SplFileInfo($data->getFileName());
                    $rootPath = getcwd();

                    // get relative file path without extension (e.g.: Entity/Book)
                    $fileName = trim(sprintf('%s/%s', str_replace(sprintf('%s/%s', $rootPath, $this->configuration->get('reference.src')), '', $file->getPath()), $file->getBasename('.'.$file->getExtension())), '/');

                    // get reference file path (e.g.: pages/references/Entity/Book.md)
                    $filePath = sprintf('%s/%s.%s', $this->configuration->get('target.directories.reference_path'), $fileName, $referenceExtension);

                    return sprintf('%s/%s', $this->configuration->get('target.base_url'), $filePath);
                }
            }

            // PHP
            if ($data instanceof ClassParser && !$data->isUserDefined()) {
                return sprintf('https://php.net/class.%s', strtolower($name));
            }

            $data = $name;
        }

        // Symfony
        if (str_starts_with($data, 'Symfony\\')) {
            return 'https://symfony.com/doc/current/index.html';
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
