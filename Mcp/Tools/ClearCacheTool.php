<?php
/**
 * Agento Core Module
 * MCP Tool: Clear Cache
 */

namespace Agento\Core\Mcp\Tools;

use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\TypeListInterface;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\JsonRpc\Contents\TextContent;

class ClearCacheTool
{
    public function __construct(
        private TypeListInterface $cacheTypeList,
        private CacheManager $cacheManager
    ) {}

    /**
     * Clear Magento cache.
     * 
     * @param string|null $type Cache type to clear (optional, clears all if not specified). Common types: config, layout, block_html, full_page, eav, collections, reflection, db_ddl, compiled_config, translate, config_integration, config_webservice
     * @return TextContent
     */
    #[McpTool(name: 'clear_cache')]
    public function clearCache(?string $type = null): TextContent
    {
        if ($type) {
            $this->cacheTypeList->cleanType($type);
            return new TextContent("Cleared cache type: {$type}");
        } else {
            $types = $this->cacheTypeList->getTypes();
            $cacheTypes = array_keys($types);
            $this->cacheManager->clean($cacheTypes);
            
            return new TextContent('All cache cleared successfully');
        }
    }
}

