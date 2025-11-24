<?php
/**
 * Agento Core Module
 * MCP Tool: Execute Magerun Commands
 */

namespace Agento\Core\Mcp\Tools;

use Agento\Core\Mcp\Logger;
use Agento\Core\Mcp\MagentoRootHelper;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\JsonRpc\Contents\TextContent;
use Symfony\Component\Process\Process;

/**
 * Magerun MCP Tool - Executes n98-magerun2 commands through MCP
 * 
 * @see MagerunToolDoc For complete documentation and parameter reference
 */
class MagerunTool extends MagerunToolDoc
{
    /**
     * Positional argument priority list
     * First non-null value from this list becomes positional argument 0
     */
    private const POSITIONAL_ARG_PRIORITY = [
        'username', 'email', 'user', 'query', 'path', 'filename',
        'store', 'website', 'entityType', 'attributeCode', 'arg0'
    ];
    
    /**
     * Boolean flags mapping (variable name => magerun option name)
     */
    private const BOOLEAN_FLAGS = [
        'activate' => 'activate',
        'deactivate' => 'deactivate',
        'force' => 'force',
        'noNewline' => 'no-newline',
        'enabled' => 'enabled',
        'suppressEvent' => 'suppress-event',
        'useMycliInsteadOfMysql' => 'use-mycli-instead-of-mysql',
        'noAutoRehash' => 'no-auto-rehash',
        'strip' => 'strip',
        'gzip' => 'gzip',
        'gitFriendly' => 'git-friendly',
        'humanReadable' => 'human-readable',
        'noViews' => 'no-views',
        'drop' => 'drop',
        'dropTables' => 'drop-tables',
        'onlyIfEmpty' => 'only-if-empty',
        'optimize' => 'optimize',
        'skipAuthorizationEntryCreation' => 'skip-authorization-entry-creation',
        'addSource' => 'add-source',
    ];
    
    /**
     * String options mapping (variable name => magerun option name)
     */
    private const STRING_OPTIONS = [
        'type' => 'type',
        'scope' => 'scope',
        'scopeId' => 'scope-id',
        'format' => 'format',
        'sort' => 'sort',
        'sortOrder' => 'sort-order',
        'columns' => 'columns',
        'search' => 'search',
        'filterType' => 'filter-type',
        'group' => 'group',
        'module' => 'module',
        'key' => 'key',
        'value' => 'value',
    ];
    
