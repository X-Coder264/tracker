<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->name('*.php')
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRules([
        'psr_autoloading' => true,
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'blank_line_after_namespace' => true,
        'braces' => true,
        'class_definition' => true,
        'declare_strict_types' => true,
        'elseif' => true,
        'function_declaration' => true,
        'indentation_type' => true,
        'line_ending' => true,
        'constant_case' => ['case' => 'lower'],
        'lowercase_keywords' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'no_break_comment' => true,
        'no_closing_tag' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_spaces_after_function_name' => true,
        'no_spaces_inside_parenthesis' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
        'single_blank_line_before_namespace' => true,
        'single_class_element_per_statement' => true,
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,
        'single_trait_insert_per_statement' => true,
        'short_scalar_cast' => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'single_quote' => true,
        'strict_param' => true,
        'visibility_required' => true,
        'encoding' => true,
        'full_opening_tag' => true,
        'phpdoc_align' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => true,
        'php_unit_construct' => true,
        'php_unit_dedicate_assert_internal_type' => true,
        'php_unit_strict' => true,
        'return_type_declaration' => true,
        'no_superfluous_phpdoc_tags' => true,
        'ordered_imports'            => [
            'sort_algorithm' => 'alpha',
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
        ],

    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
