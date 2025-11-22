# Agento - AI-Powered Development Tools for Magento

Agento is an AI-powered development tool for Magento, similar to Laravel Boost. It provides SQL query execution, cache management, and MCP server integration for AI-assisted development.

## Features

- **SQL Query Execution**: Run SQL queries directly from the command line
- **Cache Management**: Clear Magento cache with ease
- **MCP Server**: Model Context Protocol server for AI integration with Cursor and other AI tools
- **n98-magerun2 Integration**: Access to 100+ magerun commands for comprehensive Magento management

## Installation

### Step 1: Enable the Module

The module should already be enabled. If not, run:

```bash
php bin/magento module:enable Agento_Core
php bin/magento setup:upgrade
php bin/magento cache:clean
```

### Step 2: Install n98-magerun2 (Optional but Recommended)

Agento integrates with n98-magerun2 for additional Magento CLI tools. To install:

```bash
cd /var/www/html/magento
curl -sS -o bin/n98-magerun2.phar https://files.magerun.net/n98-magerun2.phar
chmod +x bin/n98-magerun2.phar
```

Verify installation:
```bash
php bin/n98-magerun2.phar --version
```

For more information, visit: https://github.com/netz98/n98-magerun2

### Step 3: Verify Installation

Check that the commands are available:

```bash
php bin/magento list | grep agento
```

You should see:
- `agento:query` - Execute SQL query
- `agento:cache:clear` - Clear cache
- `agento:mcp` - Start MCP server
- `agento:magerun:install` - Install n98-magerun2
- `agento:cursor:install` - Install Cursor MCP server configuration

You should see:
- `agento:cache:clear` - Clear Magento cache
- `agento:query` - Execute SQL query
- `agento:quick` - Clear cache and run query
- `agento:mcp` - Start MCP server

## Usage

### Execute SQL Query

```bash
php bin/magento agento:query --query "SELECT * FROM admin_user LIMIT 5"
```

With different output formats:

```bash
# Table format (default)
php bin/magento agento:query --query "SELECT * FROM admin_user LIMIT 5" --format table

# JSON format
php bin/magento agento:query --query "SELECT * FROM admin_user LIMIT 5" --format json

# CSV format
php bin/magento agento:query --query "SELECT * FROM admin_user LIMIT 5" --format csv
```

Using a different database connection:

```bash
php bin/magento agento:query --query "SELECT * FROM admin_user" --connection indexer
```

### Clear Cache

```bash
# Clear all cache
php bin/magento agento:cache:clear

# Clear specific cache type
php bin/magento agento:cache:clear --type config
```


## Cursor Integration (MCP Server)

### Quick Install (Recommended)

Run the automated installer:

```bash
php bin/magento agento:cursor:install
```

This command will:
- Detect your Magento installation path
- Generate the MCP server configuration
- Attempt to automatically add it to Cursor settings (if Cursor config is found)
- Save the configuration to `cursor-mcp-config.json` for manual installation

### Manual Installation

If automatic installation doesn't work, follow these steps:

1. Open Cursor settings
2. Navigate to **Features** → **Model Context Protocol**
3. Add a new MCP server with the following configuration:

**For Cursor Settings (JSON):**

```json
{
  "mcpServers": {
    "agento": {
      "command": "php",
      "args": [
        "/var/www/html/magento/bin/magento",
        "agento:mcp"
      ],
      "cwd": "/var/www/html/magento"
    }
  }
}
```

**Note**: Adjust the paths (`/var/www/html/magento`) to match your Magento installation directory.

### Step 2: Verify MCP Server Connection

1. Restart Cursor after adding the MCP configuration
2. The MCP server should automatically connect
3. You can verify by checking Cursor's MCP server status

### Step 3: Test MCP Server

You can test the MCP server manually:

```bash
# Start the MCP server (for testing)
php bin/magento agento:mcp
```

The server will listen on stdin/stdout for JSON-RPC 2.0 protocol messages.

## Available MCP Tools

Once connected, the following tools are available to AI assistants:

1. **execute_sql** - Execute SQL query against Magento database
   - Parameters:
     - `query` (required): SQL query to execute
     - `connection` (optional): Database connection name (default: "default")

2. **clear_cache** - Clear Magento cache
   - Parameters:
     - `type` (optional): Cache type to clear (clears all if not specified)

3. **magerun** - Execute any n98-magerun2 command (Swiss army knife for Magento)
   - Parameters:
     - `command` (required): Full magerun command (e.g., "db:query", "cache:clean", "sys:info", "admin:user:list")
     - `arguments` (optional): Command arguments as key-value pairs
     - `options` (optional): Command options as key-value pairs
   - Examples:
     - `{"command": "sys:info"}` - Get system information
     - `{"command": "db:query", "arguments": {"query": "SELECT * FROM admin_user LIMIT 5"}}` - Run database query
     - `{"command": "cache:clean", "options": {"type": "config"}}` - Clean specific cache type
     - `{"command": "admin:user:list"}` - List admin users

5. **magerun_db** - Database operations (query, dump, import, info, console, create, drop, status, variables)
   - Parameters:
     - `command` (required): One of: query, dump, import, info, console, create, drop, status, variables
     - `arguments` (optional): Command-specific arguments

6. **magerun_cache** - Cache operations (clean, flush, enable, disable, list, status, view, report)
   - Parameters:
     - `command` (required): One of: clean, flush, enable, disable, list, status, view, report
     - `arguments` (optional): Command-specific arguments (e.g., `{"type": "config"}` for clean)

7. **magerun_admin** - Admin user management (list, create, change-password, delete, activate, deactivate, unlock)
   - Parameters:
     - `command` (required): One of: user:list, user:create, user:change-password, user:delete, user:activate, user:deactivate, user:unlock, token:create
     - `arguments` (optional): Command-specific arguments

