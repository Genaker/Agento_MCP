# Installing Agento MCP Server in Cursor

This guide will help you configure the Agento MCP server in Cursor for AI-powered Magento development.

## Prerequisites

1. **Cursor IDE** installed and running
2. **Magento 2.4.x** installation with Agento module enabled
3. **PHP** accessible from command line

## Step 1: Verify Agento Module is Installed

First, verify that the Agento module is installed and working:

```bash
cd /var/www/html/magento
php bin/magento list | grep agento
```

You should see:
- `agento:query` - Execute SQL query
- `agento:cache:clear` - Clear cache
- `agento:mcp` - Start MCP server
- `agento:magerun:install` - Install n98-magerun2

## Step 2: Test MCP Server Manually

Test that the MCP server works:

```bash
cd /var/www/html/magento

# Create test JSON file
cat > /tmp/test_init.json << 'EOF'
{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}
EOF

# Test initialize request with correct Content-Length
php -r "\$json = file_get_contents('/tmp/test_init.json'); \$len = strlen(\$json); echo \"Content-Length: \$len\r\n\r\n\$json\";" | php bin/magento agento:mcp
```

You should see:
- `Agento MCP Server starting...` (on stderr)
- `Content-Length: 143` followed by a JSON response with the server info (on stdout)

**Alternative simple test (line-delimited JSON - also works):**
```bash
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}' | php bin/magento agento:mcp
```

Both methods work - the server supports both Content-Length framing (preferred) and line-delimited JSON (fallback).

## Step 3: Configure Cursor MCP Server

### Option A: Using Cursor Settings UI

1. Open Cursor
2. Go to **Settings** (Cmd/Ctrl + ,)
3. Navigate to **Features** → **Model Context Protocol**
4. Click **Add Server** or **Edit Config**
5. Add the following configuration:

**Server Name:** `agento`

**Command:** `php`

**Arguments:**
```
/var/www/html/magento/bin/magento
agento:mcp
```

**Working Directory:** `/var/www/html/magento`

### Option B: Using Cursor Settings JSON

1. Open Cursor Settings (Cmd/Ctrl + ,)
2. Click the **{}** icon to open JSON settings
3. Add or update the `mcpServers` section:

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

**Important:** Replace `/var/www/html/magento` with your actual Magento installation path.

### Option C: Using Cursor Config File

If you prefer to edit the config file directly:

1. Locate Cursor's config file:
   - **macOS:** `~/Library/Application Support/Cursor/User/globalStorage/rooveterinaryinc.roo-cline/settings/cline_mcp_settings.json`
   - **Windows:** `%APPDATA%\Cursor\User\globalStorage\rooveterinaryinc.roo-cline\settings\cline_mcp_settings.json`
   - **Linux:** `~/.config/Cursor/User/globalStorage/rooveterinaryinc.roo-cline/settings/cline_mcp_settings.json`

2. Add the Agento server configuration:

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

## Step 4: Restart Cursor

After adding the configuration:

1. **Save** the settings
2. **Restart Cursor** completely (quit and reopen)
3. The MCP server should automatically connect on startup

## Step 5: Verify Connection

1. Open Cursor's **MCP Server Status** (usually in the status bar or settings)
2. You should see `agento` listed as connected
3. Check for any error messages in Cursor's logs

### Check MCP Server Logs

If the server isn't connecting:

1. Open Cursor's **Developer Tools** (Help → Toggle Developer Tools)
2. Check the **Console** tab for MCP-related errors
3. Look for messages about the `agento` server

## Step 6: Test MCP Tools

Once connected, you can test the tools by asking Cursor's AI:

1. **Test SQL Query:**
   ```
   Use the execute_sql tool to query the admin_user table
   ```

2. **Test Cache Clear:**
   ```
   Use the clear_cache tool to clear Magento cache
   ```

3. **Test Magerun:**
   ```
   Use the magerun tool to get system info
   ```

## Troubleshooting

### Issue: MCP Server Not Connecting

**Solution 1: Check PHP Path**
```bash
which php
```
Make sure PHP is in your system PATH. If not, use the full path in the Cursor config:
```json
{
  "mcpServers": {
    "agento": {
      "command": "/usr/bin/php",
      "args": [
        "/var/www/html/magento/bin/magento",
        "agento:mcp"
      ],
      "cwd": "/var/www/html/magento"
    }
  }
}
```

**Solution 2: Check Magento Path**
Verify the path to `bin/magento` is correct:
```bash
ls -la /var/www/html/magento/bin/magento
```

**Solution 3: Test Command Manually**
```bash
cd /var/www/html/magento

# Simple test with line-delimited JSON
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}' | php bin/magento agento:mcp
```

If this doesn't work, the module may not be enabled. Run:
```bash
php bin/magento module:enable Agento_Core
php bin/magento setup:upgrade
php bin/magento cache:clean
```

### Issue: Permission Errors

If you see permission errors:
```bash
chmod +x /var/www/html/magento/bin/magento
```

### Issue: Database Connection Errors

Ensure your `app/etc/env.php` has correct database credentials. The MCP server needs database access for SQL queries.

### Issue: MCP Server Crashes

Check Cursor's developer console for error messages. Common issues:
- PHP memory limit too low
- Magento bootstrap errors
- Missing dependencies

## Available Tools

Once connected, these tools are available to Cursor's AI:

1. **execute_sql** - Execute SQL queries against Magento database
2. **clear_cache** - Clear Magento cache (all or specific types)
3. **magerun** - Execute any n98-magerun2 command
4. **magerun_db** - Database operations (query, dump, import, etc.)
5. **magerun_cache** - Cache operations
6. **magerun_admin** - Admin user management
7. **magerun_sys** - System information and management
8. **magerun_config** - Configuration management
9. **magerun_customer** - Customer management
10. **magerun_indexer** - Indexer operations
11. **magerun_module** - Module management
12. **magerun_setup** - Setup operations

## Example Usage

Once configured, you can ask Cursor's AI to:

- "Query the database to find all active admin users"
- "Clear the config cache"
- "Get system information using magerun"
- "List all Magento stores"
- "Check indexer status"
- "Get configuration value for base URL"

The AI will automatically use the appropriate MCP tools to execute these requests.

## Advanced Configuration

### Using Docker

If Magento is running in Docker:

```json
{
  "mcpServers": {
    "agento": {
      "command": "docker",
      "args": [
        "exec",
        "-i",
        "magento-container",
        "php",
        "/var/www/html/magento/bin/magento",
        "agento:mcp"
      ],
      "cwd": "/var/www/html/magento"
    }
  }
}
```

### Using Different PHP Version

If you need a specific PHP version:

```json
{
  "mcpServers": {
    "agento": {
      "command": "/usr/bin/php8.2",
      "args": [
        "/var/www/html/magento/bin/magento",
        "agento:mcp"
      ],
      "cwd": "/var/www/html/magento"
    }
  }
}
```

## Support

For issues:
1. Check Cursor's MCP server logs
2. Test the MCP server manually (see Step 2)
3. Verify Magento module is enabled and working
4. Check the main README.md for more details

