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

namespace ApiPlatform\PDGBundle\Parser;

use RuntimeException;

/**
 * Import use statements.
 *
 * @see https://gist.github.com/Zeronights/7b7d90fcf8d4daf9db0c
 */
trait UseStatementTrait
{
    private ?array $useStatements = null;

    public function getUseStatements(): array
    {
        // use statements already parsed
        if (null !== $this->useStatements) {
            return $this->useStatements;
        }

        if (!$this->isUserDefined()) {
            throw new RuntimeException('Must parse use statements from user defined classes.');
        }

        return $this->useStatements = $this->tokenizeSource($this->readFileSource());
    }

    public function hasUseStatement($class)
    {
        $useStatements = $this->getUseStatements();

        return
            array_search($class, array_column($useStatements, 'class'), true) ||
            array_search($class, array_column($useStatements, 'as'), true);
    }

    private function readFileSource(): string
    {
        $file = fopen($this->getFileName(), 'r');
        $line = 0;
        $source = '';

        while (!feof($file)) {
            ++$line;

            if ($line >= $this->getStartLine()) {
                break;
            }

            $source .= fgets($file);
        }

        fclose($file);

        return $source;
    }

    private function tokenizeSource($source)
    {
        $tokens = token_get_all($source);

        $builtNamespace = '';
        $buildingNamespace = false;
        $matchedNamespace = false;

        $useStatements = [];
        $record = false;
        $currentUse = [
            'class' => '',
            'as' => '',
        ];

        foreach ($tokens as $token) {
            if (\T_NAMESPACE === $token[0]) {
                $buildingNamespace = true;

                if ($matchedNamespace) {
                    break;
                }
            }

            if ($buildingNamespace) {
                if (';' === $token) {
                    $buildingNamespace = false;
                    continue;
                }

                switch ($token[0]) {
                    case \T_STRING:
                    case \T_NS_SEPARATOR:
                        $builtNamespace .= $token[1];
                        break;
                }

                continue;
            }

            if (';' === $token || !\is_array($token)) {
                if ($record) {
                    $useStatements[] = $currentUse;
                    $record = false;
                    $currentUse = [
                        'class' => '',
                        'as' => '',
                    ];
                }

                continue;
            }

            if (\T_CLASS === $token[0]) {
                break;
            }

            if (0 === strcasecmp($builtNamespace, $this->getNamespaceName())) {
                $matchedNamespace = true;
            }

            if ($matchedNamespace) {
                if (\T_USE === $token[0]) {
                    $record = 'class';
                }

                if (\T_AS === $token[0]) {
                    $record = 'as';
                }

                if ($record) {
                    switch ($token[0]) {
                        case \T_STRING:
                        case \T_NS_SEPARATOR:

                            if ($record) {
                                $currentUse[$record] .= $token[1];
                            }

                            break;
                    }
                }
            }

            if ($token[2] >= $this->getStartLine()) {
                break;
            }
        }

        // Make sure the as key has the name of the class even
        // if there is no alias in the use statement.
        foreach ($useStatements as &$useStatement) {
            if (empty($useStatement['as'])) {
                $useStatement['as'] = basename($useStatement['class']);
            }
        }

        return $useStatements;
    }
}
