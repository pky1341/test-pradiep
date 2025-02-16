<?php

function convertCamelCase(string $input): string
{
    if (!is_string($input)) {
        throw new InvalidArgumentException('Input must be a string.');
    }

    $converted = preg_replace('/(?<!^)([A-Z])/', ' $1', $input);

    $converted = strtolower($converted);

    return trim($converted);
}

try {
    echo convertCamelCase("camelCaseString") . "\n";
} catch (InvalidArgumentException $e) {
    echo 'Error: ' . $e->getMessage();
}
