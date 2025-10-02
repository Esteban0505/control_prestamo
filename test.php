<?php
define('BASEPATH', 'test');
// Test script for currency validation
require_once 'application/helpers/currency_helper.php';

echo "Testing validate_colombian_currency:\n";
$tests = ['1000000', '1.000.000', '1.000.000,50', 'abc123', '', '-1000'];
foreach ($tests as $test) {
    echo "$test: " . (validate_colombian_currency($test) ? 'true' : 'false') . "\n";
}

echo "\nTesting format_to_db:\n";
foreach ($tests as $test) {
    $result = format_to_db($test);
    echo "$test: " . ($result === false ? 'false' : $result) . "\n";
}

// Simulate parse_currency_input logic
function test_parse_currency_input($input, $allow_decimals = true) {
    if (empty($input)) {
        return $allow_decimals ? 0.00 : 0;
    }

    $input = trim($input);
    $input = str_replace('$', '', $input);
    $input = str_replace(' ', '', $input);

    if (is_numeric($input)) {
        return $allow_decimals ? (float) $input : (int) $input;
    }

    if (!$allow_decimals) {
        if (!preg_match('/^[\d]{1,3}(\.[\d]{3})*$/', $input)) {
            return false;
        }
        $input = str_replace('.', '', $input);
        return (int) $input;
    } else {
        if (!preg_match('/^[\d]{1,3}(\.[\d]{3})*(,[\d]{1,2})?$/', $input)) {
            return false;
        }
        $input = str_replace('.', '', $input);
        $input = str_replace(',', '.', $input);
        return (float) $input;
    }
}

echo "\nTesting parse_currency_input (allow_decimals=false):\n";
foreach ($tests as $test) {
    $result = test_parse_currency_input($test, false);
    echo "$test: " . ($result === false ? 'false' : $result) . "\n";
}

echo "\nTesting parse_currency_input (allow_decimals=true):\n";
foreach ($tests as $test) {
    $result = test_parse_currency_input($test, true);
    echo "$test: " . ($result === false ? 'false' : $result) . "\n";
}