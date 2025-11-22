# MCP Resources Guide

MCP Resources allow AI assistants (like Cursor) to read structured data from your Magento installation. Resources are accessed via URIs and provide read-only access to configuration files and database schemas.

## Available Resources

### 1. Configuration Files (`config://{file}`)

Read any Magento configuration file.

**URI Format:** `config://app/etc/env.php`

**Examples:**
- `config://app/etc/env.php` - Environment configuration
- `config://app/etc/config.php` - Module configuration
- `config://app/etc/di.xml` - Dependency injection configuration

**Usage in Cursor:**
Just ask the AI: *"Read the env.php configuration file"* or *"Show me the database configuration from env.php"*

### 2. Database Table Schemas (`db://schema/{table}`)

Get detailed schema information for any Magento database table.

**URI Format:** `db://schema/{table_name}`

**Examples:**
- `db://schema/admin_user` - Admin users table schema
- `db://schema/catalog_product_entity` - Product entity table schema
- `db://schema/sales_order` - Sales orders table schema

**Usage in Cursor:**
Ask the AI: *"Show me the schema for the admin_user table"* or *"What columns are in the catalog_product_entity table?"*

## How Resources Work

Resources are automatically discovered by the MCP server using PHP attributes. The `php-mcp/server` library handles:

1. **Discovery**: Lists available resource templates via `resources/templates/list`
2. **Reading**: Fetches resource content via `resources/read` with a URI
3. **Caching**: Resources can be cached by the client for performance

## Resource Response Format

Resources return content in the MCP format:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "contents": [
      {
        "uri": "config://app/etc/env.php",
        "mimeType": "text/plain",
        "text": "...file content..."
      }
    ]
  }
}
```

For database schemas, the content is JSON:

```json
{
  "table": "admin_user",
  "full_table_name": "admin_user",
  "columns": [
    {
      "name": "user_id",
      "type": "int",
      "nullable": false,
      "primary": true,
      ...
    },
    ...
  ],
  "indexes": [...]
}
```

## Testing Resources

### Using Cursor AI

Simply ask the AI assistant in Cursor:
- *"Read the database configuration from env.php"*
- *"Show me the schema for the admin_user table"*
- *"What's the structure of the catalog_product_entity table?"*

The AI will automatically use the appropriate resource URI.

### Manual Testing

You can test resources manually using the MCP protocol:

```bash
# List available resource templates
echo -e 'Content-Length: 123\r\n\r\n{"jsonrpc":"2.0","id":1,"method":"resources/templates/list"}' | \
  php bin/magento agento:mcp

# Read a config file
echo -e 'Content-Length: 145\r\n\r\n{"jsonrpc":"2.0","id":2,"method":"resources/read","params":{"uri":"config://app/etc/env.php"}}' | \
  php bin/magento agento:mcp

# Get a table schema
echo -e 'Content-Length: 145\r\n\r\n{"jsonrpc":"2.0","id":3,"method":"resources/read","params":{"uri":"db://schema/admin_user"}}' | \
  php bin/magento agento:mcp
```

## Security

Resources include security checks:

- **Config files**: Path traversal protection - only files within Magento root are accessible
- **Database schemas**: Uses Magento's ResourceConnection which handles table prefixes and connection security
- **Read-only**: Resources are read-only - they cannot modify data

## Adding New Resources

To add a new resource, create a class in `app/code/Agento/Core/Mcp/Resources/` with the `#[McpResourceTemplate]` attribute:

```php
<?php
namespace Agento\Core\Mcp\Resources;

use PhpMcp\Server\Attributes\McpResourceTemplate;
use PhpMcp\Server\JsonRpc\Contents\TextContent;

class MyResource
{
    #[McpResourceTemplate(
        uriTemplate: 'my://resource/{param}',
        name: 'my_resource',
        description: 'Description of what this resource provides',
        mimeType: 'application/json'
    )]
    public function getResource(string $param): TextContent
    {
        // Fetch and return resource content
        return new TextContent(json_encode(['data' => '...']));
    }
}
```

The server will automatically discover and register the new resource.



