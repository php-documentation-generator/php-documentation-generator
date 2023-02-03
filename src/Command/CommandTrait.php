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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

trait CommandTrait
{
    private function getTemplateFile(string $templatePath, string $fileName): SplFileInfo
    {
        // check template path and load it in Twig
        if (!is_dir($templatePath)) {
            throw new RuntimeException(sprintf('Template directory "%s" does not exist.', $templatePath));
        }
        if (!\in_array($templatePath, $this->environment->getLoader()->getPaths(), true)) {
            $this->environment->getLoader()->addPath($templatePath);
        }

        // look for file in the template directory
        $iterator = (new Finder())->files()->in($templatePath)->name($fileName)->getIterator();
        $iterator->rewind();
        $templateFile = $iterator->current();
        if (!$templateFile) {
            throw new RuntimeException(sprintf('No "%s" file found in "%s" template directory.', $fileName, $templatePath));
        }

        return $templateFile;
    }

    private function getNamespace(\SplFileInfo $file): string
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
