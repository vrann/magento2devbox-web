<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

require_once __DIR__ . '/../AbstractCommand.php';
require_once __DIR__ . '/../Options/Magento.php';
require_once __DIR__ . '/../Options/Db.php';
require_once __DIR__ . '/../Options/ElasticSearch.php';

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\Db as DbOptions;
use MagentoDevBox\Command\Options\ElasticSearch as ElasticSearchOptions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for ElasticSearch setup
 */
class MagentoSetupElasticSearch extends AbstractCommand
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:setup:elasticsearch')
            ->setDescription('Setup ElasticSearch for Magento')
            ->setHelp('This command allows you to setup ElasticSearch for Magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getPDOConnection($input)->exec(
            'DELETE FROM core_config_data'
                . ' WHERE path = "catalog/search/elasticsearch_server_hostname" '
                . ' OR path = "catalog/search/elasticsearch_server_port"'
                . ' OR path = "catalog/search/engine";'
        );

        $config = [
            [
                'path' => 'catalog/search/engine',
                'value' => 'elasticsearch'
            ],
            [
                'path' => 'catalog/search/elasticsearch_server_hostname',
                'value' => $input->getOption(ElasticSearchOptions::HOST)
            ],
            [
                'path' => 'catalog/search/elasticsearch_server_port',
                'value' => $input->getOption(ElasticSearchOptions::PORT)
            ]
        ];

        $stmt = $this->getPDOConnection($input)->prepare(
            'INSERT INTO core_config_data (scope, scope_id, path, `value`) VALUES ("default", 0, :path, :value);'
        );

        foreach ($config as $item) {
            $stmt->bindParam(':path', $item['path']);
            $stmt->bindParam(':value', $item['value']);
            $stmt->execute();
        }

        $this->executeCommands(
            sprintf('cd %s && php bin/magento cache:clean config', $input->getOption(MagentoOptions::PATH)),
            $output
        );
    }

    /**
     * Get connection to database
     *
     * @param InputInterface $input
     * @return \PDO
     */
    private function getPDOConnection(InputInterface $input)
    {
        if ($this->pdo === null) {
            $this->pdo = new \PDO(
                sprintf(
                    'mysql:dbname=%s;host=%s',
                    $input->getOption(DbOptions::NAME),
                    $input->getOption(DbOptions::HOST)
                ),
                $input->getOption(DbOptions::USER),
                $input->getOption(DbOptions::PASSWORD)
            );
        }

        return $this->pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            DbOptions::HOST => DbOptions::get(DbOptions::HOST),
            DbOptions::USER => DbOptions::get(DbOptions::USER),
            DbOptions::PASSWORD => DbOptions::get(DbOptions::PASSWORD),
            DbOptions::NAME => DbOptions::get(DbOptions::NAME),
            ElasticSearchOptions::HOST => ElasticSearchOptions::get(ElasticSearchOptions::HOST),
            ElasticSearchOptions::PORT => ElasticSearchOptions::get(ElasticSearchOptions::PORT)
        ];
    }
}
