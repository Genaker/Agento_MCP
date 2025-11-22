<?php
/**
 * Agento Core Module
 * SQL Query Execution Command
 */

namespace Agento\Core\Console\Command;

use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueryCommand extends Command
{
    /**
     * @var ObjectManagerFactory
     */
    private $objectManagerFactory;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

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
        $this->setName('agento:query')
            ->setDescription('Execute SQL query against Magento database')
            ->addOption(
                'query',
                null,
                InputOption::VALUE_REQUIRED,
                'SQL query to execute'
            )
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Database connection name (default: default)',
                'default'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output format (table, json, csv)',
                'table'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $omParams = $_SERVER;
        $objectManager = $this->objectManagerFactory->create($omParams);
        $this->resource = $objectManager->get(ResourceConnection::class);
        
        $connectionName = $input->getOption('connection');
        $this->connection = $this->resource->getConnection($connectionName);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $query = $input->getOption('query');
        
        if (empty($query)) {
            $output->writeln('<error>Query is required. Use --query or -q option.</error>');
            return Command::FAILURE;
        }

        try {
            $startTime = microtime(true);
            $result = $this->connection->fetchAll($query);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $format = $input->getOption('format');
            
            if (empty($result)) {
                $output->writeln('<info>Query executed successfully. No rows returned.</info>');
                $output->writeln("<comment>Execution time: {$executionTime}ms</comment>");
                return Command::SUCCESS;
            }

            $this->outputResults($output, $result, $format);
            $output->writeln("<comment>Rows returned: " . count($result) . " | Execution time: {$executionTime}ms</comment>");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error executing query: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Output results in specified format
     *
     * @param OutputInterface $output
     * @param array $result
     * @param string $format
     */
    private function outputResults(OutputInterface $output, array $result, string $format)
    {
        switch ($format) {
            case 'json':
                $output->writeln(json_encode($result, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                if (!empty($result)) {
                    $headers = array_keys($result[0]);
                    $output->writeln(implode(',', $headers));
                    foreach ($result as $row) {
                        $output->writeln(implode(',', array_map(function($value) {
                            return '"' . str_replace('"', '""', $value) . '"';
                        }, $row)));
                    }
                }
                break;
            case 'table':
            default:
                if (!empty($result)) {
                    $headers = array_keys($result[0]);
                    $rows = array_map(function($row) {
                        return array_values($row);
                    }, $result);
                    
                    $table = new \Symfony\Component\Console\Helper\Table($output);
                    $table->setHeaders($headers);
                    $table->setRows($rows);
                    $table->render();
                }
                break;
        }
    }
}

