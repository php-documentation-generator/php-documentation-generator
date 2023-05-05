<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return static function (array $headers, array $sections): string {
    $t = '';
    $t .= implode('', $headers);
    foreach ($sections as $i => $section) {
        $t .= '<SectionGuide id="section-'.$i.'">' . \PHP_EOL;
        $t .= implode('', $section['text']).\PHP_EOL;
        if ($section['code'] ?? null) {
            $t .= '```php'.\PHP_EOL;
            $t .= implode('', $section['code']).\PHP_EOL;
            $t .= '```'.\PHP_EOL;
        }
        $t .= '</SectionGuide>'.PHP_EOL;
    }

    return $t;
};
