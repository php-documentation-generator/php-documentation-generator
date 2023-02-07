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

namespace PhpDocumentGenerator\Services;

use PhpDocumentGenerator\DependencyInjection\Configuration;
use PhpDocumentGenerator\Parser\ClassParser;
use PhpDocumentGenerator\Parser\ParserInterface;
use Reflector;
use RuntimeException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

/**
 * Read user configuration on runtime.
 */
final class ConfigurationHandler
{
    private array $config = [];

    public function __construct()
    {
        $cwd = getcwd();

        // First, load config file from PDG_CONFIG_FILE environment variable
        $configFile = getenv('PDG_CONFIG_FILE');
        if ($configFile && !is_file($configFile)) {
            throw new RuntimeException(sprintf('Configuration file "%s" does not exist.', $configFile));
        }

        // If PDG_CONFIG_FILE environment variable is not set, try to load config file from default ordered ones
        if (!$configFile) {
            $files = [
                'pdg.config.yaml',
                'pdg.config.yml',
                'pdg.config.dist.yaml',
                'pdg.config.dist.yml',
            ];

            foreach ($files as $filename) {
                if (is_file(sprintf('%s%s%s', $cwd, \DIRECTORY_SEPARATOR, $filename))) {
                    $configFile = $filename;
                    break;
                }
            }
        }

        // No config file detected
        if (!$configFile) {
            throw new RuntimeException('Configuration file "pdg.config.yaml" does not exist.');
        }

        // Config file detected: read it and parse it
        $this->config = (new Processor())->processConfiguration(new Configuration(), Yaml::parse(file_get_contents($configFile)));

        // Autoload project autoloader
        $autoload = sprintf('%s%s%s', $cwd, \DIRECTORY_SEPARATOR, $this->config['autoload']);
        if (!file_exists($autoload)) {
            throw new RuntimeException(sprintf('Autoload file "%s" does not exist.', $autoload));
        }

        require_once $autoload;
    }

    public function get(string $name, $default = null): mixed
    {
        // Convert "foo.bar.baz" in "['foo' => ['bar' => ['baz' => ...]]]"
        $config = $this->config;
        $keys = explode('.', $name);
        foreach ($keys as $key) {
            if (\array_key_exists($key, $config)) {
                $config = $config[$key];
                continue;
            }

            return $default;
        }

        return \is_string($config) ? rtrim($config, '/\\') : $config;
    }

    public function isExcluded(Reflector|ParserInterface $reflection): bool
    {
        $tagsToIgnore = $this->get('references.patterns')['class_tags_to_ignore'] ?? ['@internal', '@experimental'];
        $fileName = $reflection->getFileName();

        foreach ($this->get('references.patterns.exclude') as $rule) {
            if (preg_match(sprintf('/%s/', preg_quote($rule)), $fileName) || $reflection->isExcluded()) {
                return true;
            }

            if (!$reflection instanceof ClassParser) {
                continue;
            }

            foreach ($tagsToIgnore as $tagToIgnore) {
                if ($reflection->hasTag($tagToIgnore)) {
                    return true;
                }
            }
        }

        return false;
    }
}
