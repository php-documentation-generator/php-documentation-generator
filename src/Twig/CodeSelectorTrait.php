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

// TODO: we could actually do this in twig using a CodeExampleView
trait CodeSelectorTrait
{
    public function handleCodeSelector(string $phpDoc): string
    {
        // Handle codeSelector
        $blocks = preg_split('/(\[codeSelector\][\s\S\w\n]*?\[\/codeSelector\])/', $phpDoc, 0, \PREG_SPLIT_DELIM_CAPTURE);
        $string = '';
        foreach ($blocks as $block) {
            if (str_contains($block, 'codeSelector')) {
                $string .= $this->formatCodeSelector($block);
                continue;
            }

            $string .= $block;
        }

        return trim($string);
    }

    private function formatCodeSelector(string $string): string
    {
        $codeSelectorId = uniqid();
        $inputs = '';
        $nav = '<ul class="code-selector-nav">'.\PHP_EOL;

        if (false !== preg_match_all('/```(\w+)/', $string, $languages) && $languages) {
            foreach ($languages[1] as $k => $language) {
                $defaultChecked = 0 === $k ? 'defaultChecked' : '';
                $inputs .= '<input type="radio" id="'.$codeSelectorId.'-'.$language.'" name="'.$codeSelectorId.'-code-tabs" '.$defaultChecked.' />'.\PHP_EOL;
                $nav .= '<label for="'.$codeSelectorId.'-'.$language.'">'.$language.'</label>'.\PHP_EOL;
            }
            $nav .= '</ul>'.\PHP_EOL;
        }

        $string = preg_replace(
            '/\[codeSelector\]([\w\s\S\n]*?)\[\/codeSelector\]/i',
            '<div class="code-selector">'.\PHP_EOL.$inputs.$nav.'${1}'.\PHP_EOL.'</div>'.\PHP_EOL,
            $string,
        );

        return preg_replace(
            '/(```\w+\n[\w\s\S\n]*?```)/i',
            '<div class="code-selector-content">'.\PHP_EOL.'${1}'.\PHP_EOL.'</div>'.\PHP_EOL,
            $string,
        );
    }
}
