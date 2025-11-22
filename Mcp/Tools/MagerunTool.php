<?php
/**
 * Agento Core Module
 * MCP Tool: Execute Magerun Commands
 */

namespace Agento\Core\Mcp\Tools;

use Agento\Core\Mcp\Logger;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\JsonRpc\Contents\TextContent;
use Symfony\Component\Process\Process;

class MagerunTool
{
    /**
     * @var Logger|null
     */
    private ?Logger $logger = null;
    
    /**
     * Get logger instance
     *
     * @return Logger
     */
    private function getLogger(): Logger
    {
        if ($this->logger === null) {
            $this->logger = new Logger();
            $this->logger->setMagentoRoot($this->getMagentoRoot());
        }
        return $this->logger;
    }
    /**
     * Execute any n98-magerun2 command - the Swiss army knife for Magento.
     * 
     * Provides 100+ commands for database operations, cache management, admin users, 
     * system info, configuration, customers, indexing, modules, and setup.
     * 
     * ═══════════════════════════════════════════════════════════════════════════════
     * PARAMETER USAGE - Beautiful & Intuitive API
     * ═══════════════════════════════════════════════════════════════════════════════
     * 
     * ✨ Use descriptive parameter names instead of numeric keys:
     * 
     *   OLD (doesn't work): {"command": "admin:user:activate", "0": "admin"}  ❌
     *   NEW (beautiful):    {"command": "admin:user:activate", "username": "admin"}  ✅
     * 
     * 📋 Common Parameters:
     *   - username, email, user  → User identifiers (auto-mapped to positional args)
     *   - query                  → SQL queries for db:query
     *   - path                   → File/directory paths for dump/import commands
     *   - type                   → Cache types, entity types
     *   - format                 → Output format (json, csv, xml, yaml)
     *   - activate/deactivate    → Boolean flags
     *   - force                  → Skip confirmations
     *   - website, store         → Scope identifiers
     * 
     * 🎯 Why named parameters?
     *   PHP cannot have numeric parameter names ($0, $1), and the php-mcp/server library
     *   maps JSON-RPC arguments by name. So we use intuitive descriptive names that make
     *   the API self-documenting and beautiful!
     * 
     * 🔧 Fallback for edge cases: arg0, arg1, arg2 (when descriptive names aren't enough)
     * 
     * ═══════════════════════════════════════════════════════════════════════════════
     * COMMAND EXAMPLES
     * ═══════════════════════════════════════════════════════════════════════════════
     * 
     * Admin Commands:
     * 
     * - admin:token:create
     *   Description: Creates an Admin Token for Web API authentication. This token can be used for 
     *                programmatic access to the Magento backend via REST or GraphQL.
     *   Usage: admin:token:create <username> [--no-newline]
     *   Parameters:
     *     - username: Admin username to create token for
     *   Examples:
     *     - {"command": "admin:token:create", "username": "admin"}
     *     - {"command": "admin:token:create", "email": "admin@example.com"}
     *   Security Warning: Keep your admin tokens secure. Do not share them or expose them in 
     *                     version control or public scripts.
     * 
     * - admin:user:activate
     *   Description: Activates (enables) the specified admin user account. Allows you to activate 
     *                a Magento 2 admin user account from the command line.
     *   Usage: admin:user:activate <username|email>
     *   Aliases: admin:user:enable
     *   Parameters:
     *     - username: Username or email of the admin user to activate
     *   Examples:
     *     - {"command": "admin:user:activate", "username": "admin"}
     *     - {"command": "admin:user:activate", "email": "admin@example.com"}
     *   Notes: You must have sufficient permissions to run this command. Useful for quickly 
     *          enabling admin access without using the Magento backend. Use admin:user:list to 
     *          see all admin users.
     * 
     * - admin:user:deactivate
     *   Description: Deactivates (disables) the specified admin user account. Allows you to 
     *                deactivate a Magento 2 admin user account from the command line.
     *   Usage: admin:user:deactivate <username|email>
     *   Aliases: admin:user:disable
     *   Parameters:
     *     - username: Username or email of the admin user to deactivate
     *   Examples:
     *     - {"command": "admin:user:deactivate", "username": "admin"}
     *     - {"command": "admin:user:deactivate", "email": "admin@example.com"}
     *   Notes: You must have sufficient permissions to run this command. Useful for quickly 
     *          disabling admin access without using the Magento backend. Use admin:user:list to 
     *          see all admin users.
     * 
     * - admin:user:change-status
     *   Description: Changes the status of an admin user. Can be used to activate or deactivate 
     *                a user. If no option is provided, the status will be toggled.
     *   Usage: admin:user:change-status [options] <username>
     *   Parameters:
     *     - username: Username or email of the admin user to modify
     *     - activate: (optional, boolean) - Activates the specified user
     *     - deactivate: (optional, boolean) - Deactivates the specified user
     *   Examples:
     *     - Activate: {"command": "admin:user:change-status", "activate": true, "username": "john.doe"}
     *     - Deactivate: {"command": "admin:user:change-status", "deactivate": true, "username": "john.doe"}
     *     - Toggle: {"command": "admin:user:change-status", "username": "john.doe"}
     *   Notes: If neither activate nor deactivate is provided, the user's status will be 
     *          toggled (active becomes inactive, inactive becomes active).
     * 
     * - admin:user:change-password
     *   Description: Change admin user password from the command line.
     *   Usage: admin:user:change-password [username] [password]
     *   Parameters:
     *     - username: (optional) - Admin username to change password for
     *     - password: (optional) - New password for the admin user
     *   Examples:
     *     - {"command": "admin:user:change-password", "username": "admin", "password": "newpassword123"}
     *     - {"command": "admin:user:change-password", "username": "admin", "arg1": "newpassword123"}
     *   Notes: If username or password are not provided, the command will prompt for them 
     *          interactively.
     * 
     * - admin:user:delete
     *   Description: Delete an admin user from the system.
     *   Usage: admin:user:delete [-f|--force] [<id>]
     *   Parameters:
     *     - username: (optional) - Username or email of the admin user to delete
     *     - force: (optional, boolean) - Force deletion without confirmation
     *   Examples:
     *     - With prompt: {"command": "admin:user:delete", "username": "admin"}
     *     - Force delete: {"command": "admin:user:delete", "username": "admin", "force": true}
     *     - Using email: {"command": "admin:user:delete", "email": "admin@example.com", "force": true}
     *   Notes: If username is omitted, you will be prompted for it. If the force 
     *          parameter is omitted, you will be prompted for confirmation before deletion.
     * 
     * - admin:user:list
     *   Description: Displays a list of all admin users in the Magento installation. Provides 
     *                options to format the output and sort the list by various user attributes.
     *   Usage: admin:user:list [--format=<format>] [--sort=<field>] [--sort-order=<asc|desc>] 
     *          [--columns=<columns>]
     *   Parameters:
     *     - format: (optional) - Output format: csv, json, json_array, yaml, xml. Default: table
     *     - sort: (optional) - Sort by field: user_id, username, email, logdate, firstname, lastname
     *     - columns: (optional) - Comma-separated list of columns to display
     *   Examples:
     *     - Default: {"command": "admin:user:list"}
     *     - JSON format: {"command": "admin:user:list", "format": "json"}
     *     - Sorted: {"command": "admin:user:list", "sort": "username"}
     *     - Custom columns: {"command": "admin:user:list", "columns": "user_id,firstname,lastname,email,logdate"}
     *   Notes: By default, displays id, username, email, status, and logdate columns.
     * 
     * - admin:user:create
     *   Description: Create a new admin user account.
     *   Parameters:
     *     - username: Admin username
     *     - email: Admin email address
     *     - password: Admin password
     *   Example: {"command": "admin:user:create", "username": "admin", "email": "admin@example.com", "password": "password123"}
     * 
     * Cache Commands:
     * 
     * - cache:catalog:image:flush
     *   Description: Removes pre-generated catalog images and triggers the clean_catalog_images_cache_after 
     *                event, which should invalidate the full page cache.
     *   Usage: cache:catalog:image:flush [--suppress-event]
     *   Parameters (as arguments array):
     *     - Named option "suppress-event": (optional, boolean) - Suppress clean_catalog_images_cache_after 
     *       event dispatching
     *       Example: arguments: {"suppress-event": true}
     *   Full example: arguments: {"suppress-event": true} or arguments: {}
     * 
     * - cache:clean
     *   Description: Cleans expired cache entries in Magento. Use this command to keep your cache 
     *                storage optimized and up-to-date.
     *   Usage: cache:clean [type...]
     *   Parameters:
     *     - type: (optional) - Cache type(s) to clean. Use arg1, arg2 for additional types.
     *       Available types: config, layout, block_html, collections, reflection, db_ddl, eav, 
     *                        full_page, translate, etc.
     *   Examples:
     *     - Clean all: {"command": "cache:clean"}
     *     - Clean single: {"command": "cache:clean", "type": "config"}
     *     - Clean multiple: {"command": "cache:clean", "type": "config", "arg1": "layout", "arg2": "block_html"}
     *   Notes: To remove all cache entries completely, use cache:flush command instead.
     * 
     * - cache:disable
     *   Description: Disable Magento cache type(s).
     *   Usage: cache:disable [--format[=FORMAT]] [type...]
     *   Parameters:
     *     - type: (optional) - Cache type(s) to disable
     *     - format: (optional) - Output format: csv, json, json_array, yaml, xml
     *   Examples:
     *     - Disable all: {"command": "cache:disable"}
     *     - Disable single: {"command": "cache:disable", "type": "config"}
     *     - With format: {"command": "cache:disable", "type": "config", "format": "json"}
     *   Notes: Run cache:list command to see all cache codes.
     * 
     * - cache:enable
     *   Description: Enable Magento cache type(s).
     *   Usage: cache:enable [--format[=FORMAT]] [type...]
     *   Parameters:
     *     - type: (optional) - Cache type(s) to enable
     *     - format: (optional) - Output format: csv, json, json_array, yaml, xml
     *   Examples:
     *     - Enable all: {"command": "cache:enable"}
     *     - Enable single: {"command": "cache:enable", "type": "config"}
     *     - With format: {"command": "cache:enable", "type": "config", "format": "json"}
     *   Notes: Run cache:list command to see all cache codes.
     * 
     * - cache:flush
     *   Description: Remove all cache entries from cache backend. Clears the cache backend completely, 
     *                so other cache types in the same backend will be cleared as well.
     *   Usage: cache:flush [type...]
     *   Parameters:
     *     - type: (optional) - Cache type(s) to flush
     *   Examples:
     *     - Flush all: {"command": "cache:flush"}
     *     - Flush specific: {"command": "cache:flush", "type": "full_page"}
     *   Notes: cache:flush clears the cache backend completely - more aggressive than cache:clean.
     * 
     * - cache:list
     *   Description: List Magento cache status - shows all cache types and their enabled/disabled status.
     *   Usage: cache:list [--enabled[=ENABLED]] [--format[=FORMAT]]
     *   Parameters:
     *     - format: (optional) - Output format: csv, json, json_array, yaml, xml
     *   Examples:
     *     - List all: {"command": "cache:list"}
     *     - JSON format: {"command": "cache:list", "format": "json"}
     * 
     * - cache:remove:id
     *   Description: Remove cache entry by ID. The command is not checking if the cache id exists by 
     *                default. Use --strict option to only remove if cache id exists.
     *   Usage: cache:remove:id [--strict] <id>
     *   Parameters (as arguments array):
     *     - Positional argument [0]: id (required) - Cache ID to remove
     *       Example: arguments: [0 => "CACHE_ID_HERE"]
     *     - Named option "strict": (optional, boolean) - Use strict mode (remove only if cache id exists)
     *       Example: arguments: {"strict": true, "0": "CACHE_ID_HERE"}
     *   Full examples:
     *     - Remove by ID: arguments: [0 => "catalog_product_123"]
     *     - Remove with strict check: arguments: {"strict": true, "0": "catalog_product_123"}
     *   Notes: By default, the command does not check if the cache id exists. Use --strict to ensure 
     *          the cache id exists before attempting removal.
     * 
     * - cache:report
     *   Description: Investigate what's stored inside your cache. Prints out a table with cache IDs, 
     *                tags, and modification times.
     *   Usage: cache:report [options]
     *   Parameters (as arguments array):
     *     - Named option "fpc": (optional, boolean) - Use full page cache instead of core cache
     *       Example: arguments: {"fpc": true}
     *     - Named option "tags" or "t": (optional, boolean) - Output tags
     *       Example: arguments: {"tags": true} or arguments: {"t": true}
     *     - Named option "mtime" or "m": (optional, boolean) - Output last modification time
     *       Example: arguments: {"mtime": true} or arguments: {"m": true}
     *     - Named option "filter-id": (optional, string) - Filter output by ID (substring match)
     *       Example: arguments: {"filter-id": "catalog_product"}
     *     - Named option "filter-tag": (optional, string) - Filter output by TAG (comma-separated for multiple)
     *       Example: arguments: {"filter-tag": "CATALOG_PRODUCT"} or arguments: {"filter-tag": "CATALOG_PRODUCT,STORE"}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml.
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - Basic report: arguments: {}
     *     - With tags and mtime: arguments: {"tags": true, "mtime": true}
     *     - Filter by ID: arguments: {"filter-id": "catalog_product"}
     *     - Filter by tags: arguments: {"filter-tag": "CATALOG_PRODUCT"}
     *     - Full page cache report: arguments: {"fpc": true, "tags": true}
     *     - JSON format: arguments: {"format": "json", "tags": true}
     * 
     * - cache:view
     *   Description: Prints stored cache entry by ID. Allows you to inspect the actual cache content.
     *   Usage: cache:view [options] <id>
     *   Parameters (as arguments array):
     *     - Positional argument [0]: id (required) - Cache ID to view
     *       Example: arguments: [0 => "CACHE_ID_HERE"]
     *     - Named option "fpc": (optional, boolean) - Use full page cache instead of core cache
     *       Example: arguments: {"fpc": true, "0": "page_cache_id"}
     *     - Named option "unserialize": (optional, boolean) - Unserialize output
     *       Example: arguments: {"unserialize": true, "0": "cache_id"}
     *     - Named option "decrypt": (optional, boolean) - Decrypt output with encryption key
     *       Example: arguments: {"decrypt": true, "0": "cache_id"}
     *   Full examples:
     *     - View cache entry: arguments: [0 => "catalog_product_123"]
     *     - View with unserialize: arguments: {"unserialize": true, "0": "cache_id"}
     *     - View FPC entry: arguments: {"fpc": true, "0": "page_cache_id"}
     *     - View decrypted: arguments: {"decrypt": true, "0": "encrypted_cache_id"}
     *     - Combined: arguments: {"fpc": true, "unserialize": true, "decrypt": true, "0": "cache_id"}
     * 
     * Composer Commands:
     * 
     * - composer:redeploy-base-packages
     *   Description: Redeploys all base packages as defined in the command configuration. Useful for 
     *                reapplying the deployment of Magento 2 modules and related files, especially after 
     *                changes to deployment strategies or mappings. If there are changes in base packages 
     *                (e.g. magento2-base), the Composer installer does not copy them again because all 
     *                the main project files are only copied once. This command allows you to redeploy 
     *                those files without requiring a full Composer reinstall.
     *   Usage: composer:redeploy-base-packages
     *   Parameters (as arguments array):
     *     - No parameters needed
     *   Full example: arguments: {}
     *   Notes: This command does not reinstall or update Composer packages. It only redeploys files 
     *          according to the current deployment configuration. The config.yaml shipped in the 
     *          n98-magerun2.phar defines a list of packages. The package list can be extended by a 
     *          custom config file. Introduced with version 7.1.0.
     * 
     * Config Commands:
     * 
     * - config:data:acl
     *   Description: Prints acl.xml data as table. Shows all access control list configurations from 
     *                merged acl.xml files.
     *   Usage: config:data:acl
     *   Parameters (as arguments array):
     *     - No parameters needed
     *   Full example: arguments: {}
     * 
     * - config:data:di
     *   Description: Print Dependency Injection Config Data. Shows DI configuration from all merged 
     *                di.xml files.
     *   Usage: config:data:di [--scope=SCOPE] [<type>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: type (optional) - Type (class) to filter
     *       Example: arguments: [0 => "SomeClassName"]
     *     - Named option "scope" or "s": (optional, string) - Config scope. Options: global, adminhtml, 
     *       frontend, webapi_rest, webapi_soap, etc. Default: global
     *       Example: arguments: {"scope": "adminhtml"} or arguments: {"s": "frontend"}
     *   Full examples:
     *     - All DI config: arguments: {}
     *     - Specific scope: arguments: {"scope": "adminhtml"}
     *     - Filter by type: arguments: [0 => "SomeClassName"]
     *     - Combined: arguments: {"scope": "frontend", "0": "SomeClassName"}
     * 
     * - config:data:indexer
     *   Description: Print the data of all merged indexer.xml files. Shows indexer configurations.
     *   Usage: config:data:indexer [options]
     *   Parameters (as arguments array):
     *     - Named option "scope" or "s": (optional, string) - Config scope. Options: global, adminhtml, 
     *       frontend, webapi_rest, webapi_soap, etc. Default: global
     *       Example: arguments: {"scope": "adminhtml"}
     *     - Named option "tree" or "t": (optional, boolean) - Print data as tree
     *       Example: arguments: {"tree": true} or arguments: {"t": true}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml.
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - Basic: arguments: {}
     *     - As tree: arguments: {"tree": true}
     *     - JSON format: arguments: {"format": "json"}
     *     - Combined: arguments: {"scope": "adminhtml", "tree": true, "format": "json"}
     * 
     * - config:data:mview
     *   Description: Print the data of all merged mview.xml files. Shows materialized view configurations.
     *   Usage: config:data:mview [options]
     *   Parameters (as arguments array):
     *     - Named option "scope" or "s": (optional, string) - Config scope. Options: global, adminhtml, 
     *       frontend, webapi_rest, webapi_soap, etc. Default: global
     *       Example: arguments: {"scope": "adminhtml"}
     *     - Named option "tree" or "t": (optional, boolean) - Print data as tree
     *       Example: arguments: {"tree": true} or arguments: {"t": true}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml.
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - Basic: arguments: {}
     *     - As tree: arguments: {"tree": true}
     *     - JSON format: arguments: {"format": "json"}
     *     - Combined: arguments: {"scope": "adminhtml", "tree": true, "format": "json"}
     * 
     * - config:env:create
     *   Description: Create app/etc/env.php interactively. Helps you create the env.php file required 
     *                by Magento 2. Guides you through the process interactively, allowing you to set 
     *                up all necessary configuration values. If the file already exists, it can update 
     *                existing values as needed. To update a single value, use config:env:set instead.
     *   Usage: config:env:create
     *   Parameters (as arguments array):
     *     - No parameters needed (interactive command)
     *   Full example: arguments: {}
     *   Notes: Interactive command. You will be prompted for necessary configuration values 
     *          (database, crypt key, etc.). Supports all required Magento 2 environment settings.
     * 
     * - config:env:delete
     *   Description: Remove a configuration from the env.php file by providing a key. Sub-arrays in 
     *                config.php can be specified by adding a "." character to every array level.
     *   Usage: config:env:delete <key>
     *   Parameters (as arguments array):
     *     - Positional argument [0]: key (required) - Configuration key to delete. Use "." for 
     *       sub-arrays (e.g., "cache.frontend.default.backend")
     *       Example: arguments: [0 => "system"] or arguments: [0 => "cache.frontend.default.backend"]
     *   Full examples:
     *     - Delete system: arguments: [0 => "system"]
     *     - Delete nested key: arguments: [0 => "cache.frontend.default.backend"]
     *     - Delete nested options: arguments: [0 => "cache.frontend.default.backend_options"]
     * 
     * - config:env:set
     *   Description: Set a single value in env.php by providing a key and an optional value. The command 
     *                will save an empty string as default value if no value is set. Sub-arrays in config.php 
     *                can be specified by adding a "." character to every array level. Can also accept JSON 
     *                format for non-string values.
     *   Usage: config:env:set <key> [<value>] [--input-format=INPUT-FORMAT]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: key (required) - Configuration key. Use "." for sub-arrays
     *       Example: arguments: [0 => "backend.frontName"]
     *     - Positional argument [1]: value (optional) - Configuration value. If omitted, saves empty string
     *       Example: arguments: [0 => "backend.frontName", 1 => "mybackend"]
     *     - Named option "input-format": (optional, string) - Input format. One of: plain, json. 
     *       Default: plain. Use json for non-string values (boolean, array, etc.)
     *       Example: arguments: {"input-format": "json", "0": "directories.document_root_is_pub", "1": "true"}
     *   Full examples:
     *     - Plain string: arguments: [0 => "backend.frontName", 1 => "mybackend"]
     *     - Crypt key: arguments: [0 => "crypt.key", 1 => "bb5b0075303a9bb8e3d210a971674367"]
     *     - Nested key: arguments: [0 => "session.redis.host", 1 => "192.168.1.1"]
     *     - Special chars: arguments: [0 => "x-frame-options", 1 => "*"]
     *     - JSON boolean: arguments: {"input-format": "json", "0": "directories.document_root_is_pub", "1": "true"}
     *     - JSON integer: arguments: {"input-format": "json", "0": "queue.consumers_wait_for_messages", "1": "0"}
     *     - JSON array: arguments: {"input-format": "json", "0": "cron_consumers_runner.consumers", "1": "[\"some.consumer\", \"some.other.consumer\"]"}
     * 
     * - config:env:show
     *   Description: Show env.php settings. If no key is passed, the whole content of the file is 
     *                displayed as table.
     *   Usage: config:env:show [options] [<key>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: key (optional) - Specific key to show. If omitted, shows all
     *       Example: arguments: [0 => "backend.frontName"]
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml.
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - Show all: arguments: {}
     *     - Show specific key: arguments: [0 => "backend.frontName"]
     *     - JSON format: arguments: {"format": "json"}
     *     - CSV format: arguments: {"format": "csv"}
     *     - XML format: arguments: {"format": "xml"}
     *     - Show key in JSON: arguments: {"format": "json", "0": "backend.frontName"}
     * 
     * - config:search
     *   Description: Search in the store config meta data (labels). The output is a table with id, 
     *                type and name of the config item. Type can be one of: section, group, field.
     *   Usage: config:search [--format[="..."]] <search>
     *   Parameters (as arguments array):
     *     - Positional argument [0]: search (required) - Search term to find in config metadata/labels
     *       Example: arguments: [0 => "base_url"]
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml.
     *       Example: arguments: {"format": "json", "0": "base_url"}
     *   Full examples:
     *     - Basic search: arguments: [0 => "base_url"]
     *     - JSON format: arguments: {"format": "json", "0": "base_url"}
     * 
     * - config:store:delete
     *   Description: Delete a store config value. Removes configuration values from database.
     *   Usage: config:store:delete [--scope[="..."]] [--scope-id[="..."]] [--all] <path>
     *   Parameters (as arguments array):
     *     - Positional argument [0]: path (required) - The config path to delete
     *       Example: arguments: [0 => "web/unsecure/base_url"]
     *     - Named option "scope": (optional, string) - The config value's scope. Options: default, 
     *       websites, stores. Default: default
     *       Example: arguments: {"scope": "websites", "0": "path"}
     *     - Named option "scope-id": (optional, string or integer) - The config value's scope ID
     *       Example: arguments: {"scope": "websites", "scope-id": "1", "0": "path"}
     *     - Named option "all": (optional, boolean) - Delete all entries by path across all scopes
     *       Example: arguments: {"all": true, "0": "path"}
     *   Full examples:
     *     - Delete from default: arguments: [0 => "web/unsecure/base_url"]
     *     - Delete from website: arguments: {"scope": "websites", "scope-id": "1", "0": "path"}
     *     - Delete from store: arguments: {"scope": "stores", "scope-id": "2", "0": "path"}
     *     - Delete all: arguments: {"all": true, "0": "web/unsecure/base_url"}
     * 
     * - config:store:get
     *   Description: Get a store config value. If path is not set, all available config items will be 
     *                listed. path may contain wildcards (*).
     *   Usage: config:store:get [--scope="..."] [--scope-id="..."] [--decrypt] [--update-script] 
     *          [--magerun-script] [--format[="..."]] [path]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: path (optional) - The config path. Wildcards (*) are supported. 
     *       If not set, all items are listed.
     *       Example: arguments: [0 => "web/unsecure/base_url"] or arguments: [0 => "web/*"]
     *     - Named option "scope": (optional, string) - The config value's scope. Options: default, 
     *       websites, stores. Default: default
     *       Example: arguments: {"scope": "websites", "0": "path"}
     *     - Named option "scope-id": (optional, string or integer) - The config value's scope ID or 
     *       scope code
     *       Example: arguments: {"scope": "websites", "scope-id": "1", "0": "path"}
     *     - Named option "decrypt": (optional, boolean) - Decrypt the config value using crypt key 
     *       defined in env.php
     *       Example: arguments: {"decrypt": true, "0": "path"}
     *     - Named option "update-script": (optional, boolean) - Output as update script lines
     *       Example: arguments: {"update-script": true, "0": "path"}
     *     - Named option "magerun-script": (optional, boolean) - Output for usage with config:store:set
     *       Example: arguments: {"magerun-script": true, "0": "web/*"}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml.
     *       Example: arguments: {"format": "json", "0": "path"}
     *   Full examples:
     *     - Get all config: arguments: {}
     *     - Get specific path: arguments: [0 => "web/unsecure/base_url"]
     *     - Get with wildcard: arguments: [0 => "web/*"]
     *     - Get from website: arguments: {"scope": "websites", "scope-id": "1", "0": "path"}
     *     - Decrypt value: arguments: {"decrypt": true, "0": "path"}
     *     - Magerun script output: arguments: {"magerun-script": true, "0": "web/*"}
     *     - JSON format: arguments: {"format": "json", "0": "path"}
     * 
     * - config:store:set
     *   Description: Set a store config value. Updates or creates configuration values in the database.
     *   Usage: config:store:set [--scope[="..."]] [--scope-id[="..."]] [--encrypt] [--no-null] <path> <value>
     *   Parameters (as arguments array):
     *     - Positional argument [0]: path (required) - The store config path like "general/local/code"
     *       Example: arguments: [0 => "web/unsecure/base_url"]
     *     - Positional argument [1]: value (required) - The config value
     *       Example: arguments: [0 => "web/unsecure/base_url", 1 => "https://example.com"]
     *     - Named option "scope": (optional, string) - The config value's scope. Options: default, 
     *       websites, stores. Default: default
     *       Example: arguments: {"scope": "websites", "0": "path", "1": "value"}
     *     - Named option "scope-id": (optional, string or integer) - The config value's scope ID. 
     *       Default: 0
     *       Example: arguments: {"scope": "websites", "scope-id": "1", "0": "path", "1": "value"}
     *     - Named option "encrypt": (optional, boolean) - Encrypt the config value using crypt key
     *       Example: arguments: {"encrypt": true, "0": "path", "1": "value"}
     *     - Named option "no-null": (optional, boolean) - Do not treat value NULL as NULL (NULL/"unknown" 
     *       value) value
     *       Example: arguments: {"no-null": true, "0": "path", "1": "null"}
     *   Full examples:
     *     - Set in default: arguments: [0 => "web/unsecure/base_url", 1 => "https://example.com"]
     *     - Set in website: arguments: {"scope": "websites", "scope-id": "1", "0": "path", "1": "value"}
     *     - Set in store: arguments: {"scope": "stores", "scope-id": "2", "0": "path", "1": "value"}
     *     - Encrypt value: arguments: {"encrypt": true, "0": "path", "1": "sensitive_value"}
     * 
     * Database Commands:
     * 
     * - db:add-default-authorization-entries
     *   Description: Fix empty authorization tables. If you run db:dump with stripped option and 
     *                @admin group, the authorization_rule and authorization_role tables are empty. 
     *                This blocks the creation of admin users. You can re-create the default entries 
     *                by running this command.
     *   Usage: db:add-default-authorization-entries [--connection=CONNECTION]
     *   Parameters (as arguments array):
     *     - Named option "connection": (optional, string) - Select DB connection type for Magento 
     *       configurations with several databases
     *       Example: arguments: {"connection": "default"}
     *   Full example: arguments: {"connection": "default"} or arguments: {}
     *   Notes: If you are using db:import command to import the stripped SQL dump, then this 
     *          command will be implicitly called unless --skip-authorization-entry-creation is used.
     * 
     * - db:console
     *   Description: Open MySQL Console. Provides interactive access to MySQL/MariaDB database.
     *   Usage: db:console [options]
     *   Parameters (as arguments array):
     *     - Named option "use-mycli-instead-of-mysql": (optional, boolean) - Use mycli as the MySQL 
     *       client instead of mysql
     *       Example: arguments: {"use-mycli-instead-of-mysql": true}
     *     - Named option "no-auto-rehash": (optional, boolean) - Same as -A option to MySQL client 
     *       to turn off auto-complete (avoids long initial connection time)
     *       Example: arguments: {"no-auto-rehash": true}
     *     - Named option "connection": (optional, string) - Select DB connection type for Magento 
     *       configurations with several databases. Default: default
     *       Example: arguments: {"connection": "default"}
     *   Full examples:
     *     - Basic console: arguments: {}
     *     - With mycli: arguments: {"use-mycli-instead-of-mysql": true}
     *     - No auto-rehash: arguments: {"no-auto-rehash": true}
     *     - Specific connection: arguments: {"connection": "indexer"}
     * 
     * - db:create
     *   Description: Create currently configured database. The command tries to create the configured 
     *                database according to your settings in app/etc/env.php. The configured user must 
     *                have "CREATE DATABASE" privileges on MySQL Server.
     *   Usage: db:create [--connection=CONNECTION]
     *   Parameters (as arguments array):
     *     - Named option "connection": (optional, string) - Select DB connection type for Magento 
     *       configurations with several databases
     *       Example: arguments: {"connection": "default"}
     *   Full example: arguments: {"connection": "default"} or arguments: {}
     * 
     * - db:dump
     *   Description: Dump configured Magento database with mysqldump, mariadb-dump, or mydumper. 
     *                Requires MySQL or MariaDB CLI tools (mysqldump/mariadb-dump or mydumper). Use 
     *                the --force option with caution, as it will skip confirmation prompts. The --strip 
     *                option can remove important data from the dump; review your table groups before 
     *                using it.
     *   Usage: db:dump [options] [--] [<filename>]
     *   Parameters:
     *     - path: (optional) - Dump filename
     *     - force: (optional, boolean) - Skip confirmation
     *   Examples:
     *     - {"command": "db:dump", "path": "dump.sql"}
     *     - {"command": "db:dump", "path": "dump.sql", "force": true}
     *     - Named option "connection": (optional, string) - Select DB connection type. Default: default
     *       Example: arguments: {"connection": "default"}
     *     - Named option "add-routines": (optional, boolean) - Include stored routines in dump 
     *       (procedures & functions)
     *       Example: arguments: {"add-routines": true}
     *     - Named option "add-time": (optional, string) - Append or prepend a timestamp to filename. 
     *       Values: suffix, prefix, no. Default: no
     *       Example: arguments: {"add-time": "suffix"}
     *     - Named option "compression" or "c": (optional, string) - Compress the dump file using one 
     *       of the supported algorithms (e.g., gzip, lz4, zstd)
     *       Example: arguments: {"compression": "gzip"} or arguments: {"c": "zstd"}
     *     - Named option "dry-run": (optional, boolean) - Do everything but the actual dump. Useful 
     *       to test
     *       Example: arguments: {"dry-run": true}
     *     - Named option "exclude" or "e": (optional, string or array) - Tables to exclude entirely 
     *       from the dump (including structure). Multiple values allowed. Wildcards (*, ?) supported
     *       Example: arguments: {"exclude": "admin_*"} or arguments: {"e": "log_*"}
     *     - Named option "force" or "f": (optional, boolean) - Do not prompt if all options are defined
     *       Example: arguments: {"force": true}
     *     - Named option "git-friendly": (optional, boolean) - Use one insert statement, but with 
     *       line breaks instead of separate insert statements
     *       Example: arguments: {"git-friendly": true}
     *     - Named option "human-readable": (optional, boolean) - Use a single insert with column 
     *       names per row. Use db:import --optimize for faster import
     *       Example: arguments: {"human-readable": true}
     *     - Named option "include" or "i": (optional, string or array) - Tables to include entirely 
     *       in the dump (default: all tables are included). Multiple values allowed. Wildcards (*, ?) 
     *       supported
     *       Example: arguments: {"include": "admin_user"} or arguments: {"i": "catalog_*"}
     *     - Named option "keep-definer": (optional, boolean) - Do not replace DEFINER in dump with 
     *       CURRENT_USER
     *       Example: arguments: {"keep-definer": true}
     *     - Named option "keep-column-statistics": (optional, boolean) - Retains column statistics 
     *       table in mysqldump
     *       Example: arguments: {"keep-column-statistics": true}
     *     - Named option "mydumper": (optional, boolean) - Use mydumper instead of mysqldump for 
     *       potentially faster dumps
     *       Example: arguments: {"mydumper": true}
     *     - Named option "no-single-transaction": (optional, boolean) - Do not use single-transaction 
     *       (not recommended, this is blocking)
     *       Example: arguments: {"no-single-transaction": true}
     *     - Named option "no-tablespaces": (optional, boolean) - Use this option if you want to 
     *       create a dump without having the PROCESS privilege
     *       Example: arguments: {"no-tablespaces": true}
     *     - Named option "only-command": (optional, boolean) - Print only mysqldump/mariadb-dump/mydumper 
     *       command. Does not execute
     *       Example: arguments: {"only-command": true}
     *     - Named option "print-only-filename": (optional, boolean) - Execute and prints no output 
     *       except the dump filename
     *       Example: arguments: {"print-only-filename": true}
     *     - Named option "set-gtid-purged-off": (optional, boolean) - Adds --set-gtid-purged=OFF to 
     *       mysqldump
     *       Example: arguments: {"set-gtid-purged-off": true}
     *     - Named option "stdout": (optional, boolean) - Dump to stdout
     *       Example: arguments: {"stdout": true}
     *     - Named option "strip" or "s": (optional, string) - Tables to strip (dump only structure 
     *       of those tables). Multiple values and table groups (e.g. @log) allowed. Wildcards (*, ?) 
     *       supported
     *       Example: arguments: {"strip": "@development"} or arguments: {"s": "@log @sessions"}
     *     - Named option "views": (optional, boolean) - Explicitly include views in the dump. Views 
     *       are included by default if not otherwise excluded
     *       Example: arguments: {"views": true}
     *     - Named option "no-views": (optional, boolean) - Exclude all views from the dump. This 
     *       overrides any other view inclusion
     *       Example: arguments: {"no-views": true}
     *     - Named option "zstd-level": (optional, integer) - ZSTD compression level. Default: 10
     *       Example: arguments: {"zstd-level": 15}
     *     - Named option "zstd-extra-args": (optional, string) - Custom extra options for zstd
     *       Example: arguments: {"zstd-extra-args": "--threads=4"}
     *   Full examples:
     *     - Basic dump: arguments: [0 => "dump.sql"]
     *     - With compression: arguments: {"compression": "gzip", "0": "dump.sql.gz"}
     *     - Stripped dump: arguments: {"strip": "@development", "0": "dump.sql"}
     *     - Include specific tables: arguments: {"include": "admin_user", "0": "dump.sql"}
     *     - Exclude tables: arguments: {"exclude": "admin_*", "0": "dump.sql"}
     *     - With timestamp: arguments: {"add-time": "suffix", "0": "dump.sql"}
     *     - To stdout: arguments: {"stdout": true}
     *     - Only command: arguments: {"only-command": true, "0": "dump.sql"}
     *     - Mydumper: arguments: {"mydumper": true, "0": "dump.sql"}
     *     - No views: arguments: {"no-views": true, "0": "dump.sql"}
     *   Notes: Available table groups: @2fa, @admin, @aggregated, @customers, @development, 
     *          @dotmailer, @ee_changelog, @idx, @klarna, @log, @mailchimp, @newrelic_reporting, 
     *          @oauth, @quotes, @replica, @sales, @search, @sessions, @stripped, @trade, @temp. 
     *          If both --include and --exclude are used, --include selects tables first, then 
     *          --exclude removes matches. Explicitly included tables always take precedence over 
     *          exclusions. Views are included by default unless --no-views is used.
     * 
     * - db:import
     *   Description: Import database. Requires MySQL or MariaDB CLI tools. Using the --drop or 
     *                --drop-tables options will remove existing data before import. Make sure you 
     *                have backups and understand the consequences before using these options.
     *   Usage: db:import [options] [<filename>]
     *   Parameters:
     *     - path: (required) - Dump filename to import
     *     - force: (optional, boolean) - Skip confirmation
     *   Examples:
     *     - {"command": "db:import", "path": "dump.sql.gz"}
     *     - {"command": "db:import", "path": "dump.sql", "force": true}
     *     - Named option "connection": (optional, string) - Select DB connection type for Magento 
     *       configurations with several databases
     *       Example: arguments: {"connection": "default"}
     *     - Named option "compression" or "c": (optional, string) - The compression of the specified 
     *       file (e.g. gzip, lz4, zstd)
     *       Example: arguments: {"compression": "gzip", "0": "dump.sql.gz"}
     *     - Named option "zstd-level": (optional, integer) - ZSTD compression level. Default: 10
     *       Example: arguments: {"zstd-level": 15}
     *     - Named option "zstd-extra-args": (optional, string) - Custom extra options for zstd
     *       Example: arguments: {"zstd-extra-args": "--threads=4"}
     *     - Named option "drop": (optional, boolean) - Drop and recreate database before import
     *       Example: arguments: {"drop": true, "0": "dump.sql"}
     *     - Named option "drop-tables": (optional, boolean) - Drop tables before import
     *       Example: arguments: {"drop-tables": true, "0": "dump.sql"}
     *     - Named option "force": (optional, boolean) - Continue even if an SQL error occurs
     *       Example: arguments: {"force": true, "0": "dump.sql"}
     *     - Named option "only-command": (optional, boolean) - Print only mysql/mariadb command. 
     *       Do not execute
     *       Example: arguments: {"only-command": true, "0": "dump.sql"}
     *     - Named option "only-if-empty": (optional, boolean) - Imports only if database is empty
     *       Example: arguments: {"only-if-empty": true, "0": "dump.sql"}
     *     - Named option "optimize": (optional, boolean) - Convert verbose INSERTs to short ones 
     *       before import (not working with compression)
     *       Example: arguments: {"optimize": true, "0": "dump.sql"}
     *     - Named option "skip-authorization-entry-creation": (optional, boolean) - Do not create 
     *       authorization rule/role entries if they are missing
     *       Example: arguments: {"skip-authorization-entry-creation": true, "0": "dump.sql"}
     *   Full examples:
     *     - Basic import: arguments: [0 => "dump.sql"]
     *     - Compressed import: arguments: {"compression": "gzip", "0": "dump.sql.gz"}
     *     - Drop database first: arguments: {"drop": true, "0": "dump.sql"}
     *     - Drop tables first: arguments: {"drop-tables": true, "0": "dump.sql"}
     *     - Force continue: arguments: {"force": true, "0": "dump.sql"}
     *     - Only if empty: arguments: {"only-if-empty": true, "0": "dump.sql"}
     *     - Optimize inserts: arguments: {"optimize": true, "0": "dump.sql"}
     *   Notes: Warning: Using --drop or --drop-tables will remove existing data. Always have backups.
     *          The db:add-default-authorization-entries command will be implicitly called unless 
     *          --skip-authorization-entry-creation is used.
     * 
     * - db:info
     *   Description: Dumps database information. Use this command to quickly print all information 
     *                about the current configured database in app/etc/env.php, including connection 
     *                strings for JDBC and PDO. Helpful for debugging and environment checks.
     *   Usage: db:info [options] [--] [<setting>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: setting (optional) - Only output value of named setting
     *       Example: arguments: [0 => "host"]
     *     - Named option "connection": (optional, string) - Select DB connection type for Magento 
     *       configurations with several databases
     *       Example: arguments: {"connection": "default"}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - All info: arguments: {}
     *     - Specific setting: arguments: [0 => "host"]
     *     - JSON format: arguments: {"format": "json"}
     *     - Combined: arguments: {"format": "json", "0": "host", "connection": "default"}
     *   Notes: Can print connection string for JDBC, PDO connections.
     * 
     * - db:query
     *   Description: Run a raw DB query. Running raw SQL queries can affect your database and should 
     *                be done with caution, especially in production environments. Always review your 
     *                queries before execution.
     *   Usage: db:query [--connection=CONNECTION] [--only-command] [--format=FORMAT] [<query>]
     *   Parameters:
     *     - query: (optional) - SQL query to execute
     *     - format: (optional) - Output format: csv, json, json_array, yaml, xml
     *   Examples:
     *     - {"command": "db:query", "query": "SELECT * FROM admin_user LIMIT 5"}
     *     - {"command": "db:query", "query": "SHOW TABLES", "format": "json"}
     *     - Named option "connection": (optional, string) - Select DB connection type for Magento 
     *       configurations with several databases
     *       Example: arguments: {"connection": "default"}
     *     - Named option "only-command": (optional, boolean) - Print only mysql command. Do not execute
     *       Example: arguments: {"only-command": true, "0": "SELECT * FROM admin_user"}
     *     - Named option "format": (optional, string) - Output format. Currently only csv is supported
     *       Example: arguments: {"format": "csv", "0": "SELECT * FROM admin_user"}
     *   Full examples:
     *     - Basic query: arguments: [0 => "SELECT * FROM store"]
     *     - CSV format: arguments: {"format": "csv", "0": "SELECT * FROM store"}
     *     - Only command: arguments: {"only-command": true, "0": "SELECT * FROM store"}
     *   Notes: Warning: Running raw SQL queries can affect your database. Always review queries 
     *          before execution, especially in production.
     * 
     * - db:status
     *   Description: Shows important server status information or custom selected status values. Use 
     *                this command to monitor server health or troubleshoot issues by viewing important 
     *                MySQL server status variables. You can filter results using the search argument.
     *   Usage: db:status [options] [--] [<search>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: search (optional) - Only output variables of specified name. 
     *       The wildcard % is supported
     *       Example: arguments: [0 => "Threads%"]
     *     - Named option "connection": (optional, string) - Select DB connection type for Magento 
     *       configurations with several databases
     *       Example: arguments: {"connection": "default"}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *     - Named option "rounding": (optional, integer) - Amount of decimals to display. If -1 then 
     *       disabled. Default: 0
     *       Example: arguments: {"rounding": 2}
     *     - Named option "no-description": (optional, boolean) - Disable description
     *       Example: arguments: {"no-description": true}
     *   Full examples:
     *     - All status: arguments: {}
     *     - Filtered: arguments: [0 => "Threads%"]
     *     - JSON format: arguments: {"format": "json"}
     *     - With rounding: arguments: {"rounding": 2}
     *     - Combined: arguments: {"format": "json", "rounding": 2, "0": "Threads%"}
     * 
     * - db:variables
     *   Description: Shows important variables or custom selected. Use this command to check MySQL 
     *                server configuration or troubleshoot issues by viewing important server variables. 
     *                You can filter results using the search argument.
     *   Usage: db:variables [options] [--] [<search>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: search (optional) - Only output variables of specified name. 
     *       The wildcard % is supported
     *       Example: arguments: [0 => "max_connections%"]
     *     - Named option "connection": (optional, string) - Select DB connection type for Magento 
     *       configurations with several databases
     *       Example: arguments: {"connection": "default"}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *     - Named option "rounding": (optional, integer) - Amount of decimals to display. If -1 then 
     *       disabled. Default: 0
     *       Example: arguments: {"rounding": 2}
     *     - Named option "no-description": (optional, boolean) - Disable description
     *       Example: arguments: {"no-description": true}
     *   Full examples:
     *     - All variables: arguments: {}
     *     - Filtered: arguments: [0 => "max_connections%"]
     *     - JSON format: arguments: {"format": "json"}
     *     - With rounding: arguments: {"rounding": 2}
     *     - Combined: arguments: {"format": "json", "rounding": 2, "0": "max_connections%"]
     * 
     * Customer Commands:
     * - customer:create
     *   Parameters: email, password, username (optional), website
     *   Example: {"command": "customer:create", "email": "customer@example.com", "password": "password123", "website": "base"}
     * 
     * - customer:list
     *   Parameters: website (optional)
     *   Example: {"command": "customer:list", "website": "base"}
     * 
     * - customer:info
     *   Parameters: email
     *   Example: {"command": "customer:info", "email": "customer@example.com"}
     * 
     * - customer:delete
     *   Parameters: email, force (optional)
     *   Example: {"command": "customer:delete", "email": "customer@example.com", "force": true}
     * 
     * EAV Commands:
     * 
     * - eav:attribute:list
     *   Description: List EAV attributes. This command helps you inspect all EAV attributes in your 
     *                Magento installation. Useful for debugging and development.
     *   Usage: eav:attribute:list [options]
     *   Parameters (as arguments array):
     *     - Named option "add-source": (optional, boolean) - Add source models to list
     *       Example: arguments: {"add-source": true}
     *     - Named option "add-backend": (optional, boolean) - Add backend type to list
     *       Example: arguments: {"add-backend": true}
     *     - Named option "filter-type": (optional, string) - Filter attributes by entity type
     *       Example: arguments: {"filter-type": "catalog_product"} or arguments: {"filter-type": "customer"}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - List all attributes: arguments: {}
     *     - Filter by entity type: arguments: {"filter-type": "catalog_product"}
     *     - With source models: arguments: {"add-source": true}
     *     - With backend type: arguments: {"add-backend": true}
     *     - JSON format: arguments: {"format": "json"}
     *     - Combined: arguments: {"filter-type": "catalog_product", "add-source": true, "format": "json"}
     * 
     * - eav:attribute:view
     *   Description: View the data for a particular attribute. Use this command to quickly inspect the 
     *                configuration and details of a specific EAV attribute. This is helpful for 
     *                troubleshooting and development.
     *   Usage: eav:attribute:view [--format[="..."]] <entityType> <attributeCode>
     *   Parameters (as arguments array):
     *     - Positional argument [0]: entityType (required) - Entity Type Code like "catalog_product", 
     *       "catalog_category", "customer"
     *       Example: arguments: [0 => "catalog_product"]
     *     - Positional argument [1]: attributeCode (required) - Attribute Code
     *       Example: arguments: [0 => "catalog_product", 1 => "name"]
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json", "0": "catalog_product", "1": "name"}
     *   Full examples:
     *     - View attribute: arguments: [0 => "catalog_product", 1 => "name"]
     *     - JSON format: arguments: {"format": "json", "0": "catalog_product", "1": "sku"}
     *     - Category attribute: arguments: [0 => "catalog_category", 1 => "name"]
     *   Notes: Useful for quickly inspecting the configuration and details of a specific EAV attribute.
     * 
     * - eav:attribute:remove
     *   Description: Remove attribute for a given attribute code. Use this command with caution. 
     *                Removing attributes is irreversible and may affect data integrity or break features 
     *                relying on the attribute.
     *   Usage: eav:attribute:remove <entityType> <attributeCode>...
     *   Parameters (as arguments array):
     *     - Positional argument [0]: entityType (required) - Entity Type Code, e.g. "catalog_product", 
     *       "catalog_category", "customer"
     *       Example: arguments: [0 => "catalog_product"]
     *     - Positional argument [1]: attributeCode (required) - Attribute Code (one or more). Can 
     *       specify multiple attributes
     *       Example: arguments: [0 => "catalog_product", 1 => "custom_attribute"]
     *       Multiple: arguments: [0 => "catalog_product", 1 => "attribute1", 2 => "attribute2"]
     *   Full examples:
     *     - Remove single attribute: arguments: [0 => "catalog_product", 1 => "custom_attribute"]
     *     - Remove multiple attributes: arguments: [0 => "catalog_product", 1 => "attr1", 2 => "attr2"]
     *   Notes: Warning: Use this command with caution. Removing attributes is irreversible and may 
     *          affect data integrity or break features relying on the attribute. Always backup before 
     *          removing attributes.
     * 
     * Generation Commands:
     * 
     * - generation:flush
     *   Description: Flushes generated code like factories and proxies. This command removes generated 
     *                files and forces Magento to regenerate them on the next request.
     *   Usage: generation:flush [<vendorName>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: vendorName (optional) - Vendor to remove specific folders like 
     *       "Magento". If omitted, flushes all generated code
     *       Example: arguments: [0 => "Magento"]
     *   Full examples:
     *     - Flush all generated code: arguments: {}
     *     - Flush specific vendor: arguments: [0 => "Magento"]
     *   Notes: Useful when you need to force regeneration of factories, proxies, and other generated 
     *          code. If vendor name is specified, only that vendor's generated folders will be removed.
     * 
     * System Commands:
     * 
     * - sys:check - No parameters needed
     * 
     * - sys:cron:list
     *   Description: Lists all cronjobs defined in crontab.xml files. This is useful for auditing 
     *                scheduled tasks and integrating with external tools.
     *   Usage: sys:cron:list [--format[="..."]]
     *   Parameters (as arguments array):
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - List all cronjobs: arguments: {}
     *     - JSON format: arguments: {"format": "json"}
     *     - CSV format: arguments: {"format": "csv"}
     *     - YAML format: arguments: {"format": "yaml"}
     *     - XML format: arguments: {"format": "xml"}
     *   Notes: Useful for auditing scheduled tasks and integrating with external tools.
     * 
     * - sys:cron:run
     *   Description: Runs a cronjob by code. If no job argument is passed you can select a job from 
     *                a list. If the schedule option is present, the cron is not launched, but just 
     *                scheduled immediately in Magento crontab.
     *   Usage: sys:cron:run [job]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: job (optional) - Job code to run. If omitted, you can select 
     *       a job from a list interactively
     *       Example: arguments: [0 => "sales_grid_order_async_insert"] or arguments: [0 => "indexer_reindex_all_invalid"]
     *     - Named option "schedule": (optional, boolean) - Schedule the cron instead of running it 
     *       immediately. The cron is not launched, but just scheduled immediately in Magento crontab
     *       Example: arguments: {"schedule": true, "0": "sales_grid_order_async_insert"}
     *   Full examples:
     *     - Run specific job: arguments: [0 => "sales_grid_order_async_insert"]
     *     - Interactive selection: arguments: {}
     *     - Schedule job: arguments: {"schedule": true, "0": "indexer_reindex_all_invalid"}
     *   Notes: If no job argument is passed, you can select a job from a list interactively. Use 
     *          --schedule option to schedule the cron instead of running it immediately.
     * 
     * - sys:cron:schedule
     *   Description: Schedule a cronjob for execution right now. This command schedules a Magento 
     *                cronjob (defined in crontab.xml) to be executed as soon as possible. It is useful 
     *                for testing or triggering specific jobs without waiting for the next scheduled run.
     *   Usage: sys:cron:schedule <job_code>
     *   Parameters (as arguments array):
     *     - Positional argument [0]: job_code (required) - The code of the cronjob to schedule for 
     *       immediate execution
     *       Example: arguments: [0 => "indexer_reindex_all_invalid"]
     *   Full examples:
     *     - Schedule job: arguments: [0 => "indexer_reindex_all_invalid"]
     *     - Schedule sales job: arguments: [0 => "sales_grid_order_async_insert"]
     *   Notes: Useful for testing or triggering specific jobs without waiting for the next scheduled 
     *          run. Related commands: sys:cron:list, sys:cron:run, sys:cron:kill, sys:cron:history.
     * 
     * - sys:store:list
     *   Description: List all store views. This command displays all stores, websites, and store views 
     *                in your Magento installation.
     *   Usage: sys:store:list
     *   Parameters (as arguments array):
     *     - No parameters needed
     *   Full example: arguments: {}
     *   Notes: Displays all stores, websites, and store views in your Magento installation. Useful 
     *          for auditing store configuration.
     * 
     * - sys:url:list - arguments: {"store": "default"} (store optional)
     * 
     * Route Commands:
     * 
     * - route:list
     *   Description: List Routes. Displays all registered routes in your Magento installation. You 
     *                can filter by area and module to focus on specific routes.
     *   Usage: route:list [-a|--area=AREA] [-m|--module=MODULE] [--format=FORMAT]
     *   Parameters (as arguments array):
     *     - Named option "area" or "a": (optional, string) - Route area code. One of: frontend, 
     *       adminhtml. If omitted, shows routes for all areas
     *       Example: arguments: {"area": "frontend"} or arguments: {"a": "adminhtml"}
     *     - Named option "module" or "m": (optional, string) - Show registered routes of a specific 
     *       module. If omitted, shows routes for all modules
     *       Example: arguments: {"module": "Magento_Catalog"} or arguments: {"m": "Magento_Sales"}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - List all routes: arguments: {}
     *     - Filter by area: arguments: {"area": "frontend"}
     *     - Filter by module: arguments: {"module": "Magento_Catalog"}
     *     - Filter by area and module: arguments: {"area": "frontend", "module": "Magento_Catalog"}
     *     - JSON format: arguments: {"format": "json"}
     *     - Combined: arguments: {"area": "adminhtml", "module": "Magento_Sales", "format": "json"}
     *   Notes: Useful for debugging and understanding URL routing in Magento. You can filter by 
     *          area (frontend/adminhtml) and module to focus on specific routes.
     * 
     * Index Commands:
     * 
     * - index:list
     *   Description: Lists all Magento indexes. This command helps you quickly see the status and 
     *                details of all Magento indexers in your installation.
     *   Usage: index:list [--format[=FORMAT]]
     *   Parameters (as arguments array):
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - List all indexes: arguments: {}
     *     - JSON format: arguments: {"format": "json"}
     *     - CSV format: arguments: {"format": "csv"}
     *   Notes: Useful for quickly seeing the status and details of all Magento indexers.
     * 
     * - index:trigger:recreate - arguments: {"index": "catalog_product_price"}
     * 
     * Integration Commands:
     * 
     * - integration:create
     *   Description: Create a new integration (WebAPI access token). Use this command to generate 
     *                access credentials for third-party applications or services that need to interact 
     *                with your Magento store via the WebAPI.
     *   Usage: integration:create [options] [--] <name> [<email> [<endpoint>]]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: name (required) - Name of the integration
     *       Example: arguments: [0 => "My Integration"]
     *     - Positional argument [1]: email (optional) - Email address
     *       Example: arguments: [0 => "My Integration", 1 => "integration@example.com"]
     *     - Positional argument [2]: endpoint (optional) - Endpoint URL
     *       Example: arguments: [0 => "My Integration", 1 => "email@example.com", 2 => "https://example.com/callback"]
     *     - Named option "consumer-key": (optional, string) - Consumer Key (length 32 chars). If not 
     *       provided, will be generated automatically
     *       Example: arguments: {"consumer-key": "32characterstringhere123456789"}
     *     - Named option "consumer-secret": (optional, string) - Consumer Secret (length 32 chars). 
     *       If not provided, will be generated automatically
     *       Example: arguments: {"consumer-secret": "32characterstringhere123456789"}
     *     - Named option "access-token": (optional, string) - Access-Token (length 32 chars). If not 
     *       provided, will be generated automatically
     *       Example: arguments: {"access-token": "32characterstringhere123456789"}
     *     - Named option "access-token-secret": (optional, string) - Access-Token Secret (length 32 
     *       chars). If not provided, will be generated automatically
     *       Example: arguments: {"access-token-secret": "32characterstringhere123456789"}
     *     - Named option "resource" or "r": (optional, string or array) - Defines a granted ACL 
     *       resource (multiple values allowed). This restricts which API resources the integration 
     *       can access
     *       Example: arguments: {"resource": "Magento_Catalog::products"} or arguments: {"r": ["Magento_Catalog::products", "Magento_Sales::sales"]}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - Basic integration: arguments: [0 => "My Integration"]
     *     - With email: arguments: [0 => "My Integration", 1 => "integration@example.com"]
     *     - With resources: arguments: {"resource": "Magento_Catalog::products", "0": "My Integration"}
     *     - Multiple resources: arguments: {"resource": ["Magento_Catalog::products", "Magento_Sales::sales"], "0": "My Integration"}
     *     - Custom credentials: arguments: {"consumer-key": "mykey", "consumer-secret": "mysecret", "0": "My Integration"}
     *     - JSON format: arguments: {"format": "json", "resource": "Magento_Catalog::products", "0": "My Integration"}
     *   Notes: Warning: If no ACL resource is defined, the new integration token will be created 
     *          with FULL ACCESS. To restrict access, provide a list of ACL resources using the 
     *          --resource option. All keys and secrets should be 32 characters long. If not provided, 
     *          they will be auto-generated.
     * 
     * - integration:list
     *   Description: List all existing integrations (WebAPI access tokens). This command is useful 
     *                for auditing which integrations currently have access to your Magento store.
     *   Usage: integration:list [--format[=FORMAT]]
     *   Parameters (as arguments array):
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - List all integrations: arguments: {}
     *     - JSON format: arguments: {"format": "json"}
     *     - CSV format: arguments: {"format": "csv"}
     *   Notes: Useful for auditing which integrations currently have access to your Magento store.
     * 
     * - integration:show
     *   Description: Show information about an existing integration. You can use this command to 
     *                retrieve all details about an integration, or specify a key to get a single value 
     *                (e.g., just the Access Token).
     *   Usage: integration:show [--format[=FORMAT]] <name_or_id> [<key>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: name_or_id (required) - Name or ID of the integration
     *       Example: arguments: [0 => "My Integration"] or arguments: [0 => "1"]
     *     - Positional argument [1]: key (optional) - Only output value of named param like "Access Token". 
     *       Key is case insensitive. If omitted, shows all details
     *       Example: arguments: [0 => "1", 1 => "Access Token"] or arguments: [0 => "My Integration", 1 => "Consumer Key"]
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json", "0": "1"}
     *   Full examples:
     *     - Show all details: arguments: [0 => "My Integration"]
     *     - Show by ID: arguments: [0 => "1"]
     *     - Show only Access Token: arguments: [0 => "1", 1 => "Access Token"]
     *     - Show only Consumer Key: arguments: [0 => "My Integration", 1 => "Consumer Key"]
     *     - JSON format: arguments: {"format": "json", "0": "1"}
     *     - Show specific key in JSON: arguments: {"format": "json", "0": "1", "1": "Access Token"}
     *   Notes: You can retrieve all details or specify a key to get a single value. Key is case 
     *          insensitive. Example: integration:show 1 "Access Key" will output only the access key.
     * 
     * Module Commands:
     * - module:enable - arguments: {"module": "Magento_Catalog"} (modules as comma-separated)
     * - module:disable - arguments: {"module": "Magento_Catalog"}
     * - module:status - No parameters needed
     * 
     * Development Commands:
     * 
     * - dev:theme:build-hyva
     *   Description: Build Hyva Theme CSS. This command builds the CSS for a Hyvä theme. Use the 
     *                --production option for minified output suitable for live environments.
     *   Usage: dev:theme:build-hyva [--production] [<theme-name>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: theme-name (optional) - Hyva Theme to build (e.g. "Hyva/default").
     *       Not required when using --all option
     *       Example: arguments: [0 => "Hyva/default"]
     *     - Named option "production": (optional, boolean) - Build CSS for production (minified output)
     *       Example: arguments: {"production": true, "0": "Hyva/default"}
     *     - Named option "all": (optional, boolean) - Build CSS for all Hyva themes in your Magento 
     *       installation. No theme argument is required when using this option
     *       Example: arguments: {"all": true}
     *     - Named option "suppress-no-theme-found-error": (optional, boolean) - Suppress error if no 
     *       Hyva theme was found when using --all. The command will exit successfully instead of 
     *       returning an error
     *       Example: arguments: {"all": true, "suppress-no-theme-found-error": true}
     *     - Named option "force-npm-install": (optional, boolean) - Always run npm install before 
     *       building, even if node_modules exists. Useful if you want to ensure all dependencies are 
     *       up to date or if you encounter build issues related to missing or outdated node modules
     *       Example: arguments: {"force-npm-install": true, "0": "Hyva/default"}
     *   Full examples:
     *     - Basic build: arguments: [0 => "Hyva/default"]
     *     - Production build: arguments: {"production": true, "0": "Hyva/default"}
     *     - Build all themes: arguments: {"all": true}
     *     - Production build all: arguments: {"all": true, "production": true}
     *     - Force npm install: arguments: {"force-npm-install": true, "0": "Hyva/default"}
     *   Notes: The npm install process has a timeout of 1 hour (3600 seconds). The build process 
     *          (npm run watch or npm run build-prod) has no timeout in watch mode, so it will run 
     *          until you stop it (Ctrl+C). In production mode, it will run until the build completes.
     * 
     * - dev:symlinks
     *   Description: Toggle allow symlinks setting. This command toggles the "allow symlinks" setting 
     *                in Magento, which can be useful for development environments where symlinks are 
     *                needed for static content or modules.
     *   Usage: dev:symlinks [options] [--] [<store>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: store (optional) - Store code or ID
     *       Example: arguments: [0 => "default"] or arguments: [0 => "1"]
     *     - Named option "on": (optional, boolean) - Switch on
     *       Example: arguments: {"on": true, "0": "default"}
     *     - Named option "off": (optional, boolean) - Switch off
     *       Example: arguments: {"off": true, "0": "default"}
     *     - Named option "global": (optional, boolean) - Set value on default scope
     *       Example: arguments: {"global": true}
     *   Full examples:
     *     - Toggle: arguments: [0 => "default"]
     *     - Turn on: arguments: {"on": true, "0": "default"}
     *     - Turn off: arguments: {"off": true, "0": "default"}
     *     - Global setting: arguments: {"global": true, "on": true}
     * 
     * - dev:asset:clear
     *   Description: Clear static view files. This command removes all static view files generated by 
     *                Magento. Use it to force regeneration of static assets after code or theme changes.
     *   Usage: dev:asset:clear [--theme="..."]
     *   Parameters (as arguments array):
     *     - Named option "theme" or "t": (optional, string or array) - Clear assets for specific 
     *       theme(s) only. Multiple values allowed. If omitted, clears assets for all themes
     *       Example: arguments: {"theme": "Magento/luma"} or arguments: {"t": "Magento/luma"}
     *   Full examples:
     *     - Clear all themes: arguments: {}
     *     - Clear specific theme: arguments: {"theme": "Magento/luma"}
     *     - Clear multiple themes: arguments: {"theme": ["Magento/luma", "Magento/blank"]}
     *   Notes: To clear assets for all themes, omit the --theme option. To clear assets for specific 
     *          theme(s) only, use the --theme option with theme name(s).
     * 
     * - dev:di:preferences:list
     *   Description: List DI Preferences. This command helps you inspect Magento's dependency injection 
     *                (DI) preferences for a given area. Useful for debugging and understanding class 
     *                rewrites and dependency mappings.
     *   Usage: dev:di:preferences:list [--format [FORMAT]] [<area>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: area (optional) - Area code. One of: global, adminhtml, frontend, 
     *       crontab, webapi_rest, webapi_soap, graphql, doc, admin. If omitted, lists for all areas
     *       Example: arguments: [0 => "frontend"] or arguments: [0 => "adminhtml"]
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, xml, yaml
     *       Example: arguments: {"format": "json", "0": "frontend"}
     *   Full examples:
     *     - List all areas: arguments: {}
     *     - List specific area: arguments: [0 => "frontend"]
     *     - JSON format: arguments: {"format": "json", "0": "adminhtml"}
     *     - CSV format: arguments: {"format": "csv"}
     * 
     * - dev:log:size
     *   Description: Get size of log files in var/log directory. This command displays the size of all 
     *                log files in the var/log directory. Magento 2 typically generates multiple log files 
     *                including system.log, debug.log, exception.log, and others depending on your 
     *                configuration and installed modules.
     *   Usage: dev:log:size [options]
     *   Parameters (as arguments array):
     *     - Named option "human-readable" or "H": (optional, boolean) - Show file sizes in a human 
     *       readable format (e.g., KB, MB)
     *       Example: arguments: {"human-readable": true} or arguments: {"H": true}
     *     - Named option "sort-by-size" or "s": (optional, boolean) - Sort files by size, showing 
     *       largest files first
     *       Example: arguments: {"sort-by-size": true} or arguments: {"s": true}
     *     - Named option "filter" or "f": (optional, string) - Filter log files by name pattern. Only 
     *       files containing the specified pattern will be displayed
     *       Example: arguments: {"filter": "system"} or arguments: {"f": "exception"}
     *     - Named option "format": (optional, string) - Output format for the table. Supported: csv, 
     *       json, markdown, table, xml, and more
     *       Example: arguments: {"format": "csv"} or arguments: {"format": "json"}
     *   Full examples:
     *     - Basic usage: arguments: {}
     *     - Human-readable sizes: arguments: {"human-readable": true}
     *     - Sort by size: arguments: {"sort-by-size": true, "human-readable": true}
     *     - Filter specific logs: arguments: {"filter": "system"}
     *     - Filter and sort: arguments: {"filter": "exception", "sort-by-size": true, "human-readable": true}
     *     - CSV format: arguments: {"format": "csv"}
     *   Notes: Output displays Log File, Size, and Last Modified columns. At the end, shows a summary 
     *          with total number of files and total size. Useful for log management, debugging, disk 
     *          space monitoring, and maintenance.
     * 
     * - dev:module:list
     *   Description: Lists all installed modules. You can filter by vendor, enabled/disabled state, and 
     *                output format. Useful for auditing and debugging module status. If --vendor option 
     *                is set, only modules of the given vendor are listed. If --only-enabled option is set, 
     *                only enabled modules are listed. If --only-disabled option is set, only disabled 
     *                modules are listed.
     *   Usage: dev:module:list [options]
     *   Parameters (as arguments array):
     *     - Named option "vendor": (optional, string) - Show modules of a specific vendor (case 
     *       insensitive)
     *       Example: arguments: {"vendor": "Magento"} or arguments: {"vendor": "Agento"}
     *     - Named option "only-enabled" or "e": (optional, boolean) - Show only enabled modules
     *       Example: arguments: {"only-enabled": true} or arguments: {"e": true}
     *     - Named option "only-disabled" or "d": (optional, boolean) - Show only disabled modules
     *       Example: arguments: {"only-disabled": true} or arguments: {"d": true}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - List all modules: arguments: {}
     *     - Only enabled: arguments: {"only-enabled": true}
     *     - Only disabled: arguments: {"only-disabled": true}
     *     - By vendor: arguments: {"vendor": "Magento"}
     *     - JSON format: arguments: {"format": "json"}
     *     - Combined: arguments: {"vendor": "Magento", "only-enabled": true, "format": "json"}
     * 
     * - dev:module:observer:list
     *   Description: List Observers. This command lists all event observers registered in your Magento 
     *                installation. You can filter by event name or area to focus on specific observers.
     *   Usage: dev:module:observer:list [--sort] [--format=FORMAT] [<event> [<area>]]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: event (optional) - Filter observers for specific event
     *       Example: arguments: [0 => "catalog_product_save_after"]
     *     - Positional argument [1]: area (optional) - Filter observers in specific area. One of: 
     *       global, adminhtml, frontend, crontab, webapi_rest, webapi_soap, graphql, doc, admin
     *       Example: arguments: [0 => "catalog_product_save_after", 1 => "frontend"]
     *     - Named option "sort": (optional, boolean) - Sort output ascending by event name
     *       Example: arguments: {"sort": true}
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - List all observers: arguments: {}
     *     - Filter by event: arguments: [0 => "catalog_product_save_after"]
     *     - Filter by event and area: arguments: [0 => "catalog_product_save_after", 1 => "frontend"]
     *     - Sort output: arguments: {"sort": true}
     *     - JSON format: arguments: {"format": "json"}
     *     - Combined: arguments: {"sort": true, "format": "json", "0": "catalog_product_save_after"}
     * 
     * - dev:template-hints-blocks
     *   Description: Toggle template hints for blocks in the frontend. This command toggles template 
     *                hints for blocks in the frontend, which is useful for debugging and theme development.
     *   Usage: dev:template-hints-blocks [options] [--] [<store>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: store (optional) - Store code or ID
     *       Example: arguments: [0 => "default"] or arguments: [0 => "1"]
     *   Full examples:
     *     - Toggle for store: arguments: [0 => "default"]
     *   Notes: Template hints help you identify which template files are used for each block in the 
     *          frontend. Useful for debugging and theme development.
     * 
     * - dev:template-hints
     *   Description: Toggle template hints. This command toggles template hints in the frontend, which 
     *                is useful for debugging and theme development. Template hints help you identify 
     *                which template files are used for each block.
     *   Usage: dev:template-hints [options] [--] [<store>]
     *   Parameters (as arguments array):
     *     - Positional argument [0]: store (optional) - Store code or ID
     *       Example: arguments: [0 => "default"] or arguments: [0 => "1"]
     *     - Named option "on": (optional, boolean) - Switch on
     *       Example: arguments: {"on": true, "0": "default"}
     *     - Named option "off": (optional, boolean) - Switch off
     *       Example: arguments: {"off": true, "0": "default"}
     *   Full examples:
     *     - Toggle: arguments: [0 => "default"]
     *     - Turn on: arguments: {"on": true, "0": "default"}
     *     - Turn off: arguments: {"off": true, "0": "default"}
     *   Notes: Template hints help you identify which template files are used for each block. Useful 
     *          for debugging and theme development.
     * 
     * - dev:theme:list
     *   Description: Lists all available Magento themes. You can change the output format for easier 
     *                integration with other tools or scripts.
     *   Usage: dev:theme:list [--format[=FORMAT]]
     *   Parameters (as arguments array):
     *     - Named option "format": (optional, string) - Output format. One of: csv, json, json_array, 
     *       yaml, xml
     *       Example: arguments: {"format": "json"}
     *   Full examples:
     *     - List all themes: arguments: {}
     *     - JSON format: arguments: {"format": "json"}
     *     - CSV format: arguments: {"format": "csv"}
     *     - XML format: arguments: {"format": "xml"}
     * 
     * See https://github.com/netz98/n98-magerun2 for full command documentation with all parameters.
     * 
     * @param string $command Full magerun command path (e.g., "db:query", "cache:clean", "admin:user:list")
     * @param string|null $username Username (auto-mapped to positional argument for admin commands)
     * @param string|null $email Email address (alternative to username)
     * @param string|null $user User identifier (alternative to username/email)
     * @param string|null $query SQL query (for db:query command)
     * @param string|null $path File/directory path
     * @param string|null $store Store code
     * @param string|null $website Website code
     * @param bool $activate Activate flag
     * @param bool $deactivate Deactivate flag
     * @param bool $force Force flag
     * @param string|null $type Type parameter (e.g., cache type)
     * @param string|null $format Output format (json, csv, xml, yaml)
     * @param string|null $sort Sort field
     * @param string|null $columns Columns to display
     * @param string|null $password Password
     * @param string|null $arg0 Generic positional argument (fallback)
     * @param string|null $arg1 Second positional argument
     * @param string|null $arg2 Third positional argument
     * @return TextContent
     * 
     * Why named parameters instead of "0", "1", "2"?
     * PHP cannot have numeric parameter names, and the php-mcp/server library maps JSON-RPC 
     * arguments to method parameters by name. Numeric keys like "0", "1" cannot be mapped.
     * Solution: Use descriptive names (username, email, query) that auto-map to positional args.
     */
    #[McpTool(name: 'magerun')]
    public function executeMagerun(
        string $command = '',
        // Descriptive parameters for common use cases (auto-mapped to positional args)
        ?string $username = null,
        ?string $email = null,
        ?string $user = null,
        ?string $query = null,
        ?string $path = null,
        ?string $store = null,
        ?string $website = null,
        // Boolean flags
        bool $activate = false,
        bool $deactivate = false,
        bool $force = false,
        // Named options
        ?string $type = null,
        ?string $format = null,
        ?string $sort = null,
        ?string $columns = null,
        ?string $password = null,
        // Generic fallback for other positional arguments
        ?string $arg0 = null,
        ?string $arg1 = null,
        ?string $arg2 = null
    ): TextContent {
        // Build params array from individual parameters
        $params = [];
        
        // Smart mapping: Use descriptive parameters as positional arguments
        // Priority: username > email > user > query > path > arg0
        $positionalArg = $username ?? $email ?? $user ?? $query ?? $path ?? $arg0;
        
        if ($positionalArg !== null) {
            $params[0] = $positionalArg;
        }
        if ($arg1 !== null) {
            $params[1] = $arg1;
        }
        if ($arg2 !== null) {
            $params[2] = $arg2;
        }
        
        // Add boolean flags
        if ($activate) $params['activate'] = $activate;
        if ($deactivate) $params['deactivate'] = $deactivate;
        if ($force) $params['force'] = $force;
        
        // Add named options
        if ($type !== null) $params['type'] = $type;
        if ($format !== null) $params['format'] = $format;
        if ($sort !== null) $params['sort'] = $sort;
        if ($columns !== null) $params['columns'] = $columns;
        if ($store !== null) $params['store'] = $store;
        if ($website !== null) $params['website'] = $website;
        if ($password !== null) $params['password'] = $password;
        
        // Start output buffering to catch any PHP errors/warnings
        ob_start();
        $originalErrorReporting = error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
        
        try {
            // Validate command
            if (empty($command)) {
                ob_end_clean();
                error_reporting($originalErrorReporting);
                $errorMsg = 'Error: Command is required';
                $this->getLogger()->logError('', $errorMsg, '');
                return new TextContent($errorMsg);
            }
            
            // IMPORTANT LIMITATION: The php-mcp/server library extracts named parameters (like 'command') 
            // from the JSON-RPC arguments object and maps them to method parameters by name.
            // When it does this, it filters out numeric keys (like "0", "1") that don't match parameter names.
            // This means positional arguments may be lost. The library passes the remaining arguments
            // to the $arguments parameter, but numeric keys are filtered out during parameter extraction.
            // 
            // WORKAROUND: Use named arguments where possible, or use the execute_sql tool for operations
            // that require positional arguments. For example, instead of:
            //   {"command": "admin:user:activate", "0": "admin"}
            // Use SQL: UPDATE admin_user SET is_active = 1 WHERE username = 'admin'

            $magentoRoot = $this->getMagentoRoot();
            $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';

            if (!file_exists($magerunPath)) {
                ob_end_clean();
                error_reporting($originalErrorReporting);
                $errorMsg = 'Error: n98-magerun2.phar not found at ' . $magerunPath . 
                    '. Run: php bin/magento agento:magerun:install';
                $this->getLogger()->logError($command, $errorMsg, '');
                return new TextContent($errorMsg);
            }


            // Build command
            $cmd = ['php', $magerunPath, $command];

            // Add magerun command arguments (e.g., --format=json)
            // Handle the case where params might be an associative array with numeric string keys
            // or a sequential array (if numeric keys were lost during parameter extraction)
            if (!empty($params)) {
                
                // Check if params is a sequential array (numeric keys starting from 0)
                // This happens when the MCP framework converts numeric keys to sequential indices
                $isSequentialArray = array_keys($params) === range(0, count($params) - 1);
                
                // Sort arguments to ensure positional arguments (numeric keys) come first
                // This is important for magerun commands that require positional arguments
                $positionalArgs = [];
                $namedArgs = [];
                
                foreach ($params as $key => $value) {
                    // Handle sequential arrays (when numeric keys were converted to 0,1,2...)
                    if ($isSequentialArray) {
                        // All values in a sequential array are positional arguments
                        $positionalArgs[(int)$key] = (string)$value;
                    } elseif (is_int($key) || (is_string($key) && ctype_digit($key))) {
                        // Handle both string numeric keys ("0", "1") and integer keys (0, 1)
                        // JSON decodes numeric string keys as integers, but we need to handle both cases
                        $positionalArgs[(int)$key] = (string)$value;
                    } else {
                        $namedArgs[$key] = $value;
                    }
                }
                
                // Add positional arguments in order (0, 1, 2, etc.)
                ksort($positionalArgs);
                foreach ($positionalArgs as $value) {
                    $cmd[] = $value;
                }
                
                // Add named arguments
                foreach ($namedArgs as $key => $value) {
                    if (is_bool($value)) {
                        // Boolean flag
                        if ($value) {
                            $cmd[] = '--' . $key;
                        }
                    } elseif (is_string($value) || is_numeric($value)) {
                        // Key-value argument
                        $cmd[] = '--' . $key . '=' . $value;
                    }
                }
                
            }

            // Execute magerun command
            $process = new Process($cmd, $magentoRoot);
            $process->setTimeout(300); // 5 minutes
            $process->run();

            // Clean any captured output (should be empty, but just in case)
            $bufferedOutput = ob_get_clean();
            error_reporting($originalErrorReporting);

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            if ($process->getExitCode() !== 0) {
                // Clean error message without stack traces
                $errorMessage = 'Magerun command failed (exit code: ' . $process->getExitCode() . ')';
                $fullErrorDetails = "Command: " . implode(' ', $cmd) . "\n";
                $fullErrorDetails .= "Exit Code: " . $process->getExitCode() . "\n";
                
                if (!empty($errorOutput)) {
                    $fullErrorDetails .= "Error Output: " . $errorOutput . "\n";
                    // Remove PHP stack traces from error output (lines starting with #number)
                    $cleanError = preg_replace('/^\s*#\d+\s+.*$/m', '', $errorOutput);
                    $cleanError = preg_replace('/Stack trace:.*$/s', '', $cleanError);
                    $cleanError = trim($cleanError);
                    if (!empty($cleanError)) {
                        $errorMessage .= "\n" . $cleanError;
                    }
                }
                // Use output if error output is empty
                if (empty($errorOutput) && !empty($output)) {
                    $fullErrorDetails .= "Output: " . $output . "\n";
                    $errorMessage .= "\n" . trim($output);
                }
                
                // Log error to file
                $this->getLogger()->logError($command, $fullErrorDetails, $errorOutput ?: $output);
                
                return new TextContent($errorMessage);
            }

            return new TextContent($output ?: 'Command executed successfully (no output)');
        } catch (\Throwable $e) {
            // Clean output buffer
            ob_end_clean();
            error_reporting($originalErrorReporting);
            
            // Log exception details
            $errorDetails = "Exception: " . get_class($e) . "\n";
            $errorDetails .= "Message: " . $e->getMessage() . "\n";
            $errorDetails .= "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            $errorDetails .= "Trace: " . $e->getTraceAsString() . "\n";
            $this->getLogger()->logError($command ?? '', $errorDetails, '');
            
            // Return clean error message without stack trace
            return new TextContent('Error: ' . $e->getMessage());
        }
    }

