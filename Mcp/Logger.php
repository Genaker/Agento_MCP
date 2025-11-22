<?php
/**
 * Agento Core Module
 * MCP Logger - Logs all MCP-related errors to a dedicated log file
 */

namespace Agento\Core\Mcp;

class Logger
{
    /**
     * Log file name
     */
    private const LOG_FILE = 'agento-mcp.log';
    
    /**
     * Maximum log file size before rotation (10MB)
     */
    private const MAX_LOG_SIZE = 10 * 1024 * 1024;
    
    /**
     * Log directory path (relative to Magento root)
     */
    private const LOG_DIR = 'var/log';
    
    /**
     * @var string|null
     */
    private ?string $magentoRoot = null;
    
    /**
     * Set Magento root directory
     *
     * @param string $root
     * @return self
     */
    public function setMagentoRoot(string $root): self
    {
        $this->magentoRoot = $root;
        return $this;
    }
    
    /**
     * Get Magento root directory
     *
     * @return string
     */
    private function getMagentoRoot(): string
    {
        if ($this->magentoRoot !== null) {
            return $this->magentoRoot;
        }
        
        // Try to discover Magento root from current file location
        $currentDir = __DIR__;
        
        // Try app/code path
        $appCodePath = dirname(dirname(dirname(dirname(dirname($currentDir)))));
        if ($this->isMagentoRoot($appCodePath)) {
            $this->magentoRoot = $appCodePath;
            return $appCodePath;
        }
        
        // Try vendor path
        if (strpos($currentDir, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
            $pathParts = explode(DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR, $currentDir);
            if (count($pathParts) >= 2) {
                $vendorPath = dirname($pathParts[0] . DIRECTORY_SEPARATOR . 'vendor');
                if ($this->isMagentoRoot($vendorPath)) {
                    $this->magentoRoot = $vendorPath;
                    return $vendorPath;
                }
            }
        }
        
        // Search upwards
        $current = $currentDir;
        for ($i = 0; $i < 15; $i++) {
            if ($this->isMagentoRoot($current)) {
                $this->magentoRoot = $current;
                return $current;
            }
            
            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }
            $current = $parent;
        }
        
        // Fallback
        $this->magentoRoot = $appCodePath;
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
     * Log error to agento-mcp.log file
     *
     * @param string $command The command that failed (optional)
     * @param string $errorDetails Detailed error information
     * @param string $errorOutput Raw error output from command (optional)
     * @return void
     */
    public function logError(string $command = '', string $errorDetails = '', string $errorOutput = ''): void
    {
        try {
            $magentoRoot = $this->getMagentoRoot();
            $logDir = $magentoRoot . '/' . self::LOG_DIR;
            
            // Create log directory if it doesn't exist
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            
            $logFile = $logDir . '/' . self::LOG_FILE;
            $timestamp = date('Y-m-d H:i:s');
            $pid = getmypid() ?: 'unknown';
            
            // Format log entry
            $logEntry = sprintf(
                "[%s] [PID:%s] [COMMAND:%s]\n%s\n%s\n%s\n",
                $timestamp,
                $pid,
                $command ?: 'N/A',
                str_repeat('-', 80),
                $errorDetails,
                $errorOutput ? "Raw Output:\n" . $errorOutput : ''
            );
            
            // Rotate log file if it exceeds maximum size
            if (file_exists($logFile) && filesize($logFile) > self::MAX_LOG_SIZE) {
                $backupFile = $logFile . '.' . date('Y-m-d-H-i-s') . '.backup';
                @rename($logFile, $backupFile);
            }
            
            // Append to log file (create if doesn't exist)
            @file_put_contents($logFile, $logEntry . "\n", FILE_APPEND | LOCK_EX);
            
        } catch (\Throwable $e) {
            // Silently fail if logging fails (don't break the main functionality)
            // Could optionally write to PHP error log, but avoiding to prevent recursion
        }
    }
    
    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logInfo(string $message, array $context = []): void
    {
        try {
            $magentoRoot = $this->getMagentoRoot();
            $logDir = $magentoRoot . '/' . self::LOG_DIR;
            
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            
            $logFile = $logDir . '/' . self::LOG_FILE;
            $timestamp = date('Y-m-d H:i:s');
            $pid = getmypid() ?: 'unknown';
            
            $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
            $logEntry = sprintf(
                "[%s] [PID:%s] [INFO] %s%s\n",
                $timestamp,
                $pid,
                $message,
                $contextStr
            );
            
            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            
        } catch (\Throwable $e) {
            // Silently fail
        }
    }
}

