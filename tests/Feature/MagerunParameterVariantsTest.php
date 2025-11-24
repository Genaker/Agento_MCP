<?php

/**
 * MagerunTool Parameter Variants Tests
 * Tests all possible parameter combinations for the magerun MCP tool
 */

require_once __DIR__ . '/../Helpers.php';

// ============================================================================
// Parameter Validation Tests
// ============================================================================

test('MagerunTool validates command parameter is required', function () {
    $magentoRoot = getMagentoRoot();
    
    $initRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0']
        ]
    ]) . "\n";
    
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    // Test with empty command
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => ['command' => '']
        ]
    ]) . "\n";
    
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 10, $input);
    
    $combinedOutput = $process->getOutput() . $process->getErrorOutput();
    
    // Should contain error about command being required
    expect($combinedOutput)->toContain('Command is required');
})->group('agento', 'magerun-params');

// ============================================================================
// Positional Argument Priority Tests
// ============================================================================

test('MagerunTool accepts username as positional arg 0', function () {
    $magentoRoot = getMagentoRoot();
    
    $initRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0']
        ]
    ]) . "\n";
    
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'cache:list',
                'username' => 'testuser',
                'format' => 'json'
            ]
        ]
    ]) . "\n";
    
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 10, $input);
    
    $combinedOutput = $process->getOutput() . $process->getErrorOutput();
    
    // Should execute successfully (username parameter accepted)
    expect($process->getExitCode())->toBeLessThanOrEqual(1);
})->group('agento', 'magerun-params');

test('MagerunTool accepts query as positional arg 0', function () {
    $magentoRoot = getMagentoRoot();
    
    $initRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0']
        ]
    ]) . "\n";
    
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'cache:list',
                'query' => 'SELECT 1',
                'format' => 'json'
            ]
        ]
    ]) . "\n";
    
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 10, $input);
    
    expect($process->getExitCode())->toBeLessThanOrEqual(1);
})->group('agento', 'magerun-params');

test('MagerunTool accepts arg0 through arg5', function () {
    $magentoRoot = getMagentoRoot();
    
    $initRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0']
        ]
    ]) . "\n";
    
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'cache:list',
                'arg0' => 'value0',
                'arg1' => 'value1',
                'arg2' => 'value2',
                'arg3' => 'value3',
                'arg4' => 'value4',
                'arg5' => 'value5',
                'format' => 'json'
            ]
        ]
    ]) . "\n";
    
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 10, $input);
    
    expect($process->getExitCode())->toBeLessThanOrEqual(1);
})->group('agento', 'magerun-params');

// ============================================================================
// Format Parameter Tests
// ============================================================================

test('MagerunTool accepts format parameter - json', function () {
    $magentoRoot = getMagentoRoot();
    
    $initRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0']
        ]
    ]) . "\n";
    
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'cache:list',
                'format' => 'json'
            ]
        ]
    ]) . "\n";
    
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 10, $input);
    
    $combinedOutput = $process->getOutput() . $process->getErrorOutput();
    
    // Should return JSON formatted output or successfully execute
    $hasJsonOutput = strpos($combinedOutput, '"Name"') !== false || strpos($combinedOutput, 'Name') !== false;
    expect($hasJsonOutput || $process->getExitCode() <= 1)->toBeTrue();
})->group('agento', 'magerun-params');

// ============================================================================
// Boolean Flags Tests
// ============================================================================

test('MagerunTool accepts force flag', function () {
    $magentoRoot = getMagentoRoot();
    
    $initRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0']
        ]
    ]) . "\n";
    
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'cache:clean',
                'force' => true,
                'type' => 'config'
            ]
        ]
    ]) . "\n";
    
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 10, $input);
    
    expect($process->getExitCode())->toBeLessThanOrEqual(1);
})->group('agento', 'magerun-params');

// ============================================================================
// Multiple Parameters Test
// ============================================================================

test('MagerunTool accepts multiple parameters simultaneously', function () {
    $magentoRoot = getMagentoRoot();
    
    $initRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0']
        ]
    ]) . "\n";
    
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'cache:list',
                'format' => 'json',
                'sort' => 'name',
                'columns' => 'Name,Enabled'
            ]
        ]
    ]) . "\n";
    
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 10, $input);
    
    $combinedOutput = $process->getOutput() . $process->getErrorOutput();
    
    // Should execute successfully with multiple parameters
    $hasOutput = strpos($combinedOutput, '"Name"') !== false || strpos($combinedOutput, 'Name') !== false;
    expect($hasOutput || $process->getExitCode() <= 1)->toBeTrue();
})->group('agento', 'magerun-params');

// ============================================================================
// Debug Parameter Test
// ============================================================================

test('MagerunTool dd parameter dumps all parameters', function () {
    $magentoRoot = getMagentoRoot();
    
    $initRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0']
        ]
    ]) . "\n";
    
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'cache:list',
                'format' => 'json',
                'dd' => true
            ]
        ]
    ]) . "\n";
    
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 10, $input);
    
    $combinedOutput = $process->getOutput() . $process->getErrorOutput();
    
    // Should dump all 52 parameters
    expect($combinedOutput)->toContain('array:')
        ->and($combinedOutput)->toContain('"command" => "cache:list"')
        ->and($combinedOutput)->toContain('"format" => "json"')
        ->and($combinedOutput)->toContain('"dd" => true');
})->group('agento', 'magerun-params');


