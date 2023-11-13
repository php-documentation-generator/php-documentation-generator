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
        $id = 'section-'.$i;
        $texte = implode('', $section['text']).\PHP_EOL;
        $code = implode('', $section['text']).\PHP_EOL;
        $code = '';
        if ($section['code'] ?? null) {
            $code .= \PHP_EOL;
            $code .= '```php'.\PHP_EOL;
            $code .= implode('', $section['code']).\PHP_EOL;
            $code .= '```'.\PHP_EOL;
            $code .= \PHP_EOL;
        }

        $t .= <<<HTML
<div class="grid grid-cols-1 py-4 group xl:grid-cols-2 pt-8">
      <div class="flex-1 flex flex-row border-b-gray-300 dark:border-blue-dark xl:border-b-px border-dotted group-last:border-0 xl:mr-8 xl:pb-8 ">
        <div class="relative w-full xl:pr-8">
          <a
            id="$id"
            href="#${id}"
            class="absolute right-full -translate-x-1 top-1 pt-24 -mt-24 opacity-0 group-hover:opacity-100 duration-500 transition-all"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              strokeWidth={1.5}
              class="w-4 h-4 stroke-blue"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"
              />
            </svg>
          </a>
          $texte
        </div>
      </div>
      <div>$code</div>
    </div>
HTML;
    }

    return $t;
};
