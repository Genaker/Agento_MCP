<?php

use Symfony\Component\Process\Process;

test('query command executes SQL', function () {
    $magentoRoot = getMagentoRoot();
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:query',
        '--query',
        'SELECT VERSION() as mysql_version'
    ], $magentoRoot, 120);
    
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('mysql_version')
        ->and($process->getOutput())->toContain('Execution time');
})->group('agento');

test('query command requires query parameter', function () {
    $magentoRoot = getMagentoRoot();
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:query'
    ], $magentoRoot, 120);
    
    $output = $process->getOutput();
    expect($process->getExitCode())->not->toBe(0)
        ->and($output)->toMatch('/Query is required/');
})->group('agento');

test('query command supports JSON format', function () {
    $magentoRoot = getMagentoRoot();
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:query',
        '--query',
        'SELECT 1 as test_value',
        '--format',
        'json'
    ], $magentoRoot, 120);
    
    expect($process->getExitCode())->toBe(0);
    
    $output = $process->getOutput();
    // Extract JSON part (before the "Rows returned" line)
    $lines = explode("\n", $output);
    $jsonLines = [];
    foreach ($lines as $line) {
        if (strpos($line, 'Rows returned') !== false) {
            break;
        }
        $jsonLines[] = $line;
    }
    $jsonString = implode("\n", $jsonLines);
    
    $json = json_decode($jsonString, true);
    expect($json)->not->toBeNull()
        ->and($json)->toBeArray()
        ->and($json)->toHaveCount(1)
        ->and($json[0])->toHaveKey('test_value');
})->group('agento');
