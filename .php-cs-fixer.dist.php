<?php

declare(strict_types=1);

$header = <<<'HEADER'
This file is part of the PHP Documentation Generator project

(c) Antoine Bluchet <soyuka@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'tests/Fixtures/guides'
    ])
    ;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'align_multiline_comment' => [
            'comment_type' => 'phpdocs_like',
        ],
        'array_indentation' => true,
        'compact_nullable_typehint' => true,
        'explicit_indirect_variable' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => false,
        ],
        'header_comment' => [
            'header' => $header,
            'location' => 'after_open',
        ],
        'logical_operators' => true,
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'no_alternative_syntax' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'break',
                'continue',
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => false,
        ],
        'no_unset_cast' => true,
        'no_unset_on_property' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => [
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'php_unit_method_casing' => [
            'case' => 'camel_case',
        ],
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_test_annotation' => [
            'style' => 'prefix',
        ],
        'phpdoc_add_missing_param_annotation' => [
            'only_untyped' => true,
        ],
        'phpdoc_no_alias_tag' => true,
        'phpdoc_order' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_var_annotation_correct_order' => true,
        'return_assignment' => true,
        'strict_param' => true,
        'visibility_required' => [
            'elements' => [
                'const',
                'method',
                'property',
            ],
        ],
    ])
    ->setFinder($finder);
