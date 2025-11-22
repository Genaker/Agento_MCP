<?php

test('cache clear command help works', function () {
    $magentoRoot = getMagentoRoot();
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:cache:clear',
        '--help'
    ], $magentoRoot, 30);
    
    $output = $process->getOutput();
    expect($process->getExitCode())->toBe(0)
        ->and($output)->toMatch('/Usage:|help|Description:/');
})->group('agento');

test('cache clear command with type option help works', function () {
    $magentoRoot = getMagentoRoot();
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:cache:clear',
        '--help'
    ], $magentoRoot, 30);
    
    $output = $process->getOutput();
    expect($process->getExitCode())->toBe(0)
        ->and($output)->toMatch('/type|Usage:/');
})->group('agento');
