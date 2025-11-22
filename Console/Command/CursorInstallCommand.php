<?php
/**
 * Agento Core Module
 * Cursor MCP Server Installation Command
 * 
 * Inspired by Laravel Boost's InstallCommand
 * @see https://github.com/laravel/boost/blob/main/src/Console/InstallCommand.php
 */

namespace Agento\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CursorInstallCommand extends Command
{
    /**
     * @var string
     */
    private $magentoRoot;

    /**
     * @var string
     */
    private $magentoBin;

    /**
     * @var string
     */
    private $phpPath;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('agento:cursor:install')
            ->setDescription('Install Agento MCP server configuration in Cursor IDE')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force installation even if configuration already exists'
            )
            ->addOption(
                'php-path',
                null,
                InputOption::VALUE_REQUIRED,
                'Custom PHP executable path'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Agento MCP Server - Cursor Installation');
        
        // Discover environment
        if (!$this->discoverMagentoRoot($io)) {
            return Command::FAILURE;
        }
        
        if (!$this->discoverPhpPath($input, $io)) {
            return Command::FAILURE;
        }
        
        // Verify installation
        if (!$this->verifyInstallation($io)) {
            return Command::FAILURE;
        }
        
        // Build MCP command
        $mcpCommand = $this->buildMcpCommand();
        
        // Install configuration
        return $this->installMcpConfig($mcpCommand, $input->getOption('force'), $io);
    }

    /**
     * Discover Magento root directory
     *
     * @param SymfonyStyle $io
     * @return bool
     */
    private function discoverMagentoRoot(SymfonyStyle $io): bool
    {
        // Method 1: Try to find from command location
        $commandDir = __DIR__;
        $this->magentoRoot = dirname(dirname(dirname(dirname(dirname(dirname($commandDir))))));
        $this->magentoBin = $this->magentoRoot . '/bin/magento';
        
        // Method 2: If not found, search upwards
        if (!file_exists($this->magentoBin)) {
            $current = $commandDir;
            for ($i = 0; $i < 15; $i++) {
                $current = dirname($current);
                if (file_exists($current . '/bin/magento')) {
                    $this->magentoRoot = $current;
                    $this->magentoBin = $this->magentoRoot . '/bin/magento';
                    break;
                }
                // Stop if we hit filesystem root
                if ($current === dirname($current)) {
                    break;
                }
            }
        }
        
        // Method 3: Check if we're in a Composer vendor directory
        if (!file_exists($this->magentoBin)) {
            $vendorPath = dirname(dirname(dirname(dirname(dirname($commandDir)))));
            if (strpos($vendorPath, 'vendor') !== false) {
                // We're in vendor/Agento/Core/Console/Command
                // Go up to find Magento root
                $potentialRoot = dirname(dirname(dirname($vendorPath)));
                if (file_exists($potentialRoot . '/bin/magento')) {
                    $this->magentoRoot = $potentialRoot;
                    $this->magentoBin = $this->magentoRoot . '/bin/magento';
                }
            }
        }
        
        if (!file_exists($this->magentoBin)) {
            $io->error([
                'Could not find Magento installation.',
                '',
                'Please ensure you are running this command from within a Magento installation,',
                'or specify the Magento root path manually.'
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Discover PHP executable path
     *
     * @param InputInterface $input
     * @param SymfonyStyle $io
     * @return bool
     */
    private function discoverPhpPath(InputInterface $input, SymfonyStyle $io): bool
    {
        // Use custom path if provided
        if ($input->getOption('php-path')) {
            $phpPath = $input->getOption('php-path');
            if (!file_exists($phpPath) && !$this->isExecutable($phpPath)) {
                $io->error("PHP executable not found at: {$phpPath}");
                return false;
            }
            $this->phpPath = $phpPath;
            return true;
        }
        
        // Try PHP_BINARY first (current PHP process)
        if (defined('PHP_BINARY') && PHP_BINARY) {
            $this->phpPath = PHP_BINARY;
            if ($this->isExecutable($this->phpPath)) {
                return true;
            }
        }
        
        // Try 'php' in PATH
        $phpInPath = $this->findExecutable('php');
        if ($phpInPath) {
            $this->phpPath = $phpInPath;
            return true;
        }
        
        // Common PHP locations
        $commonPaths = [
            '/usr/bin/php',
            '/usr/local/bin/php',
            '/opt/homebrew/bin/php',
            'C:\\php\\php.exe',
            'C:\\xampp\\php\\php.exe',
        ];
        
        foreach ($commonPaths as $path) {
            if ($this->isExecutable($path)) {
                $this->phpPath = $path;
                return true;
            }
        }
        
        // Default to 'php' and hope it's in PATH
        $this->phpPath = 'php';
        $io->warning([
            'Could not detect PHP executable path.',
            'Using "php" - ensure it is available in your system PATH.',
            'You can specify a custom path with --php-path option.'
        ]);
        
        return true;
    }

    /**
     * Check if file is executable
     *
     * @param string $path
     * @return bool
     */
    private function isExecutable(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }
        
        // On Windows, check if it's a .exe or .bat
        if (PHP_OS_FAMILY === 'Windows') {
            return is_file($path) && (
                strtolower(substr($path, -4)) === '.exe' ||
                strtolower(substr($path, -4)) === '.bat'
            );
        }
        
        return is_executable($path);
    }

    /**
     * Find executable in PATH
     *
     * @param string $command
     * @return string|null
     */
    private function findExecutable(string $command): ?string
    {
        $path = getenv('PATH') ?: '';
        $paths = explode(PATH_SEPARATOR, $path);
        
        foreach ($paths as $dir) {
            if (empty($dir)) {
                continue;
            }
            
            $fullPath = $dir . DIRECTORY_SEPARATOR . $command;
            if ($this->isExecutable($fullPath)) {
                return $fullPath;
            }
            
            // On Windows, also check with .exe extension
            if (PHP_OS_FAMILY === 'Windows') {
                $fullPathExe = $fullPath . '.exe';
                if ($this->isExecutable($fullPathExe)) {
                    return $fullPathExe;
                }
            }
        }
        
        return null;
    }

    /**
     * Verify Magento installation
     *
     * @param SymfonyStyle $io
     * @return bool
     */
    private function verifyInstallation(SymfonyStyle $io): bool
    {
        $io->section('Verifying Installation');
        
        $checks = [
            'Magento root' => $this->magentoRoot,
            'Magento CLI' => $this->magentoBin,
            'PHP executable' => $this->phpPath,
        ];
        
        $allValid = true;
        foreach ($checks as $label => $path) {
            $exists = file_exists($path);
            if ($exists) {
                $io->text("  <fg=green>✓</> {$label}: <fg=cyan>{$path}</>");
            } else {
                $io->text("  <fg=red>✗</> {$label}: <fg=red>{$path}</> (not found)");
                $allValid = false;
            }
        }
        
        // Test PHP version
        $phpVersion = $this->getPhpVersion();
        if ($phpVersion) {
            $io->text("  <fg=green>✓</> PHP version: <fg=cyan>{$phpVersion}</>");
        } else {
            $io->text("  <fg=red>✗</> PHP version: <fg=red>Could not determine</>");
            $allValid = false;
        }
        
        // Test Magento command
        $magentoVersion = $this->getMagentoVersion();
        if ($magentoVersion) {
            $io->text("  <fg=green>✓</> Magento CLI: <fg=cyan>{$magentoVersion}</>");
        } else {
            $io->text("  <fg=red>✗</> Magento CLI: <fg=red>Could not execute</>");
            $allValid = false;
        }
        
        if (!$allValid) {
            $io->error('Installation verification failed. Please check the paths above.');
            return false;
        }
        
        $io->newLine();
        return true;
    }

    /**
     * Get PHP version
     *
     * @return string|null
     */
    private function getPhpVersion(): ?string
    {
        $output = [];
        $returnVar = 0;
        @exec("{$this->phpPath} --version 2>&1", $output, $returnVar);
        
        if ($returnVar === 0 && !empty($output)) {
            return trim($output[0]);
        }
        
        return null;
    }

    /**
     * Get Magento version
     *
     * @return string|null
     */
    private function getMagentoVersion(): ?string
    {
        $output = [];
        $returnVar = 0;
        $command = "{$this->phpPath} {$this->magentoBin} --version 2>&1";
        @exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && !empty($output)) {
            return trim($output[0]);
        }
        
        return null;
    }

    /**
     * Build MCP command configuration
     *
     * @return array
     */
    private function buildMcpCommand(): array
    {
        $inWsl = $this->isRunningInWsl();
        
        // Use absolute path for PHP if not in PATH
        $phpCommand = $this->phpPath;
        if (strpos($this->phpPath, DIRECTORY_SEPARATOR) !== false) {
            // It's an absolute path, use as-is
            $phpCommand = $this->phpPath;
        } else {
            // It's just 'php', use as-is (will be found in PATH)
            $phpCommand = 'php';
        }
        
        // Handle WSL if needed
        if ($inWsl) {
            // In WSL, we might need to use wsl.exe to run PHP
            // But for now, just use the PHP path directly
        }
        
        return [
            'command' => $phpCommand,
            'args' => [
                $this->magentoBin,
                'agento:mcp'
            ],
            'cwd' => $this->magentoRoot
        ];
    }

    /**
     * Check if running in WSL
     *
     * @return bool
     */
    private function isRunningInWsl(): bool
    {
        return !empty(getenv('WSL_DISTRO_NAME')) || !empty(getenv('IS_WSL'));
    }
        
    /**
     * Install MCP server configuration
     *
     * @param array $mcpCommand
     * @param bool $force
     * @param SymfonyStyle $io
     * @return int
     */
    private function installMcpConfig(array $mcpCommand, bool $force, SymfonyStyle $io): int
    {
        $io->section('Installing MCP Server Configuration');
        
        $config = [
            'mcpServers' => [
                'agento' => $mcpCommand
            ]
        ];
        
        $configJson = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        // Install project-level configuration
        $projectInstalled = $this->installProjectConfig($configJson, $force, $io);
        
        // Install global Cursor configuration
        $globalInstalled = $this->installGlobalConfig($config, $force, $io);
        
        // Summary
        $io->newLine();
        $io->section('Installation Summary');
        
        $results = [];
        $results[] = $projectInstalled 
            ? '<fg=green>✓</> Project config: .cursor/mcp.json (saved)'
            : '<fg=red>✗</> Project config: Failed to save';
        
        $results[] = $globalInstalled
            ? '<fg=green>✓</> Global config: Installed in Cursor settings'
            : '<fg=yellow>⚠</> Global config: Manual installation required';
        
        foreach ($results as $result) {
            $io->text("  {$result}");
        }
        
        $io->newLine();
        
        if ($globalInstalled) {
            $io->note('Please restart Cursor completely for the changes to take effect.');
        } else {
            $this->showManualInstructions($configJson, $io);
        }
        
        return ($projectInstalled || $globalInstalled) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Install project-level configuration
     *
     * @param string $configJson
     * @param bool $force
     * @param SymfonyStyle $io
     * @return bool
     */
    private function installProjectConfig(string $configJson, bool $force, SymfonyStyle $io): bool
    {
        $projectConfigPath = $this->magentoRoot . '/.cursor/mcp.json';
        $projectConfigDir = dirname($projectConfigPath);
        
        // Check if already exists
        if (file_exists($projectConfigPath) && !$force) {
            $io->text("  <fg=yellow>⚠</> Project config already exists: .cursor/mcp.json");
            $io->text("     Use --force to overwrite");
            return true; // Not a failure, just skipped
        }
        
        // Create directory
        if (!is_dir($projectConfigDir)) {
            if (!mkdir($projectConfigDir, 0755, true)) {
                $io->error("Could not create .cursor directory");
                return false;
            }
        }
        
        // Write config
        if (file_put_contents($projectConfigPath, $configJson) === false) {
            $io->error("Could not save project configuration to: {$projectConfigPath}");
            return false;
        }
        
        $io->text("  <fg=green>✓</> Project config: .cursor/mcp.json");
        return true;
    }

    /**
     * Install global Cursor configuration
     *
     * @param array $config
     * @param bool $force
     * @param SymfonyStyle $io
     * @return bool
     */
    private function installGlobalConfig(array $config, bool $force, SymfonyStyle $io): bool
    {
        $configPaths = $this->getCursorConfigPaths();
        $found = false;
        
        foreach ($configPaths as $os => $path) {
            $expandedPath = $this->expandPath($path);
            
            if (file_exists($expandedPath)) {
                $found = true;
                $io->text("  <fg=green>✓</> Found Cursor config: <fg=cyan>{$os}</>");
                
                if ($this->updateCursorConfig($expandedPath, $config, $force, $io)) {
                    $io->text("  <fg=green>✓</> Global config: Installed in Cursor settings");
                    return true;
                } else {
                    $io->text("  <fg=red>✗</> Global config: Failed to update");
                }
                break; // Only try the first found config
            }
        }
        
        if (!$found) {
            $io->text("  <fg=yellow>⚠</> Global config: Cursor settings file not found");
        }
        
        return false;
    }

    /**
     * Show manual installation instructions
     *
     * @param string $configJson
     * @param SymfonyStyle $io
     * @return void
     */
    private function showManualInstructions(string $configJson, SymfonyStyle $io): void
    {
        $io->section('Manual Installation Instructions');
        
        $io->text([
            'The project-level configuration has been saved to <fg=cyan>.cursor/mcp.json</>',
            '',
            'To complete the installation in Cursor:',
            '',
            '<fg=cyan>1. Open Cursor Settings</>',
            '   Press <fg=yellow>Cmd/Ctrl + ,</> (or go to Settings)',
            '',
            '<fg=cyan>2. Open JSON Settings</>',
            '   Click the <fg=yellow>{}</> icon to open JSON settings',
            '',
            '<fg=cyan>3. Add MCP Configuration</>',
            '   Add the following to your <fg=yellow>mcpServers</> section:'
        ]);
        
        $io->newLine();
        $io->block($configJson, null, 'fg=cyan', ' ', true);
        $io->newLine();
        
        $io->text([
            '<fg=cyan>4. Save and Restart</>',
            '   Save the settings and completely restart Cursor (not just close window)',
            '',
            '<fg=green>Tip:</> You can also copy the contents from <fg=cyan>.cursor/mcp.json</> directly.'
        ]);
    }

    /**
     * Get Cursor config file paths for different operating systems
     *
     * @return array
     */
    private function getCursorConfigPaths(): array
    {
        return [
            'macOS' => '~/Library/Application Support/Cursor/User/globalStorage/rooveterinaryinc.roo-cline/settings/cline_mcp_settings.json',
            'Windows' => '%APPDATA%\\Cursor\\User\\globalStorage\\rooveterinaryinc.roo-cline\\settings\\cline_mcp_settings.json',
            'Linux' => '~/.config/Cursor/User/globalStorage/rooveterinaryinc.roo-cline/settings/cline_mcp_settings.json',
            // Alternative paths (newer Cursor versions might use different locations)
            'macOS (alt)' => '~/Library/Application Support/Cursor/User/settings.json',
            'Windows (alt)' => '%APPDATA%\\Cursor\\User\\settings.json',
            'Linux (alt)' => '~/.config/Cursor/User/settings.json'
        ];
    }

    /**
     * Expand path with environment variables and home directory
     *
     * @param string $path
     * @return string
     */
    private function expandPath(string $path): string
    {
        // Expand ~ to home directory
        if (strpos($path, '~') === 0) {
            $path = str_replace('~', getenv('HOME') ?: getenv('USERPROFILE'), $path);
        }
        
        // Expand environment variables
        $path = preg_replace_callback('/%([^%]+)%/', function($matches) {
            return getenv($matches[1]) ?: $matches[0];
        }, $path);
        
        return $path;
    }

    /**
     * Update Cursor configuration file
     *
     * @param string $configPath
     * @param array $newConfig
     * @param bool $force
     * @param SymfonyStyle $io
     * @return bool
     */
    private function updateCursorConfig(string $configPath, array $newConfig, bool $force, SymfonyStyle $io): bool
    {
        try {
            // Read existing config
            $existingContent = file_exists($configPath) ? file_get_contents($configPath) : '{}';
            $existingConfig = json_decode($existingContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $io->warning("Existing config file has invalid JSON. Creating new file.");
                $existingConfig = [];
            }
            
            // Check if agento config already exists
            if (isset($existingConfig['mcpServers']['agento']) && !$force) {
                $io->text("  <fg=yellow>⚠</> Agento MCP server already configured");
                $io->text("     Use --force to overwrite");
                return true; // Not a failure, just skipped
            }
            
            // Merge configurations
            if (!isset($existingConfig['mcpServers'])) {
                $existingConfig['mcpServers'] = [];
            }
            
            // Merge agento config
            $existingConfig['mcpServers']['agento'] = $newConfig['mcpServers']['agento'];
            
            // Write back
            $newContent = json_encode($existingConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // Create directory if it doesn't exist
            $dir = dirname($configPath);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $io->error("Could not create directory: {$dir}");
                    return false;
                }
            }
            
            // Backup existing config if it exists
            if (file_exists($configPath)) {
                $backupPath = $configPath . '.backup.' . date('Y-m-d_H-i-s');
                @copy($configPath, $backupPath);
            }
            
            if (file_put_contents($configPath, $newContent) === false) {
                $io->error("Could not write to: {$configPath}");
                $io->text("You may need to run this command with appropriate permissions.");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $io->error("Error updating config: " . $e->getMessage());
            return false;
        }
    }
}

