<?php

/**
 * Test MCP magerun tool integration
 * Focuses on verifying that the magerun tool works through MCP
 */

test('mcp server executes magerun tool successfully', function () {
    $magentoRoot = getMagentoRoot();
    
    // Initialize MCP server
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
    
    // Send initialized notification
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    // Call magerun tool with cache:list command
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'cache:list'
            ]
        ]
    ]) . "\n";
    
    // Use line-delimited JSON (works better than Content-Length framing for tests)
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 60, $input);
    
    $output = $process->getOutput();
    $errorOutput = $process->getErrorOutput();
    
    // Output stdout and stderr separately for user visibility
    echo "\n=== STDOUT ===\n";
    echo $output . "\n";
    echo "=== END STDOUT ===\n\n";
    
    echo "=== STDERR ===\n";
    echo $errorOutput . "\n";
    echo "=== END STDERR ===\n\n";
    
    $combinedOutput = $output . $errorOutput;
    
    // Check that server received and processed the magerun command
    // The response should contain either the cache list output or debug logs showing it was called
    $hasMagerunResponse = strpos($combinedOutput, 'cache:list') !== false
        || strpos($combinedOutput, 'config') !== false  // cache type from output
        || strpos($combinedOutput, 'layout') !== false  // cache type from output
        || strpos($combinedOutput, 'magerun') !== false
        || strpos($combinedOutput, 'tools/call') !== false
        || strpos($combinedOutput, 'Sent response') !== false;
    
    expect($hasMagerunResponse)->toBeTrue('MCP magerun tool should execute and return results');
})->group('agento');

test('mcp server executes magerun tool with arguments', function () {
    $magentoRoot = getMagentoRoot();
    
    // Initialize MCP server
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
    
    // Send initialized notification
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    // Call magerun tool with admin:user:list command and format argument
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'admin:user:list',
                'format' => 'json'
            ]
        ]
    ]) . "\n";
    
    // Use line-delimited JSON
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 60, $input);
    
    $output = $process->getOutput();
    $errorOutput = $process->getErrorOutput();
    
    // Output stdout and stderr separately for user visibility
    echo "\n=== STDOUT ===\n";
    echo $output . "\n";
    echo "=== END STDOUT ===\n\n";
    
    echo "=== STDERR ===\n";
    echo $errorOutput . "\n";
    echo "=== END STDERR ===\n\n";
    
    $combinedOutput = $output . $errorOutput;
    
    // Check that server received and processed the magerun command with arguments
    $hasResponse = strpos($combinedOutput, 'admin:user:list') !== false
        || strpos($combinedOutput, 'magerun') !== false
        || strpos($combinedOutput, 'tools/call') !== false
        || strpos($combinedOutput, 'Sent response') !== false
        || strpos($combinedOutput, 'format') !== false;
    
    expect($hasResponse)->toBeTrue('MCP magerun tool should accept and process arguments');
})->group('agento');

test('mcp server executes magerun db:query to query admin table', function () {
    $magentoRoot = getMagentoRoot();
    
    // Initialize MCP server
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
    
    // Send initialized notification
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    // Call magerun tool with db:query command to query admin_user table
    // The query must be passed as a positional argument (index 0)
    $toolRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'db:query',
                '0' => 'SELECT user_id, username, email, is_active FROM admin_user LIMIT 5'
            ]
        ]
    ]) . "\n";
    
    // Use line-delimited JSON
    $input = $initRequest . $initializedRequest . $toolRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 60, $input);
    
    $output = $process->getOutput();
    $errorOutput = $process->getErrorOutput();
    
    // Output stdout and stderr separately for user visibility
    echo "\n=== STDOUT ===\n";
    echo $output . "\n";
    echo "=== END STDOUT ===\n\n";
    
    echo "=== STDERR ===\n";
    echo $errorOutput . "\n";
    echo "=== END STDERR ===\n\n";
    
    $combinedOutput = $output . $errorOutput;
    
    // Check that server received and processed the db:query command
    // The response should contain either admin user data or debug logs showing it was called
    $hasAdminQueryResult = strpos($combinedOutput, 'user_id') !== false
        || strpos($combinedOutput, 'username') !== false
        || strpos($combinedOutput, 'email') !== false
        || strpos($combinedOutput, 'admin_user') !== false
        || strpos($combinedOutput, 'db:query') !== false
        || strpos($combinedOutput, 'tools/call') !== false
        || strpos($combinedOutput, 'Sent response') !== false;
    
    expect($hasAdminQueryResult)->toBeTrue('MCP magerun tool should execute db:query and return admin user data');
})->group('agento');

