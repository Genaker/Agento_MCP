# Agento MCP Server Troubleshooting

## Issue: Cursor Closes Connection Immediately

If you see errors like:
- "Client closed for command"
- "No server info found"
- MCP server starts but connection closes immediately

## Solutions

### 1. Verify MCP Server Works Manually

Test the server directly:

```bash
cd /var/www/html/magento
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"cursor","version":"1.0"}}}' | php bin/magento agento:mcp
```

You should see:
- `Agento MCP Server starting...` (on stderr)
- A JSON response with server info (on stdout)

### 2. Check Cursor Configuration

Verify `.cursor/mcp.json` exists and has correct format:

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

**Important:** Ensure paths are absolute and correct for your system.

### 3. Restart Cursor

After changing MCP configuration:
1. Completely quit Cursor (not just close window)
2. Restart Cursor
3. Check MCP server status in Cursor settings

### 4. Check PHP Path

Ensure `php` command is available in PATH:

```bash
which php
php --version
```

If PHP is not in PATH, update `.cursor/mcp.json` to use full path:

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

### 5. Verify Magento Installation

Ensure Magento is properly installed:

```bash
cd /var/www/html/magento
php bin/magento list | grep agento
```

You should see:
- `agento:query`
- `agento:cache:clear`
- `agento:mcp`
- `agento:cursor:install`
- `agento:magerun:install`

### 6. Check Permissions

Ensure Cursor can execute the PHP command and access Magento files:

```bash
ls -la /var/www/html/magento/bin/magento
php /var/www/html/magento/bin/magento --version
```

### 7. Enable Debug Logging

If the issue persists, check Cursor's developer console:
1. Open Cursor
2. Press `Cmd/Ctrl + Shift + P`
3. Type "Developer: Toggle Developer Tools"
4. Check Console tab for MCP-related errors

### 8. Reinstall MCP Configuration

Run the installer again:

```bash
cd /var/www/html/magento
php bin/magento agento:cursor:install
```

Then restart Cursor.

## Common Issues

### Issue: "No server info found"
- **Cause:** Initialize handshake didn't complete
- **Solution:** Check that PHP command works and Magento is accessible

### Issue: "Client closed for command"
- **Cause:** Server didn't respond in expected format or timing
- **Solution:** Verify server responds correctly to manual test (see step 1)

### Issue: "Command not found"
- **Cause:** PHP not in PATH or wrong path in config
- **Solution:** Use full path to PHP in `.cursor/mcp.json`

## Still Not Working?

1. Check Cursor version - ensure you're using the latest version
2. Review Cursor's MCP documentation
3. Check Cursor community forums for similar issues
4. Verify your system meets Cursor's requirements




