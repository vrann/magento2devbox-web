<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\WebServer as WebServerOptions;
use MagentoDevBox\Command\Options\Db as DbOptions;
use MagentoDevBox\Command\Options\Varnish as VarnishOptions;
use MagentoDevBox\Command\Registry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Bootstrap;
use Magento\PageCache\Model\Config;

/**
 * Command for Varnish setup
 */
class MagentoSetupVarnish extends AbstractCommand
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
        $this->setName('magento:setup:varnish')
            ->setDescription('Setup varnish')
            ->setHelp('This command allows you to setup Varnish inside magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (Registry::get(VarnishOptions::FPC_INSTALLED)
            || !$this->requestOption(VarnishOptions::FPC_SETUP, $input, $output)) {
            return;
        }

        $this->saveConfig($input, $output);

        require_once sprintf('%s/app/bootstrap.php', $this->requestOption('magento-path', $input, $output));

        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $objectManager = $bootstrap->getObjectManager();
        /** @var Config $config */
        $config = $objectManager->get(Config::class);
        $content = $config->getVclFile(Config::VARNISH_4_CONFIGURATION_PATH);
        file_put_contents($this->requestOption(VarnishOptions::CONFIG_PATH, $input, $output), $content);

        Registry::set(MagentoOptions::PORT, $this->requestOption(VarnishOptions::HOME_PORT, $input, $output));
        Registry::set(VarnishOptions::FPC_INSTALLED, true);
    }

    /**
     * Save config for Magento
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function saveConfig(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getPdoConnection($input);
        $connection->exec(
            'DELETE FROM core_config_data'
                . ' WHERE path = "system/full_page_cache/caching_application" '
                . ' OR path like "system/full_page_cache/varnish/%";'
        );

        $config = [
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'system/full_page_cache/caching_application',
                'value' => 2
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'system/full_page_cache/varnish/access_list',
                'value' => $this->requestOption(WebServerOptions::HOST, $input, $output)
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'system/full_page_cache/varnish/backend_host',
                'value' => $this->requestOption(WebServerOptions::HOST, $input, $output)
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'system/full_page_cache/varnish/backend_port',
                'value' => $this->requestOption(WebServerOptions::PORT, $input, $output)
            ]
        ];

        $statement = $connection->prepare(
            'INSERT INTO core_config_data (scope, scope_id, path, `value`) VALUES (:scope, :scope_id, :path, :value);'
        );

        foreach ($config as $item) {
            $statement->bindParam(':scope', $item['scope']);
            $statement->bindParam(':scope_id', $item['scope_id']);
            $statement->bindParam(':path', $item['path']);
            $statement->bindParam(':value', $item['value']);
            $statement->execute();
        }

        $this->executeCommands(
            sprintf(
                'cd %s && php bin/magento cache:clean config',
                $this->requestOption('magento-path', $input, $output)
            ),
            $output
        );

        $homePort = $this->requestOption(VarnishOptions::HOME_PORT, $input, $output);
        $magentoHost = $input->getOption('magento-host');
        $options = [
            'web/unsecure/base_url' => 'http',
            'web/secure/base_url' => 'https'
        ];

        foreach ($options as $optionPath => $protocol) {
            $statement = $connection->prepare(
                'UPDATE `core_config_data` SET `value`=:url WHERE `path`=:path'
            );
            $statement->bindParam(':url', sprintf('%s://%s:%s', $protocol, $magentoHost, $homePort));
            $statement->bindParam(':path', $optionPath);
            $statement->execute();
        }
    }

    /**
     * Get connection to database
     *
     * @param InputInterface $input
     * @return \PDO
     */
    private function getPdoConnection(InputInterface $input)
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
            VarnishOptions::FPC_SETUP => VarnishOptions::get(VarnishOptions::FPC_SETUP),
            VarnishOptions::CONFIG_PATH => VarnishOptions::get(VarnishOptions::CONFIG_PATH),
            VarnishOptions::HOME_PORT => VarnishOptions::get(VarnishOptions::HOME_PORT),
            WebServerOptions::HOST => WebServerOptions::get(WebServerOptions::HOST),
            WebServerOptions::PORT => WebServerOptions::get(WebServerOptions::PORT),
            DbOptions::HOST => DbOptions::get(DbOptions::HOST),
            DbOptions::PORT => DbOptions::get(DbOptions::PORT),
            DbOptions::USER => DbOptions::get(DbOptions::USER),
            DbOptions::PASSWORD => DbOptions::get(DbOptions::PASSWORD),
            DbOptions::NAME => DbOptions::get(DbOptions::NAME),
            MagentoOptions::HOST => MagentoOptions::get(MagentoOptions::HOST),
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH)
        ];
    }
}
