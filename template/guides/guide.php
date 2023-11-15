<?php

use Symfony\Component\Yaml\Yaml;

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
    $stop = false;
    foreach ($headers as $header) {
        if (str_contains($header, '---')) {
            if ($stop) {
                continue;
            }

            $stop = true;
        }

        $t .= $header;
    }

    $t .= 'type: guides' . PHP_EOL;

    $s = [];
    foreach ($sections as $i => $section) {
        $temp = [];
        $temp['id'] = 'section-'.$i;
        $temp['text'] = implode('', $section['text']).\PHP_EOL;
        if ($section['code'] ?? null) {
            $temp['code'] = '';
            $temp['code'] .= '```php'.\PHP_EOL;
            $temp['code'] .= implode('', $section['code']).\PHP_EOL;
            $temp['code'] .= '```'.\PHP_EOL;
        }
        $s[] = $temp;
    }

    $t .= 'sections: '.PHP_EOL;
    foreach (explode(PHP_EOL, Yaml::dump($s, 2, 2)) as $line) {
        $t .= '  ' . $line . PHP_EOL;
    }

    $t .= '---';

    return $t;
};
