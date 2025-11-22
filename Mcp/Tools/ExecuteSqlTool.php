<?php
/**
 * Agento Core Module
 * MCP Tool: Execute SQL Query
 */

namespace Agento\Core\Mcp\Tools;

use Magento\Framework\App\ResourceConnection;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\JsonRpc\Contents\TextContent;

class ExecuteSqlTool
{
    public function __construct(
        private ResourceConnection $resourceConnection
    ) {}

    /**
     * Execute SQL query against Magento database.
     * 
     * @param string $query SQL query to execute (e.g., "SELECT * FROM catalog_product_entity LIMIT 10", "SELECT COUNT(*) FROM sales_order")
     * @param string $connection Database connection name (default: "default", alternative: "indexer")
     * @param string $format Output format: "table" (default), "json", or "csv"
     * @return TextContent
     */
    #[McpTool(name: 'execute_sql')]
    public function executeSql(string $query, string $connection = 'default', string $format = 'table'): TextContent
    {
        if (empty($query)) {
            throw new \InvalidArgumentException('Query is required');
        }

        $conn = $this->resourceConnection->getConnection($connection);
        $result = $conn->fetchAll($query);
        
        $text = match($format) {
            'json' => json_encode($result, JSON_PRETTY_PRINT),
            'csv' => $this->arrayToCsv($result),
            default => $this->arrayToTable($result)
        };

        return new TextContent($text);
    }

    private function arrayToTable(array $data): string
    {
        if (empty($data)) {
            return 'No results found.';
        }

        $headers = array_keys($data[0]);
        $rows = array_map(fn($row) => array_values($row), $data);
        
        $output = implode(' | ', $headers) . "\n";
        $output .= str_repeat('-', strlen($output) - 1) . "\n";
        
        foreach ($rows as $row) {
            $output .= implode(' | ', array_map(fn($v) => is_null($v) ? 'NULL' : (string)$v, $row)) . "\n";
        }
        
        return $output;
    }

    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $headers = array_keys($data[0]);
        $output = implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $output .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string)$v) . '"', array_values($row))) . "\n";
        }
        
        return $output;
    }
}

