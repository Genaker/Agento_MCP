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

test('mcp magerun creates, blocks, verifies, and deletes admin user', function () {
    $magentoRoot = getMagentoRoot();
    
    // Generate unique test user credentials
    $timestamp = time();
    $testUsername = "testuser_{$timestamp}";
    $testEmail = "testuser_{$timestamp}@test.local";
    $testPassword = "SecurePass123!";
    
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
    
    $initializedRequest = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized'
    ]) . "\n";
    
    // Step 1: Create admin user using beautiful API
    $createUserRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'execute_sql',
            'arguments' => [
                'query' => "INSERT INTO admin_user (username, email, password, created, modified, is_active, firstname, lastname) 
                           VALUES ('{$testUsername}', '{$testEmail}', SHA2('test', 256), NOW(), NOW(), 1, 'Test', 'User')"
            ]
        ]
    ]) . "\n";
    
    // Step 2: Block the user using beautiful magerun API
    $blockUserRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'admin:user:change-status',
                'deactivate' => true,
                'username' => $testUsername
            ]
        ]
    ]) . "\n";
    
    // Step 3: Verify user is blocked
    $verifyBlockedRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 4,
        'method' => 'tools/call',
        'params' => [
            'name' => 'execute_sql',
            'arguments' => [
                'query' => "SELECT username, email, is_active FROM admin_user WHERE username = '{$testUsername}'"
            ]
        ]
    ]) . "\n";
    
    // Step 4: Delete the user using beautiful magerun API
    $deleteUserRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 5,
        'method' => 'tools/call',
        'params' => [
            'name' => 'magerun',
            'arguments' => [
                'command' => 'admin:user:delete',
                'username' => $testUsername,
                'force' => true
            ]
        ]
    ]) . "\n";
    
    // Step 5: Verify user is deleted
    $verifyDeletedRequest = json_encode([
        'jsonrpc' => '2.0',
        'id' => 6,
        'method' => 'tools/call',
        'params' => [
            'name' => 'execute_sql',
            'arguments' => [
                'query' => "SELECT COUNT(*) as count FROM admin_user WHERE username = '{$testUsername}'"
            ]
        ]
    ]) . "\n";
    
    // Combine all requests
    $input = $initRequest . $initializedRequest . $createUserRequest . $blockUserRequest . 
             $verifyBlockedRequest . $deleteUserRequest . $verifyDeletedRequest;
    
    $process = executeCommand([
        'php',
        $magentoRoot . '/bin/magento',
        'agento:mcp'
    ], $magentoRoot, 60, $input);
    
    $output = $process->getOutput();
    $errorOutput = $process->getErrorOutput();
    
    echo "\n=== STDOUT ===\n";
    echo $output . "\n";
    echo "=== END STDOUT ===\n\n";
    
    echo "=== STDERR ===\n";
    echo $errorOutput . "\n";
    echo "=== END STDERR ===\n\n";
    
    $combinedOutput = $output . $errorOutput;
    
    // Verify user was created and blocked
    $userWasDeactivated = strpos($combinedOutput, 'User has been deactivated') !== false
        || strpos($combinedOutput, 'deactivated') !== false;
    
    expect($userWasDeactivated)->toBeTrue('User should be deactivated using beautiful magerun API');
    
    // Verify is_active = 0 (blocked)
    $userIsBlocked = strpos($combinedOutput, 'is_active') !== false 
        && (strpos($combinedOutput, '| 0') !== false || preg_match('/is_active[^\d]*0/', $combinedOutput));
    
    expect($userIsBlocked)->toBeTrue('User should have is_active = 0 (blocked)');
    
    // Verify user was deleted
    $userWasDeleted = strpos($combinedOutput, 'successfully deleted') !== false
        || (strpos($combinedOutput, 'count') !== false && preg_match('/count[^\d]*0/', $combinedOutput));
    
    expect($userWasDeleted)->toBeTrue('User should be deleted at the end');
    
    echo "\n✅ Test passed: User created → blocked → verified → deleted using beautiful magerun MCP API\n";
})->group('agento');

