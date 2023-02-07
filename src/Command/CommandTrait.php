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

namespace PhpDocumentGenerator\Command;

use RuntimeException;
use SplFileInfo;

trait CommandTrait
{
    private function loadTemplate(string $template): string
    {
        // check template
        if (!is_file($template)) {
            throw new RuntimeException(sprintf('Template file "%s" does not exist.', $template));
        }

        // load template dir in Twig
        $templatePath = pathinfo($template, \PATHINFO_DIRNAME);
        if (!\in_array($templatePath, $this->environment->getLoader()->getPaths(), true)) {
            $this->environment->getLoader()->addPath($templatePath);
        }

        // return the template file name without dir
        return pathinfo($template, \PATHINFO_BASENAME);
    }

    private function getNamespace(SplFileInfo $file): string
    {
        // Remove root path from file path
        $namespace = preg_replace(sprintf('#^%s%s?#i', $this->configuration->get('reference.src'), \DIRECTORY_SEPARATOR), '', $file->getPath());
        // Convert it to namespace format
        $namespace = str_replace(\DIRECTORY_SEPARATOR, '\\', $namespace);
        // Prepend main namespace
        $namespace = rtrim(sprintf('%s\\%s', $this->configuration->get('reference.namespace'), $namespace), '\\');

        return sprintf('%s\\%s', $namespace, $file->getBasename('.'.$file->getExtension()));
    }
}
