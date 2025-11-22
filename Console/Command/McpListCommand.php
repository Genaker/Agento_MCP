<?php
/**
 * Agento Core Module
 * List Available MCP Tools Command
 */

namespace Agento\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class McpListCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('agento:mcp:list')
            ->setDescription('List all available MCP tools in Agento MCP server');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Agento MCP Server - Available Tools');
        
        $tools = $this->getTools();
        
        $io->section('Core Agento Tools');
        
        $coreTools = array_filter($tools, function($tool) {
            return in_array($tool['name'], ['execute_sql', 'clear_cache']);
        });
        
        foreach ($coreTools as $tool) {
            $io->text("<fg=cyan>{$tool['name']}</>");
            $io->text("  {$tool['description']}");
            $io->text("");
        }
        
        $io->section('n98-magerun2 Integration Tools');
        
        $magerunTools = array_filter($tools, function($tool) {
            return strpos($tool['name'], 'magerun') === 0;
        });
        
        foreach ($magerunTools as $tool) {
            $io->text("<fg=cyan>{$tool['name']}</>");
            $io->text("  {$tool['description']}");
            
            // Show available commands if it's a category tool
            if (isset($tool['inputSchema']['properties']['command']['enum'])) {
                $commands = implode(', ', $tool['inputSchema']['properties']['command']['enum']);
                $io->text("  <fg=yellow>Commands:</> {$commands}");
            }
            $io->text("");
        }
        
        $io->section('Summary');
        $io->table(
            ['Category', 'Tool Count'],
            [
                ['Core Agento Tools', count($coreTools)],
                ['Magerun Integration', count($magerunTools)],
                ['<fg=cyan>Total Tools</>', '<fg=cyan>' . count($tools) . '</>']
            ]
        );
        
        $io->note('Use these tools in Cursor by asking the AI assistant to use them.');
        
        return Command::SUCCESS;
    }

    /**
     * Get list of available tools (same as McpCommand)
     *
     * @return array
     */
    private function getTools(): array
    {
        return [
            [
                'name' => 'execute_sql',
                'description' => 'Execute SQL query against Magento database. Use this to query product data, orders, customers, configuration, or any other database tables. Returns results in table format by default, or JSON/CSV if specified.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'SQL query to execute (e.g., "SELECT * FROM catalog_product_entity LIMIT 10", "SELECT COUNT(*) FROM sales_order")'
                        ],
                        'connection' => [
                            'type' => 'string',
                            'description' => 'Database connection name (default: "default", alternative: "indexer")',
                            'default' => 'default'
                        ],
                        'format' => [
                            'type' => 'string',
                            'description' => 'Output format: "table" (default), "json", or "csv"',
                            'enum' => ['table', 'json', 'csv'],
                            'default' => 'table'
                        ]
                    ],
                    'required' => ['query']
                ]
            ],
            [
                'name' => 'clear_cache',
                'description' => 'Clear Magento cache. Use this after making configuration changes, code updates, or when experiencing caching issues. Common cache types: config, layout, block_html, full_page, eav, collections. If no type specified, clears all cache types.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'description' => 'Cache type to clear (optional, clears all if not specified). Common types: config, layout, block_html, full_page, eav, collections, reflection, db_ddl, compiled_config, translate, config_integration, config_webservice'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'magerun',
                'description' => 'Execute any n98-magerun2 command - the Swiss army knife for Magento. Provides 100+ commands for database operations, cache management, admin users, system info, configuration, customers, indexing, modules, and setup. Use this for any magerun command not covered by specific category tools. See https://github.com/netz98/n98-magerun2 for full command list.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'Full magerun command path (e.g., "db:query", "cache:clean", "sys:info", "admin:user:list", "config:store:get", "customer:create", "indexer:reindex", "module:enable", "setup:upgrade")'
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command arguments as key-value pairs (e.g., {"query": "SELECT * FROM admin_user"} for db:query, {"path": "web/unsecure/base_url"} for config:store:get)',
                            'additionalProperties' => true
                        ],
                        'options' => [
                            'type' => 'object',
                            'description' => 'Command options as key-value pairs (e.g., {"type": "config"} for cache:clean, {"format": "json"} for db:query)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ],
            [
                'name' => 'magerun_db',
                'description' => 'Database operations for Magento. Use query to run SQL, dump to backup database, import to restore, info for connection details, console for MySQL CLI access, create/drop for database management, status for replication status, variables for MySQL variables.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'DB command: query (run SQL), dump (backup), import (restore), info (connection info), console (MySQL CLI), create (new DB), drop (delete DB), status (replication), variables (MySQL vars)',
                            'enum' => ['query', 'dump', 'import', 'info', 'console', 'create', 'drop', 'status', 'variables']
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command-specific arguments (e.g., {"query": "SELECT * FROM admin_user"} for query, {"compression": "gzip"} for dump)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ],
            [
                'name' => 'magerun_cache',
                'description' => 'Magento cache operations via magerun. Use clean to clear specific cache types, flush to clear all cache storage, enable/disable to toggle cache types, list to see all cache types, status to check cache state, view to inspect cache entries, report for cache statistics.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'Cache command: clean (clear specific types), flush (clear all storage), enable (turn on), disable (turn off), list (show all types), status (check state), view (inspect entry), report (statistics)',
                            'enum' => ['clean', 'flush', 'enable', 'disable', 'list', 'status', 'view', 'report']
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command-specific arguments (e.g., {"type": "config"} for clean, {"id": "cache_id"} for view)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ],
            [
                'name' => 'magerun_admin',
                'description' => 'Admin user management for Magento backend. Use user:list to see all admin users, user:create to add new admin, user:change-password to reset password, user:delete to remove admin, user:activate/deactivate to enable/disable, user:unlock to unlock locked accounts, token:create for API tokens.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'Admin command: user:list (show all), user:create (add new), user:change-password (reset), user:delete (remove), user:activate (enable), user:deactivate (disable), user:unlock (unlock), token:create (API token)',
                            'enum' => ['user:list', 'user:create', 'user:change-password', 'user:delete', 'user:activate', 'user:deactivate', 'user:unlock', 'token:create']
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command-specific arguments (e.g., {"username": "admin", "email": "admin@example.com", "password": "password123"} for user:create, {"username": "admin", "password": "newpass"} for change-password)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ],
            [
                'name' => 'magerun_sys',
                'description' => 'System information and management. Use info to get Magento version and system details, check for system health, store:list/website:list to see stores/websites, url:list to view URLs, cron:list to see cron jobs, cron:run to execute cron, maintenance to enable/disable maintenance mode.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'System command: info (Magento details), check (health check), store:list (all stores), website:list (all websites), url:list (base URLs), cron:list (cron jobs), cron:run (execute cron), maintenance (maintenance mode)',
                            'enum' => ['info', 'check', 'store:list', 'website:list', 'url:list', 'cron:list', 'cron:run', 'maintenance']
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command-specific arguments (e.g., {"job": "catalog_product_price"} for cron:run)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ],
            [
                'name' => 'magerun_config',
                'description' => 'Magento configuration management. Use store:get to read config values, store:set to update config, env:set to change environment variables, search to find config paths, show to display config, show:urls to see base URLs. Config paths use dot notation like "web/unsecure/base_url" or "catalog/frontend/list_per_page".',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'Config command: store:get (read value), store:set (update value), env:set (env variable), search (find path), show (display config), show:urls (base URLs), show:default-url (default URL), show:store-url (store URL)',
                            'enum' => ['store:get', 'store:set', 'env:set', 'search', 'show', 'show:urls', 'show:default-url', 'show:store-url']
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command-specific arguments (e.g., {"path": "web/unsecure/base_url"} for store:get, {"path": "web/unsecure/base_url", "value": "https://example.com"} for store:set, {"scope": "stores", "scope-code": "default"} for store:get)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ],
            [
                'name' => 'magerun_customer',
                'description' => 'Customer account management. Use list to see all customers, create to add new customer, info to view customer details, delete to remove customer, change-password to reset password, add-address to add shipping/billing address, token:create for customer API tokens.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'Customer command: list (all customers), create (new customer), info (customer details), delete (remove customer), change-password (reset password), add-address (add address), token:create (API token)',
                            'enum' => ['list', 'create', 'info', 'delete', 'change-password', 'add-address', 'token:create']
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command-specific arguments (e.g., {"email": "customer@example.com", "firstname": "John", "lastname": "Doe", "password": "password123"} for create, {"email": "customer@example.com"} for info/delete)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ],
            [
                'name' => 'magerun_indexer',
                'description' => 'Magento indexer management. Use reindex to rebuild indexes (product prices, categories, search, etc.), reset to clear index data, status to check index state, set-mode to change index mode (realtime/schedule/manual), show-mode to display current mode, info for index details, set-status to change index status.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'Indexer command: reindex (rebuild indexes), reset (clear index data), status (check state), set-mode (change mode: realtime/schedule/manual), show-mode (display mode), info (index details), set-status (change status)',
                            'enum' => ['reindex', 'reset', 'status', 'set-mode', 'show-mode', 'info', 'set-status']
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command-specific arguments (e.g., {"index": "catalog_product_price"} for reindex/reset, {"index": "catalog_product_price", "mode": "realtime"} for set-mode)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ],
            [
                'name' => 'magerun_module',
                'description' => 'Magento module management. Use enable to activate a module, disable to deactivate, status to check if module is enabled/disabled, uninstall to remove module. Module names use format like "Magento_Catalog", "Magento_Sales", or custom modules like "Vendor_ModuleName".',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'Module command: enable (activate module), disable (deactivate module), status (check enabled/disabled), uninstall (remove module)',
                            'enum' => ['enable', 'disable', 'status', 'uninstall']
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command-specific arguments (e.g., {"module": "Magento_Catalog"} for enable/disable/status, {"module": "Magento_Catalog", "clear-static-content": true} for uninstall)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ],
            [
                'name' => 'magerun_setup',
                'description' => 'Magento setup and maintenance operations. Use upgrade to run database migrations and update schema, install for fresh installation, db:status to check database schema status, di:compile to compile dependency injection, static-content:deploy to deploy static files, backup to create backup, rollback to restore backup, uninstall to remove Magento.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'Setup command: upgrade (run migrations), install (fresh install), db:status (schema status), di:compile (compile DI), static-content:deploy (deploy static files), backup (create backup), rollback (restore backup), uninstall (remove Magento)',
                            'enum' => ['upgrade', 'install', 'db:status', 'di:compile', 'static-content:deploy', 'backup', 'rollback', 'uninstall']
                        ],
                        'arguments' => [
                            'type' => 'object',
                            'description' => 'Command-specific arguments (e.g., {"code": "en_US", "area": "frontend", "theme": "Magento/luma"} for static-content:deploy, {"code": "en_US", "area": "adminhtml"} for admin static files)',
                            'additionalProperties' => true
                        ]
                    ],
                    'required' => ['command']
                ]
            ]
        ];
    }
}








