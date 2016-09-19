<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command;

require_once __DIR__ . '/../AbstractCommand.php';

use MagentoDevBox\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Magento installation
 */
class MagentoSetupIntegrationTests extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:setup:integration-tests')
            ->setDescription('Configure Magento to run integration tests')
            ->setHelp('This command allows you to configure Magento to run integration tests.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dbName = 'magento_integration_tests';

        $this->executeCommands(
            sprintf('mysql -h db -u root -proot -e "CREATE DATABASE IF NOT EXISTS %s;"', $dbName),
            $output
        );

        $magentoPath = $input->getOption('magento-path');
        $sourceFile = sprintf('%s/dev/tests/integration/etc/install-config-mysql.php.dist', $magentoPath);
        $targetFile = sprintf('%s/dev/tests/integration/etc/install-config-mysql.php', $magentoPath);

        if (file_exists($sourceFile) && !file_exists($targetFile)) {
            $config = file_get_contents($sourceFile);
            $config = $this->replaceOptionValues(
                [
                    'db-host' => 'db',
                    'db-user' => 'root',
                    'db-password' => 'root',
                    'db-name' => $dbName,
                    'backend-frontname' => 'admin'
                ],
                $config
            );
            file_put_contents($targetFile, $config);
        }
    }

    /**
     * Replace option values in config
     *
     * @param array $values
     * @param string $config
     * @return string
     */
    private function replaceOptionValues($values, $config)
    {
        foreach ($values as $name => $value) {
            $config = preg_replace("~'$name' => '.+',~", "'$name' => '$value',", $config);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            static::OPTION_MAGENTO_PATH => $this->getMagentoPathConfig()
        ];
    }
}
