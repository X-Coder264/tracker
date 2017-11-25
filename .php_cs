<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude(['vendor', 'storage', 'resources', 'public', 'bootstrap', 'config'])
    ->files()
    ->name('*.php')
;

return PhpCsFixer\Config::create()
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_useless_return' => true,
        'ordered_imports' => ['sortAlgorithm' => 'length'],
        'strict_comparison' => true,
        'yoda_style' => true,
    ])
;
