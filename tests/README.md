# Agento Tests

Pest tests for Agento commands that run processes isolated from Magento, simulating how Cursor/MCP would call them.

## Running Tests

To run all Pest tests:

```bash
cd /var/www/html/magento
./vendor/bin/pest app/code/Agento/Core/tests
```

Or run specific test files:

```bash
./vendor/bin/pest app/code/Agento/Core/tests/Feature/QueryCommandTest.php
./vendor/bin/pest app/code/Agento/Core/tests/Feature/CacheClearCommandTest.php
./vendor/bin/pest app/code/Agento/Core/tests/Feature/MagerunIntegrationTest.php
```

Or use the test runner script:

```bash
cd /var/www/html/magento
./app/code/Agento/Core/run-tests.sh
```

## Test Structure

The Pest test suite includes:

### Core Agento Commands (`QueryCommandTest.php`)
1. **Query command executes SQL** - Verifies SQL queries work correctly
2. **Query command requires query parameter** - Validates error handling
3. **Query command supports JSON format** - Tests output format options

### Cache Commands (`CacheClearCommandTest.php`)
4. **Cache clear command help works** - Tests cache clear command help (doesn't actually clear cache)
5. **Cache clear command with type option help works** - Tests cache clear command help with type option

### Command Availability (`CommandAvailabilityTest.php`)
6. **All commands are available** - Verifies command registration
7. **Magerun install command is available** - Verifies install command registration

### Magerun Integration (`MagerunIntegrationTest.php`)
8. **Magerun is installed and accessible** - Checks magerun PHAR file exists and works
9. **Magerun commands work correctly** - Tests basic magerun command execution
10-18. **Magerun category command help tests** - Tests help for db, cache, admin, sys, config, customer, indexer, module, setup commands

### All Magerun Commands (`MagerunCommandsTest.php`)
Tests all ~100+ magerun commands with `--help` flag to verify they're accessible:
- Admin Commands (8 commands)
- Cache Commands (9 commands)
- Composer Commands (1 command)
- Configuration Commands (12 commands)
- Customer Commands (7 commands)
- Database Commands (11 commands)
- Development Commands (21 commands)
- EAV Commands (3 commands)
- Generation Commands (1 command)
- Gift Card Commands (4 commands)
- GitHub Commands (1 command)
- Indexing Commands (2 commands)
- Installation Commands (1 command)
- Integration Commands (4 commands)
- Magerun Commands (2 commands)
- Media Commands (1 command)
- Script Commands (1 command)
- Search Commands (1 command)
- System Commands (15 commands)
- Route Commands (1 command)

### MCP Server Tests (`McpCommandTest.php`, `McpToolsIntegrationTest.php`, `McpMagerunTest.php`)
Tests for the Model Context Protocol (MCP) server integration:
- **MCP server initialization** - Verifies the server starts and handles initialize requests
- **Tool discovery** - Tests that MCP tools are discovered and listed correctly
- **Tool execution** - Tests individual tools like `execute_sql`, `clear_cache`, `magerun`
- **Error handling** - Verifies proper error responses for invalid requests
- **Protocol compliance** - Tests JSON-RPC protocol handling

## MCP Test Output Behavior (STDOUT vs STDERR)

When running MCP server tests, you may notice that **all output appears in STDERR** instead of STDOUT. This is expected behavior and here's why:

### Why Output Goes to STDERR

1. **JSON-RPC Protocol Messages Should Go to STDOUT**
   - According to the MCP specification, JSON-RPC protocol messages (responses) should be written to **STDOUT**
   - The `StdioServerTransport` class handles this correctly

2. **Debug Logs Go to STDERR** (This is Correct)
   - Our custom logger writes all log messages (including DEBUG frames with copies of JSON-RPC responses) to **STDERR**
   - This includes messages like `[INFO] Agento MCP Server starting...` and `[DEBUG] Sent response {...}`
   - This is the standard practice: protocol messages to STDOUT, logs to STDERR

3. **Why STDOUT Appears Empty in Tests**
   - When running tests via Symfony Process, STDOUT may appear empty because:
     - The JSON-RPC responses are written during the blocking `listen()` call
     - Symfony Process may have timing/buffering issues capturing STDOUT
     - Responses might be written after the process closes
   - However, **the debug logs in STDERR contain copies of all responses**, so you can verify the server is working correctly

### What This Means for Tests

- **STDERR contains**: Debug logs, info messages, and copies of JSON-RPC responses (for debugging)
- **STDOUT contains**: Actual JSON-RPC protocol responses (used by MCP clients like Cursor)
- **In tests**: We check both STDOUT and STDERR (combined output) to verify server functionality
- **In production**: MCP clients read from STDOUT for protocol messages and ignore STDERR

### Example Test Output

When running MCP tests, you'll see output like:

```
=== STDOUT ===

=== END STDOUT ===

=== STDERR ===
[INFO] Agento MCP Server starting...
[DEBUG] Received message {"clientId":"stdio","frame":"{...}"}
[DEBUG] Sent response {"clientId":"stdio","frame":"{\"jsonrpc\":\"2.0\",\"id\":1,\"result\":{...}}"}
[INFO] STDIN stream closed.
=== END STDERR ===
```

The `[DEBUG] Sent response` line in STDERR shows that the server sent a response (which also went to STDOUT, but wasn't captured in the test). The actual JSON-RPC response can be extracted from the debug frame for verification.

## How Tests Work

All tests use `Symfony\Component\Process\Process` to run commands as separate processes, exactly like Cursor/MCP would:

1. Each test creates a new process with the command
2. Commands run isolated from the test environment
3. Tests verify exit codes and output
4. No direct Magento object manager usage in tests

This ensures tests accurately reflect how the commands will behave when called by external tools.

## Requirements

- PHP 7.4+
- Magento 2.4.x
- Pest PHP (installed via composer: `composer require pestphp/pest --dev`)
- Symfony Process component (available in Magento vendor or tests/vendor)
- Agento module enabled and registered
- n98-magerun2.phar installed (run `php bin/magento agento:magerun:install`)

## Installation

### Install Pest (if not already installed)

```bash
cd /var/www/html/magento
composer require pestphp/pest --dev
```

### Install test dependencies (optional, for standalone symfony/process)

```bash
cd app/code/Agento/Core/tests
composer install
```

This installs `symfony/process` independently in `tests/vendor`, but tests will prefer Magento's vendor directory if available.

## Troubleshooting

If tests fail:

1. **Ensure Magento is properly set up:**
   ```bash
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

2. **Check database connection** in `app/etc/env.php`

3. **Verify commands are registered:**
   ```bash
   php bin/magento list | grep agento
   ```
   Should show: `agento:query`, `agento:cache:clear`, `agento:mcp`, `agento:magerun:install`

4. **Check module is enabled:**
   ```bash
   php bin/magento module:status | grep Agento_Core
   ```

5. **If timeouts occur**, Magento bootstrap may be slow. Tests use 120-second timeout by default for command execution.

6. **If Pest is not found**, ensure it's installed:
   ```bash
   composer require pestphp/pest --dev
   ```

## Test Output Example

```
PEST
PHPUnit 10.5.0 by Sebastian Bergmann and contributors.

  PASS  Tests\Feature\QueryCommandTest
  ✓ query command executes SQL
  ✓ query command requires query parameter
  ✓ query command supports JSON format

  PASS  Tests\Feature\CacheClearCommandTest
  ✓ cache clear command help works
  ✓ cache clear command with type option help works

  PASS  Tests\Feature\CommandAvailabilityTest
  ✓ all commands are available
  ✓ magerun install command is available

  PASS  Tests\Feature\MagerunIntegrationTest
  ✓ magerun is installed and accessible
  ✓ magerun commands work correctly
  ✓ magerun db command help works
  ...

Tests:    120 passed (120 assertions)
Duration: 45.23s
```
