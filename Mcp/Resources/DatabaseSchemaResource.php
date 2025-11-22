<?php
/**
 * Agento Core Module
 * MCP Resource: Database Table Schemas
 */

namespace Agento\Core\Mcp\Resources;

use Magento\Framework\App\ResourceConnection;
use PhpMcp\Server\Attributes\McpResourceTemplate;
use PhpMcp\Server\JsonRpc\Contents\TextContent;

class DatabaseSchemaResource
{
    public function __construct(
        private ResourceConnection $resourceConnection
    ) {}

    /**
     * Get database table schema information.
     * 
     * @param string $table Table name (e.g., "catalog_product_entity", "admin_user", "sales_order")
     * @return TextContent
     */
    #[McpResourceTemplate(
        uriTemplate: 'db://schema/{table}',
        name: 'database_schema',
        description: 'Get database table schema information including column names, types, and constraints. Use db://schema/catalog_product_entity to see product table structure, db://schema/admin_user for admin users table, or any other Magento table name.',
        mimeType: 'application/json'
    )]
    public function getTableSchema(string $table): TextContent
    {
        $connection = $this->resourceConnection->getConnection();
        
        // Get table name with prefix
        $tableName = $this->resourceConnection->getTableName($table);
        
        try {
            // Get column information
            $columns = $connection->describeTable($tableName);
            
            // Format schema information
            $schema = [
                'table' => $table,
                'full_table_name' => $tableName,
                'columns' => []
            ];
            
            foreach ($columns as $columnName => $columnInfo) {
                $schema['columns'][] = [
                    'name' => $columnName,
                    'type' => $columnInfo['DATA_TYPE'] ?? 'unknown',
                    'nullable' => ($columnInfo['NULLABLE'] ?? false) ? true : false,
                    'default' => $columnInfo['DEFAULT'] ?? null,
                    'primary' => ($columnInfo['PRIMARY'] ?? false) ? true : false,
                    'unsigned' => ($columnInfo['UNSIGNED'] ?? false) ? true : false,
                    'length' => $columnInfo['LENGTH'] ?? null,
                    'scale' => $columnInfo['SCALE'] ?? null,
                    'precision' => $columnInfo['PRECISION'] ?? null,
                ];
            }
            
            // Get index information
            try {
                $indexes = $connection->getIndexList($tableName);
                $schema['indexes'] = [];
                foreach ($indexes as $indexName => $indexInfo) {
                    $schema['indexes'][] = [
                        'name' => $indexName,
                        'type' => $indexInfo['INDEX_TYPE'] ?? 'unknown',
                        'fields' => $indexInfo['COLUMNS'] ?? []
                    ];
                }
            } catch (\Exception $e) {
                // Index information might not be available, skip it
                $schema['indexes'] = [];
            }
            
            return new TextContent(json_encode($schema, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to get schema for table '{$table}': " . $e->getMessage());
        }
    }
}



