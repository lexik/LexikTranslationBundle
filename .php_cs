<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('bin')
    ->exclude('vendor')
    ->in(__DIR__)
;

$fixers = array(
    'double_arrow_multiline_whitespaces',
    'duplicate_semicolon',
    'extra_empty_lines',
    'include',
    'multiline_array_trailing_comma',
    'namespace_no_leading_whitespace',
    'new_with_braces',
    'object_operator',
    'operators_spaces',
    'remove_leading_slash_use',
    'remove_lines_between_uses',
    'spaces_cast',
    'standardize_not_equal',
    'ternary_spaces',
    'whitespacy_lines',
    'align_double_arrow',
);

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers($fixers)
    ->finder($finder)
;
