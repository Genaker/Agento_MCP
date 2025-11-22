<?php

test('magerun is installed and accessible', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, '--version'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('n98-magerun2');
})->group('agento');

test('magerun commands work correctly', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    // Test sys:info command
    $process = executeCommand(['php', $magerunPath, 'sys:info'], $magentoRoot, 60);
    
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('Magento');
})->group('agento');

test('magerun db command help works', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, 'db:query', '--help'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/Usage:|help/');
})->group('agento');

test('magerun cache command help works', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, 'cache:clean', '--help'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/Usage:|help/');
})->group('agento');

test('magerun admin command help works', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, 'admin:user:list', '--help'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/Usage:|help/');
})->group('agento');

test('magerun sys command help works', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, 'sys:info', '--help'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/Usage:|help/');
})->group('agento');

test('magerun config command help works', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, 'config:store:get', '--help'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/Usage:|help/');
})->group('agento');

test('magerun customer command help works', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, 'customer:list', '--help'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/Usage:|help/');
})->group('agento');

test('magerun indexer command help works', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, 'indexer:reindex', '--help'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/Usage:|help/');
})->group('agento');

test('magerun module command help works', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, 'module:enable', '--help'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/Usage:|help/');
})->group('agento');

test('magerun setup command help works', function () {
    $magentoRoot = getMagentoRoot();
    $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
    
    if (!file_exists($magerunPath)) {
        skip('Magerun is not installed. Run: php bin/magento agento:magerun:install');
    }
    
    $process = executeCommand(['php', $magerunPath, 'setup:upgrade', '--help'], $magentoRoot, 30);
    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/Usage:|help/');
})->group('agento');
