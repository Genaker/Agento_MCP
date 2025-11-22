<?php
/**
 * Agento Core Module
 * MCP Server Command for AI Integration
 * 
 * Implements Model Context Protocol (MCP) server using php-mcp/server library
 */

namespace Agento\Core\Console\Command;

use Magento\Framework\App\ObjectManagerFactory;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;
use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class McpCommand extends Command
{
    /**
     * @var ObjectManagerFactory
     */
    private $objectManagerFactory;

    /**
     * @param ObjectManagerFactory $objectManagerFactory
     */
    public function __construct(ObjectManagerFactory $objectManagerFactory)
    {
        $this->objectManagerFactory = $objectManagerFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('agento:mcp')
            ->setDescription('Start Agento MCP server for AI integration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Disable output buffering to ensure stdout/stderr work correctly
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Ensure stdout and stderr are unbuffered and directly accessible
        // This is critical for MCP protocol which requires direct stdout access
        if (function_exists('stream_set_write_buffer')) {
            stream_set_write_buffer(STDOUT, 0);
            stream_set_write_buffer(STDERR, 0);
        }
        
        // Force flush any existing buffers
        if (function_exists('fflush')) {
            fflush(STDOUT);
            fflush(STDERR);
        }
        
        // Ensure STDOUT/STDERR constants point to actual file descriptors
        // In case Symfony Console redirected them, reopen them
        if (!is_resource(STDOUT) || !is_resource(STDERR)) {
            define('STDOUT', fopen('php://stdout', 'w'));
            define('STDERR', fopen('php://stderr', 'w'));
        }
        
        // Create a logger that writes to stderr
        $logger = new class extends AbstractLogger {
            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $logLine = sprintf(
                    "[%s] %s %s\n",
                    strtoupper($level),
                    $message,
                    empty($context) ? '' : json_encode($context)
                );
                fwrite(STDERR, $logLine);
                fflush(STDERR);
            }
        };

        try {
            $logger->info('Agento MCP Server starting...');

            // Create ObjectManager for dependency injection
            $omParams = $_SERVER;
            $objectManager = $this->objectManagerFactory->create($omParams);

            // Create a container adapter for php-mcp/server
            $container = new class($objectManager) implements \Psr\Container\ContainerInterface {
                private $om;

                public function __construct($objectManager)
                {
                    $this->om = $objectManager;
                }

                public function get(string $id)
                {
                    return $this->om->get($id);
                }

                public function has(string $id): bool
                {
                    return $this->om->has($id);
                }
            };

            // Build the MCP server
            $server = Server::make()
                ->withServerInfo('agento', '1.0.0')
                ->withLogger($logger)
                ->withContainer($container)
                ->build();

            // Discover MCP tools in the Mcp/Tools directory
            // basePath should be Magento root, scanDirs relative to basePath
            // Use Bootstrap to get Magento root reliably
            $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
            $magentoRoot = $bootstrap->getObjectManager()->get(\Magento\Framework\Filesystem\DirectoryList::class)->getRoot();
            
            $server->discover(
                basePath: $magentoRoot,
                scanDirs: ['app/code/Agento/Core/Mcp/Tools', 'app/code/Agento/Core/Mcp/Resources']
            );

            // Create stdio transport
            $transport = new StdioServerTransport();

            // Start listening (blocking call)
            $server->listen($transport);

            $logger->info('Server stopped gracefully.');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            fwrite(STDERR, "[MCP SERVER CRITICAL ERROR]\n");
            fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
            fwrite(STDERR, 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n");
            fwrite(STDERR, $e->getTraceAsString() . "\n");
            return Command::FAILURE;
        }
    }
}
