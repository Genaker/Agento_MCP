<?php

test('all commands are available', function () {
    $magentoRoot = getMagentoRoot();
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'list',
        'agento'
    ], $magentoRoot, 120);
    
    $output = $process->getOutput();
    expect($process->getExitCode())->toBe(0)
        ->and($output)->toContain('agento:query')
        ->and($output)->toContain('agento:cache:clear')
        ->and($output)->toContain('agento:mcp');
})->group('agento');

test('magerun install command is available', function () {
    $magentoRoot = getMagentoRoot();
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'list',
        'agento'
    ], $magentoRoot, 120);
    
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('agento:magerun:install');
})->group('agento');






