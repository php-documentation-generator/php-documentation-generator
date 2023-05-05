<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpDocumentGenerator\Command;

use Symfony\Component\Filesystem\Path;
use Twig\Environment;

trait CommandTrait
{
    protected Environment $environment;

    private function loadTemplate(string $template): string
    {
        // check template
        if (!is_file($template)) {
            throw new \RuntimeException(sprintf('Template file "%s" does not exist.', $template));
        }

        // load template dir in Twig
        $templatePath = pathinfo($template, \PATHINFO_DIRNAME);
        if (!\in_array($templatePath, $this->environment->getLoader()->getPaths(), true)) {
            $this->environment->getLoader()->addPath($templatePath);
        }

        // return the template file name without dir
        return pathinfo($template, \PATHINFO_BASENAME);
    }

    private function getFQDNFromFile(\SplFileInfo $file, string $src, string $namespace): string
    {
        $relativeToSrc = Path::makeRelative($file->getPath(), $src);
        // Remove root path from file path
        // Convert it to namespace format
        $ns = str_replace(\DIRECTORY_SEPARATOR, '\\', $relativeToSrc);
        // Prepend main namespace
        $ns = rtrim(sprintf('%s\\%s', $namespace, $ns), '\\');

        return sprintf('%s\\%s', $ns, $file->getBasename('.'.$file->getExtension()));
    }
}
