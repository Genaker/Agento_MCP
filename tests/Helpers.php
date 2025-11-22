<?php

if (!function_exists('getMagentoRoot')) {
    function getMagentoRoot(): string
    {
        $testDir = __DIR__;
        
        // Try app/code path first: tests/ -> Core/ -> Agento/ -> code/ -> app/ -> magento root
        $appCodePath = dirname(dirname(dirname(dirname(dirname($testDir)))));
        if (file_exists($appCodePath . '/bin/magento')) {
            return $appCodePath;
        }
        
        // Try vendor path: tests/ -> Core/ -> Agento/ -> vendor/ -> magento root
        $vendorPath = dirname(dirname(dirname(dirname($testDir))));
        if (file_exists($vendorPath . '/bin/magento')) {
            return $vendorPath;
        }
        
        // Fallback: go up from tests until we find bin/magento
        $current = $testDir;
        for ($i = 0; $i < 10; $i++) {
            $current = dirname($current);
            if (file_exists($current . '/bin/magento')) {
                return $current;
            }
        }
        
        // Default fallback
        return dirname(dirname(dirname(dirname(dirname($testDir)))));
    }
}

if (!function_exists('executeCommand')) {
    function executeCommand(array $command, string $cwd, int $timeout = 60, ?string $input = null): \Symfony\Component\Process\Process
    {
        // Use only test vendor autoload (not Magento's vendor)
        $testAutoload = __DIR__ . '/vendor/autoload.php';
        if (file_exists($testAutoload)) {
            require_once $testAutoload;
        } else {
            throw new \RuntimeException('Test vendor autoload not found. Run: cd ' . __DIR__ . ' && composer install');
        }
        
        $process = new \Symfony\Component\Process\Process($command, $cwd);
        $process->setTimeout($timeout);
        // Set idle timeout to ensure process doesn't hang waiting for input
        $process->setIdleTimeout($timeout);
        // Enable output buffering to capture incremental output
        $process->setOptions(['create_new_console' => false]);
        
        if ($input !== null) {
            $process->setInput($input);
        }
        
        // Run with callback to capture output incrementally
        $stdout = '';
        $stderr = '';
        $process->run(function ($type, $buffer) use (&$stdout, &$stderr) {
            if ($type === \Symfony\Component\Process\Process::OUT) {
                $stdout .= $buffer;
            } else {
                $stderr .= $buffer;
            }
        });
        
        return $process;
    }
}

