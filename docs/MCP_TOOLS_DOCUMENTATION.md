# MCP Tools Documentation for AI

This document describes the available MCP tools that AI assistants (like Cursor) can use to interact with your Magento installation.

## How AI Accesses Tool Documentation

The documentation for AI is **automatically generated** from the tool code itself:

1. **DocBlocks** - The `@param` descriptions in each tool method
2. **Type Hints** - PHP type hints define parameter types and requirements
3. **MCP Protocol** - The `php-mcp/server` library automatically converts this into MCP tool schemas
4. **Auto-Discovery** - AI clients query `tools/list` to get all available tools with their schemas

The AI doesn't need to read this file - it gets the information directly from the MCP server!

## Available Tools

### 1. `execute_sql`

**Description:** Execute SQL query against Magento database. Use this to query product data, orders, customers, configuration, or any other database tables. Returns results in table format by default, or JSON/CSV if specified.

**Parameters:**
- `query` (string, **required**): SQL query to execute
  - Example: `"SELECT * FROM catalog_product_entity LIMIT 10"`
  - Example: `"SELECT COUNT(*) FROM sales_order"`
- `connection` (string, optional, default: `"default"`): Database connection name
  - Options: `"default"` or `"indexer"`
- `format` (string, optional, default: `"table"`): Output format
  - Options: `"table"`, `"json"`, or `"csv"`

**Examples:**
```json
{
  "name": "execute_sql",
  "arguments": {
    "query": "SELECT * FROM admin_user LIMIT 5",
    "format": "json"
  }
}
```

```json
{
  "name": "execute_sql",
  "arguments": {
    "query": "SELECT COUNT(*) as total_products FROM catalog_product_entity",
    "connection": "default",
    "format": "table"
  }
}
```

---

### 2. `clear_cache`

**Description:** Clear Magento cache. Use this after making configuration changes, code updates, or when experiencing caching issues. Common cache types: config, layout, block_html, full_page, eav, collections. If no type specified, clears all cache types.

**Parameters:**
- `type` (string, optional): Cache type to clear
  - If not specified, clears **all** cache types
  - Common types: `config`, `layout`, `block_html`, `full_page`, `eav`, `collections`, `reflection`, `db_ddl`, `compiled_config`, `translate`, `config_integration`, `config_webservice`

**Examples:**
```json
{
  "name": "clear_cache",
  "arguments": {
    "type": "config"
  }
}
```

```json
{
  "name": "clear_cache",
  "arguments": {}
}
```
(No type = clears all cache)

---

### 3. `magerun`

**Description:** Execute any n98-magerun2 command - the Swiss army knife for Magento. Provides 100+ commands for database operations, cache management, admin users, system info, configuration, customers, indexing, modules, and setup. Use this for any magerun command not covered by specific category tools. See https://github.com/netz98/n98-magerun2 for full command list.

**Parameters:**
- `command` (string, **required**): Full magerun command path
  - Examples: `"db:query"`, `"cache:clean"`, `"sys:info"`, `"admin:user:list"`, `"config:store:get"`, `"customer:create"`, `"indexer:reindex"`, `"module:enable"`, `"setup:upgrade"`
- `arguments` (object, optional): Command arguments as key-value pairs
  - Example: `{"query": "SELECT * FROM admin_user"}` for `db:query`
  - Example: `{"path": "web/unsecure/base_url"}` for `config:store:get`
- `options` (object, optional): Command options as key-value pairs
  - Example: `{"type": "config"}` for `cache:clean`
  - Example: `{"format": "json"}` for `db:query`

**Examples:**

Get system information:
```json
{
  "name": "magerun",
  "arguments": {
    "command": "sys:info"
  }
}
```

Run database query:
```json
{
  "name": "magerun",
  "arguments": {
    "command": "db:query",
    "arguments": {
      "query": "SELECT * FROM admin_user LIMIT 5"
    }
  }
}
```

Clean specific cache type:
```json
{
  "name": "magerun",
  "arguments": {
    "command": "cache:clean",
    "options": {
      "type": "config"
    }
  }
}
```

List admin users:
```json
{
  "name": "magerun",
  "arguments": {
    "command": "admin:user:list"
  }
}
```

Get configuration value:
```json
{
  "name": "magerun",
  "arguments": {
    "command": "config:store:get",
    "arguments": {
      "path": "web/unsecure/base_url"
    }
  }
}
```

Reindex:
```json
{
  "name": "magerun",
  "arguments": {
    "command": "indexer:reindex",
    "arguments": {
      "index": "catalog_product_price"
    }
  }
}
```

## How AI Uses These Tools

When you ask Cursor (or any MCP client) to do something like:

- "Query the database to find all active admin users"
- "Clear the config cache"
- "Get system information"
- "List all Magento stores"
- "Check the base URL configuration"

The AI will:
1. Query `tools/list` to see available tools
2. Match your request to the appropriate tool
3. Call the tool with the correct parameters
4. Return the results to you

## Tool Schema Location

The tool schemas are automatically generated from:
- **File:** `app/code/Agento/Core/Mcp/Tools/*.php`
- **Method DocBlocks:** Parameter descriptions
- **Type Hints:** Parameter types and defaults
- **Attributes:** `#[McpTool(name: 'tool_name')]`

The `php-mcp/server` library reads these and generates JSON schemas that follow the MCP specification.

## Viewing Tool Schemas

To see the actual schemas that AI receives, you can:

1. **Use the list command:**
   ```bash
   php bin/magento agento:mcp:list
   ```

2. **Query the MCP server directly:**
   ```bash
   # Initialize and get tools list
   echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}' | \
   php bin/magento agento:mcp | grep -A 100 '"tools"'
   ```

3. **Check Cursor's MCP server panel** - It will show all available tools with their schemas

## Adding New Tools

To add a new tool:

1. Create a new class in `app/code/Agento/Core/Mcp/Tools/`
2. Add a method with `#[McpTool(name: 'tool_name')]` attribute
3. Add detailed DocBlock with `@param` descriptions
4. Use type hints for all parameters
5. The tool will be automatically discovered and available to AI!

Example:
```php
#[McpTool(name: 'my_new_tool')]
public function myNewTool(string $param1, ?int $param2 = null): TextContent
{
    /**
     * @param string $param1 Description of param1
     * @param int|null $param2 Description of param2 (optional)
     */
    // Implementation...
}
```

## Reference

- **MCP Protocol Spec:** https://modelcontextprotocol.io
- **php-mcp/server:** https://github.com/php-mcp/server
- **n98-magerun2 Docs:** https://github.com/netz98/n98-magerun2