    private function getMagentoRoot(): string
    {
        $currentDir = __DIR__;
        
        // Method 1: Try app/code path (standard Magento installation)
        // Go up from app/code/Agento/Core/Mcp/Tools to magento root
        // __DIR__ = app/code/Agento/Core/Mcp/Tools
        // Need to go up 6 levels: Tools -> Mcp -> Core -> Agento -> code -> app -> root
        $appCodePath = dirname(dirname(dirname(dirname(dirname(dirname($currentDir))))));
        if ($this->isMagentoRoot($appCodePath)) {
            return $appCodePath;
        }
        
        // Method 2: Check if we're in a vendor directory (Composer installation)
        // vendor/Agento/Core/Mcp/Tools -> vendor -> magento root
        if (strpos($currentDir, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
            $pathParts = explode(DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR, $currentDir);
            if (count($pathParts) >= 2) {
                $vendorPath = dirname($pathParts[0] . DIRECTORY_SEPARATOR . 'vendor');
                if ($this->isMagentoRoot($vendorPath)) {
                    return $vendorPath;
                }
            }
        }
        
        // Method 3: Search upwards for Magento root markers
        $current = $currentDir;
        for ($i = 0; $i < 15; $i++) {
            if ($this->isMagentoRoot($current)) {
                return $current;
            }
            
            $parent = dirname($current);
            // Stop if we've reached the filesystem root
            if ($parent === $current) {
                break;
            }
            $current = $parent;
        }
        
        // Method 4: Fallback to original method if nothing found
        return $appCodePath;
    }
    
    /**
     * Check if given path is a Magento root directory
     *
     * @param string $path
     * @return bool
     */
    private function isMagentoRoot(string $path): bool
    {
        // Check for key Magento files/directories
        $markers = [
            $path . '/bin/magento',
            $path . '/app/etc/env.php',
            $path . '/app/etc/di.xml',
            $path . '/pub/index.php'
        ];
        
        // At least 2 markers should exist to be confident it's Magento root
        $foundCount = 0;
        foreach ($markers as $marker) {
            if (file_exists($marker)) {
                $foundCount++;
                if ($foundCount >= 2) {
                    return true;
                }
            }
        }
        
        return false;
    }
}