    /**
     * Debug output all received arguments
     * 
     * @param string $command
     * @param mixed ...$args All other method parameters
     * @return void (exits via dd())
     */
    private function debugOutputArguments(
        string $command,
        ?string $username,
        ?string $email,
        ?string $user,
        ?string $query,
        ?string $path,
        ?string $store,
        ?string $website,
        ?string $type,
        ?string $scope,
        ?string $scopeId,
        ?string $filename,
        ?string $entityType,
        ?string $attributeCode,
        ?string $arg0,
        ?string $arg1,
        ?string $arg2,
        ?string $arg3,
        ?string $arg4,
        ?string $arg5,
        bool $activate,
        bool $deactivate,
        bool $force,
        bool $noNewline,
        bool $enabled,
        bool $suppressEvent,
        $connection,
        bool $useMycliInsteadOfMysql,
        bool $noAutoRehash,
        bool $strip,
        bool $gzip,
        bool $gitFriendly,
        bool $humanReadable,
        bool $noViews,
        bool $drop,
        bool $dropTables,
        bool $onlyIfEmpty,
        bool $optimize,
        bool $skipAuthorizationEntryCreation,
        $rounding,
        ?string $format,
        ?string $sort,
        ?string $sortOrder,
        ?string $columns,
        ?string $search,
        ?string $filterType,
        bool $addSource,
        ?string $group,
        ?string $module,
        ?string $key,
        ?string $value,
        bool $dd
    ): void {
        dd(compact(
            'command', 'username', 'email', 'user', 'query', 'path',
            'store', 'website', 'type', 'scope', 'scopeId', 'filename',
            'entityType', 'attributeCode', 'arg0', 'arg1', 'arg2', 'arg3', 'arg4', 'arg5',
            'activate', 'deactivate', 'force', 'noNewline', 'enabled',
            'suppressEvent', 'connection', 'useMycliInsteadOfMysql',
            'noAutoRehash', 'strip', 'gzip', 'gitFriendly', 'humanReadable',
            'noViews', 'drop', 'dropTables', 'onlyIfEmpty', 'optimize',
            'skipAuthorizationEntryCreation', 'rounding', 'format', 'sort',
            'sortOrder', 'columns', 'search', 'filterType', 'addSource',
            'group', 'module', 'key', 'value', 'dd'
        ));
    }
    
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
     * {@inheritDoc}
     */
    #[McpTool(name: 'magerun')]
    public function executeMagerun(
        // MCP automatically maps {"format": "json"} → $format = "json" etc ...
        string $command = '',
        ?string $username = null,
        ?string $email = null,
        ?string $user = null,
        ?string $query = null,
        ?string $path = null,
        ?string $store = null,
        ?string $website = null,
        ?string $type = null,
        ?string $scope = null,
        ?string $scopeId = null,
        ?string $filename = null,
        ?string $entityType = null,
        ?string $attributeCode = null,
        ?string $arg0 = null,
        ?string $arg1 = null,
        ?string $arg2 = null,
        ?string $arg3 = null,
        ?string $arg4 = null,
        ?string $arg5 = null,
        bool $activate = false,
        bool $deactivate = false,
        bool $force = false,
        bool $noNewline = false,
        bool $enabled = false,
        bool $suppressEvent = false,
        bool $connection = false,
        bool $useMycliInsteadOfMysql = false,
        bool $noAutoRehash = false,
        bool $strip = false,
        bool $gzip = false,
        bool $gitFriendly = false,
        bool $humanReadable = false,
        bool $noViews = false,
        bool $drop = false,
        bool $dropTables = false,
        bool $onlyIfEmpty = false,
        bool $optimize = false,
        bool $skipAuthorizationEntryCreation = false,
        bool $rounding = false,
        ?string $format = null,
        ?string $sort = null,
        ?string $sortOrder = null,
        ?string $columns = null,
        ?string $search = null,
        ?string $filterType = null,
        bool $addSource = false,
        ?string $group = null,
        ?string $module = null,
        ?string $key = null,
        ?string $value = null,
        bool $dd = false
    ): TextContent {
        // Debug: dump all parameters if requested
        if ($dd) {
            $this->debugOutputArguments(
                $command, $username, $email, $user, $query, $path, $store, $website,
                $type, $scope, $scopeId, $filename, $entityType, $attributeCode,
                $arg0, $arg1, $arg2, $arg3, $arg4, $arg5, $activate, $deactivate, $force, $noNewline,
                $enabled, $suppressEvent, $connection, $useMycliInsteadOfMysql,
                $noAutoRehash, $strip, $gzip, $gitFriendly, $humanReadable,
                $noViews, $drop, $dropTables, $onlyIfEmpty, $optimize,
                $skipAuthorizationEntryCreation, $rounding, $format, $sort,
                $sortOrder, $columns, $search, $filterType, $addSource,
                $group, $module, $key, $value, $dd
            );
        }
        
        // Build params array from individual parameters
        $params = [];
        
        // Smart mapping: Use descriptive parameters as positional arguments
        // Priority list - first non-null value becomes positional arg 0
        $positionalArgPriority = [
            'username', 'email', 'user', 'query', 'path', 'filename',
            'store', 'website', 'entityType', 'attributeCode', 'arg0'
        ];
        
        foreach ($positionalArgPriority as $argName) {
            if ($$argName !== null) {
                $params[0] = $$argName;
                break;
            }
        }
        
        // Add remaining positional arguments
        if ($arg1 !== null) $params[1] = $arg1;
        if ($arg2 !== null) $params[2] = $arg2;
        if ($arg3 !== null) $params[3] = $arg3;
        if ($arg4 !== null) $params[4] = $arg4;
        if ($arg5 !== null) $params[5] = $arg5;
        
        // Map boolean flags to magerun options
        $booleanFlags = [
            'activate' => 'activate',
            'deactivate' => 'deactivate',
            'force' => 'force',
            'noNewline' => 'no-newline',
            'enabled' => 'enabled',
            'suppressEvent' => 'suppress-event',
            'useMycliInsteadOfMysql' => 'use-mycli-instead-of-mysql',
            'noAutoRehash' => 'no-auto-rehash',
            'strip' => 'strip',
            'gzip' => 'gzip',
            'gitFriendly' => 'git-friendly',
            'humanReadable' => 'human-readable',
            'noViews' => 'no-views',
            'drop' => 'drop',
            'dropTables' => 'drop-tables',
            'onlyIfEmpty' => 'only-if-empty',
            'optimize' => 'optimize',
            'skipAuthorizationEntryCreation' => 'skip-authorization-entry-creation',
            'addSource' => 'add-source',
        ];
        
        foreach ($booleanFlags as $varName => $optionName) {
            if ($$varName) {
                $params[$optionName] = true;
            }
        }
        
        // Map string options to magerun options
        $stringOptions = [
            'type' => 'type',
            'scope' => 'scope',
            'scopeId' => 'scope-id',
            'format' => 'format',
            'sort' => 'sort',
            'sortOrder' => 'sort-order',
            'columns' => 'columns',
            'search' => 'search',
            'filterType' => 'filter-type',
            'group' => 'group',
            'module' => 'module',
            'key' => 'key',
            'value' => 'value',
        ];
        
        foreach ($stringOptions as $varName => $optionName) {
            if ($$varName !== null) {
                $params[$optionName] = $$varName;
            }
        }
        
        // Handle special cases
        if ($connection !== false) {
            // Connection can be a boolean or string, handle accordingly
            if (is_string($connection)) {
                $params['connection'] = $connection;
            } else {
                $params['connection'] = true;
            }
        }
        if ($rounding !== false) {
            // Rounding can be a boolean or numeric value
            if (is_numeric($rounding)) {
                $params['rounding'] = $rounding;
            } else {
                $params['rounding'] = true;
            }
        }
        
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
            // Use  {"command": "admin:user:activate", "username": "admin"}

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

    protected function getMagentoRoot(): string
    {
        return MagentoRootHelper::getMagentoRoot(__DIR__);
    }
    
    /**
     * Check if given path is a Magento root directory
     *
     * @param string $path
     * @return bool
     */
    protected function isMagentoRoot(string $path): bool
    {
        return MagentoRootHelper::isMagentoRoot($path);
    }
}