6. **magerun_sys** - System information (info, check, store:list, website:list, url:list, cron:list, cron:run)
   - Parameters:
     - `command` (required): One of: info, check, store:list, website:list, url:list, cron:list, cron:run, maintenance
     - `arguments` (optional): Command-specific arguments

7. **magerun_config** - Configuration management (store:get, store:set, env:set, search, show)
   - Parameters:
     - `command` (required): One of: store:get, store:set, env:set, search, show, show:urls, show:default-url, show:store-url
     - `arguments` (optional): Command-specific arguments (e.g., `{"path": "web/unsecure/base_url"}` for store:get)

8. **magerun_customer** - Customer management (list, create, info, delete, change-password, add-address)
    - Parameters:
      - `command` (required): One of: list, create, info, delete, change-password, add-address, token:create
      - `arguments` (optional): Command-specific arguments

9. **magerun_indexer** - Indexer operations (reindex, reset, status, set-mode, show-mode, info)
    - Parameters:
      - `command` (required): One of: reindex, reset, status, set-mode, show-mode, info, set-status
      - `arguments` (optional): Command-specific arguments

10. **magerun_module** - Module management (enable, disable, status, uninstall)
    - Parameters:
      - `command` (required): One of: enable, disable, status, uninstall
      - `arguments` (optional): Command-specific arguments (e.g., `{"module": "Magento_Catalog"}`)

11. **magerun_setup** - Setup operations (upgrade, install, db:status, di:compile, static-content:deploy)
    - Parameters:
      - `command` (required): One of: upgrade, install, db:status, di:compile, static-content:deploy, backup, rollback, uninstall
      - `arguments` (optional): Command-specific arguments

**Note**: For a complete list of all available magerun commands, visit: https://github.com/netz98/n98-magerun2/tree/develop/docs/docs/command-docs

## Testing the Installation

### Test 1: Verify Commands are Available

```bash
php bin/magento list | grep agento
```

Expected output:
```
agento:cache:clear          Clear Magento cache
agento:mcp                  Start Agento MCP server for AI integration
agento:query                Execute SQL query against Magento database
agento:quick                Clear cache and execute SQL query in one command
```

### Test 2: Test SQL Query Command

```bash
php bin/magento agento:query --query "SELECT VERSION() as mysql_version"
```

Expected output:
```
+-------------------------+
| mysql_version           |
+-------------------------+
| 8.0.42-0ubuntu0.24.04.2 |
+-------------------------+
Rows returned: 1 | Execution time: X.XXms
```

### Test 3: Test Cache Clear Command

```bash
php bin/magento agento:cache:clear
```

Expected: Should show cache types being cleared.

### Test 4: Test Quick Command

```bash
php bin/magento agento:quick --query "SELECT COUNT(*) as total FROM admin_user"
```

Expected: Should show:
1. "Clearing cache..." message
2. "✓ All cache cleared" or "✓ Cleared cache type: X"
3. "Executing query..." message
4. Query results in table format
5. Row count and execution time

### Test 5: Test MCP Server (Manual)

Create a test file `test_mcp.json`:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/list"
}
```

Then test:

```bash
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | php bin/magento agento:mcp
```

Expected: Should return a JSON response with available tools.

## Troubleshooting

### Command Not Found

If commands are not available:

```bash
php bin/magento module:enable Agento_Core
php bin/magento setup:upgrade
php bin/magento cache:clean
```

### MCP Server Not Connecting

1. Verify the path to `bin/magento` is correct in Cursor settings
2. Ensure PHP is in your system PATH
3. Check Cursor's MCP server logs for errors
4. Test the MCP server manually using the test above

### Database Connection Errors

Ensure your `app/etc/env.php` has correct database credentials:

```php
'db' => [
    'connection' => [
        'default' => [
            'host' => '127.0.0.1',
            'dbname' => 'magento',
            'username' => 'magento',
            'password' => 'magento'
        ]
    ]
]
```

## Examples

### Example 1: Get All Admin Users

```bash
php bin/magento agento:query --query "SELECT user_id, username, email, is_active FROM admin_user"
```

### Example 2: Get Product Count by Status

```bash
php bin/magento agento:query --query "SELECT status, COUNT(*) as count FROM catalog_product_entity GROUP BY status"
```


### Example 4: Export Data as JSON

```bash
php bin/magento agento:query --query "SELECT * FROM admin_user LIMIT 5" --format json > users.json
```

### Example 5: Using n98-magerun2 Commands

You can also use n98-magerun2 commands directly:

```bash
# Get system information
php bin/n98-magerun2.phar sys:info

# Run database query
php bin/n98-magerun2.phar db:query "SELECT * FROM admin_user LIMIT 5"

# List admin users
php bin/n98-magerun2.phar admin:user:list

# Clean cache
php bin/n98-magerun2.phar cache:clean

# Get store list
php bin/n98-magerun2.phar sys:store:list
```

**Note**: These commands are also available through the MCP server for AI assistants in Cursor.

## Requirements

- Magento 2.4.x
- PHP 7.4 or higher
- MySQL/MariaDB database

## License

This module is provided as-is for development purposes.

## Testing

Standalone tests are available that run commands as isolated processes (simulating how Cursor/MCP would call them):

```bash
# Run all tests
php app/code/Agento/Core/tests/run-standalone-tests.php
```

See `tests/README.md` for more details.

## Support

For issues or questions, please check:
- Magento documentation
- Cursor MCP documentation
- Module code in `app/code/Agento/Core/`
- Test suite in `app/code/Agento/Core/tests/`

