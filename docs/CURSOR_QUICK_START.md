# Quick Start: Agento MCP in Cursor

## Option 1: Automatic Installation (Recommended)

Run the install command:

```bash
php bin/magento agento:cursor:install
```

This will:
- Create `.cursor/mcp.json` in your project (can be committed to git)
- Attempt to automatically configure Cursor settings
- Provide manual instructions if auto-install fails

## Option 2: Manual Installation

### Add to Cursor Settings (JSON)

Open Cursor Settings (Cmd/Ctrl + ,) → Click `{}` icon → Add:

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

**Replace `/var/www/html/magento` with your Magento path!**

Or copy the contents from `.cursor/mcp.json` (created by the install command).

## 2. Restart Cursor

Quit and reopen Cursor completely.

## 3. Verify

Check Cursor's MCP server status - `agento` should be connected.

## 4. Test

Ask Cursor's AI:
- "Query the admin_user table"
- "Clear Magento cache"
- "Get system info using magerun"

## Troubleshooting

**Not connecting?**
```bash
# Test manually
cd /var/www/html/magento
php bin/magento agento:mcp
```

**Module not found?**
```bash
php bin/magento module:enable Agento_Core
php bin/magento setup:upgrade
php bin/magento cache:clean
```

**See full guide:** `CURSOR_SETUP.md`

