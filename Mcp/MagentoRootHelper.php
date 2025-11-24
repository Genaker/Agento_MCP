<?php
/**
 * Agento Core Module
 * Magento Root Directory Helper
 * 
 * Common helper to find Magento root directory from any location
 */

namespace Agento\Core\Mcp;

class MagentoRootHelper
{
    /**
     * Cached Magento root path
     *
     * @var string|null
     */
    private static ?string $magentoRoot = null;
    
    /**
     * Get Magento root directory
     *
     * @param string|null $startPath Optional starting path (defaults to __DIR__ of the caller)
     * @return string
     */
    public static function getMagentoRoot(?string $startPath = null): string
    {
        // Return cached value if available
        if (self::$magentoRoot !== null) {
            return self::$magentoRoot;
        }
        
        $currentDir = $startPath ?? __DIR__;
        
        // Try app/code path first
        $appCodePath = dirname(dirname(dirname(dirname(dirname($currentDir)))));
        if (self::isMagentoRoot($appCodePath)) {
            self::$magentoRoot = $appCodePath;
            return $appCodePath;
        }
        
        // Try vendor path
        if (strpos($currentDir, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
            $pathParts = explode(DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR, $currentDir);
            if (count($pathParts) >= 2) {
                $vendorPath = dirname($pathParts[0] . DIRECTORY_SEPARATOR . 'vendor');
                if (self::isMagentoRoot($vendorPath)) {
                    self::$magentoRoot = $vendorPath;
                    return $vendorPath;
                }
            }
        }
        
        // Search upwards from current directory
        $current = $currentDir;
        for ($i = 0; $i < 15; $i++) {
            if (self::isMagentoRoot($current)) {
                self::$magentoRoot = $current;
                return $current;
            }
            
            $parent = dirname($current);
            if ($parent === $current) {
                break; // Reached filesystem root
            }
            $current = $parent;
        }
        
        // Fallback to app/code path
        self::$magentoRoot = $appCodePath;
        return $appCodePath;
    }
    
    /**
     * Check if given path is a Magento root directory
     *
     * @param string $path
     * @return bool
     */
    public static function isMagentoRoot(string $path): bool
    {
        $markers = [
            $path . '/bin/magento',
            $path . '/app/etc/env.php',
            $path . '/app/etc/di.xml',
            $path . '/pub/index.php'
        ];
        
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
    
    /**
     * Set custom Magento root (useful for testing or edge cases)
     *
     * @param string $root
     * @return void
     */
    public static function setMagentoRoot(string $root): void
    {
        self::$magentoRoot = $root;
    }
    
    /**
     * Clear cached Magento root (useful for testing)
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$magentoRoot = null;
    }
}


