<?php
/**
 * Agento Core Module
 * MCP Resource: Configuration Files
 */

namespace Agento\Core\Mcp\Resources;

use Magento\Framework\Filesystem\DirectoryList;
use PhpMcp\Server\Attributes\McpResourceTemplate;
use PhpMcp\Server\JsonRpc\Contents\TextContent;

class ConfigResource
{
    public function __construct(
        private DirectoryList $directoryList
    ) {}

    /**
     * Read Magento configuration files.
     * 
     * @param string $file Configuration file path relative to Magento root (e.g., "app/etc/env.php", "app/etc/config.php")
     * @return TextContent
     */
    #[McpResourceTemplate(
        uriTemplate: 'config://{file}',
        name: 'magento_config',
        description: 'Read Magento configuration files. Use config://app/etc/env.php to read environment configuration, config://app/etc/config.php for module configuration, or any other config file path relative to Magento root.',
        mimeType: 'text/plain'
    )]
    public function readConfigFile(string $file): TextContent
    {
        $magentoRoot = $this->directoryList->getRoot();
        $filePath = $magentoRoot . '/' . ltrim($file, '/');
        
        // Security: Prevent directory traversal
        $realPath = realpath($filePath);
        $realRoot = realpath($magentoRoot);
        
        if ($realPath === false || strpos($realPath, $realRoot) !== 0) {
            throw new \InvalidArgumentException("Invalid file path: {$file}");
        }
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Configuration file not found: {$file}");
        }
        
        if (!is_readable($filePath)) {
            throw new \RuntimeException("Configuration file is not readable: {$file}");
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read configuration file: {$file}");
        }
        
        return new TextContent($content);
    }
}



