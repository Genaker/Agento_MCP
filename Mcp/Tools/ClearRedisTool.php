<?php
/**
 * Agento Core Module
 * MCP Tool: Clear Redis - Flush All Data from Redis
 * 
 * This tool directly connects to Redis and flushes all data using FLUSHALL command.
 * Useful for development environments to quickly clear all Redis data.
 */

namespace Agento\Core\Mcp\Tools;

use Agento\Core\Mcp\Logger;
use Agento\Core\Mcp\MagentoRootHelper;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\JsonRpc\Contents\TextContent;

class ClearRedisTool
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
        }
        return $this->logger;
    }
    
    /**
     * Clear/flush all data from Redis.
     * 
     * This command directly connects to Redis and executes FLUSHALL to remove all data.
     * Useful for development environments to quickly clear all Redis cache and session data.
     * 
     * @return TextContent
     */
    #[McpTool(name: 'clear_redis')]
    public function clearRedis(): TextContent
    {
        try {
            // Check if Redis extension is available
            if (!extension_loaded('redis')) {
                return new TextContent('Error: Redis PHP extension is not installed. Please install php-redis extension.');
            }
            
            // Get Redis configuration from env.php
            $redisConfig = $this->getRedisConfig();
            
            // Connect to Redis
            $redis = $this->connectToRedis($redisConfig);
            
            if (!$redis) {
                $errorMsg = sprintf(
                    'Error: Failed to connect to Redis at %s:%d. Please check your Redis configuration and ensure Redis server is running.',
                    $redisConfig['host'] ?? '127.0.0.1',
                    $redisConfig['port'] ?? 6379
                );
                
                // Log error
                $this->getLogger()->logError('clear_redis', $errorMsg, '');
                
                return new TextContent($errorMsg);
            }
            
            // Get info before flush (optional, for confirmation)
            $dbSize = $redis->dbSize();
            
            // Execute FLUSHALL command
            $result = $redis->flushAll();
            
            // Close connection
            $redis->close();
            
            if ($result) {
                $message = sprintf(
                    'Successfully flushed all data from Redis (host: %s:%d, database: %d, keys deleted: %d)',
                    $redisConfig['host'] ?? '127.0.0.1',
                    $redisConfig['port'] ?? 6379,
                    $redisConfig['database'] ?? 0,
                    $dbSize
                );
                return new TextContent($message);
            } else {
                return new TextContent('Warning: FLUSHALL command executed but returned false. Redis may have already been empty.');
            }
            
        } catch (\Throwable $e) {
            // Log error
            $errorDetails = "Exception: " . get_class($e) . "\n";
            $errorDetails .= "Message: " . $e->getMessage() . "\n";
            $errorDetails .= "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            $this->getLogger()->logError('clear_redis', $errorDetails, '');
            
            return new TextContent('Error clearing Redis: ' . $e->getMessage());
        }
    }
    
    /**
     * Get Redis configuration from env.php
     *
     * @return array
     */
    private function getRedisConfig(): array
    {
        $magentoRoot = $this->getMagentoRoot();
        $envFile = $magentoRoot . '/app/etc/env.php';
        
        if (!file_exists($envFile)) {
            // Return default config if env.php doesn't exist
            return [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 0,
                'password' => null,
            ];
        }
        
        $config = include $envFile;
        
        // Check cache configuration
        $cacheConfig = $config['cache']['frontend']['default']['backend_options'] ?? null;
        if ($cacheConfig && isset($cacheConfig['server'])) {
            return [
                'host' => $cacheConfig['server'],
                'port' => $cacheConfig['port'] ?? 6379,
                'database' => $cacheConfig['database'] ?? 0,
                'password' => $cacheConfig['password'] ?? null,
            ];
        }
        
        // Check session save configuration
        $sessionSave = $config['session']['save'] ?? null;
        if ($sessionSave === 'redis') {
            $sessionConfig = $config['session']['save_path'] ?? null;
            if ($sessionConfig) {
                // Parse Redis DSN: tcp://host:port?database=0
                if (preg_match('/tcp:\/\/([^:]+):(\d+)/', $sessionConfig, $matches)) {
                    return [
                        'host' => $matches[1],
                        'port' => (int)$matches[2],
                        'database' => 0,
                        'password' => null,
                    ];
                }
            }
        }
        
        // Try default Redis configuration
        return [
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'password' => null,
        ];
    }
    
    /**
     * Connect to Redis
     *
     * @param array $config
     * @return \Redis|null
     */
    private function connectToRedis(array $config): ?\Redis
    {
        try {
            $redis = new \Redis();
            
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 6379;
            $timeout = $config['timeout'] ?? 2.5;
            
            // Connect to Redis
            $connected = $redis->connect($host, $port, $timeout);
            
            if (!$connected) {
                return null;
            }
            
            // Authenticate if password is provided
            if (!empty($config['password'])) {
                $authResult = $redis->auth($config['password']);
                if (!$authResult) {
                    $redis->close();
                    return null;
                }
            }
            
            // Select database if specified
            if (isset($config['database']) && $config['database'] > 0) {
                $redis->select($config['database']);
            }
            
            return $redis;
            
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * Get Magento root directory
     *
     * @return string
     */
    private function getMagentoRoot(): string
    {
        return MagentoRootHelper::getMagentoRoot(__DIR__);
    }
}

