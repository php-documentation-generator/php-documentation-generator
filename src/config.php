<?php

$config = function(): array {
    // PDG: PHP Documentation Generator
    $configFile = getenv('PHP_DOC_CONFIG') ?: getcwd() . '/pdg.config.json';

    // https://gist.github.com/1franck/5076758
    $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', file_get_contents($configFile));
    $config = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        fwrite(STDERR, sprintf('JSON error "%s" while reading "%s"', json_last_error_msg(), $configFile));
        exit(1);
    }

    return $config;
};

assert(is_array($config()));

return $config;
