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
class MagentoSetup extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:setup')
            ->setDescription('Install Magento')
            ->setHelp('This command allows you to install Magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $magentoPath = $input->getOption('magento-path');
        $this->executeCommands(
            sprintf('cd %s && rm -rf var/* pub/static/* app/etc/env.php app/etc/config.php', $magentoPath)
        );

        $command = sprintf(
            'cd %s && php bin/magento setup:install'
                . ' --base-url=http://localhost:1748/ --db-host=db --db-name=magento2'
                . ' --db-user=root --db-password=root --admin-firstname=Magento --admin-lastname=User'
                . ' --admin-email=user@example.com --admin-user=%s --admin-password=%s'
                . ' --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1'
                . ' --backend-frontname=%s',
            $magentoPath,
            $input->getOption('magento-admin-user'),
            $input->getOption('magento-admin-password'),
            $input->getOption('magento-backend-path')
        );

        if ($input->getOption('rabbitmq-setup')) {
            $amqpModuleExist = exec(
                sprintf('cd %s && php bin/magento module:status | grep Magento_Amqp', $magentoPath)
            );

            if ($amqpModuleExist) {
                $rabbitmqHost = $this->requestOption('rabbitmq-host', $input, $output);
                $rabbitmqPort = $this->requestOption('rabbitmq-port', $input, $output);

                $command .= sprintf(
                    ' --amqp-virtualhost=/ --amqp-host=%s --amqp-port=%s --amqp-user=guest --amqp-password=guest',
                    $rabbitmqHost,
                    $rabbitmqPort
                );
            }
        }

        $this->executeCommands($command, $output);

        if (!$input->getOption('magento-sources-reuse')) {
            $composerHomePath = sprintf('%s/var/composer_home', $magentoPath);

            if (!file_exists($composerHomePath)) {
                mkdir($composerHomePath, 0777, true);
            }

            copy('/home/magento2/.composer/auth.json', sprintf('%s/auth.json', $composerHomePath));

            if ($this->requestOption('magento-sample-data-install', $input, $output)) {
                $this->executeCommands(
                    [
                        sprintf('cd %s && php bin/magento sampledata:deploy', $magentoPath),
                        sprintf('cd %s && php bin/magento setup:upgrade', $magentoPath)
                    ],
                    $output
                );
            }
        }

        $output->writeln('To prepare magento sources run <info>m2init magento:finalize</info> command next');
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            static::OPTION_MAGENTO_PATH => $this->getMagentoPathConfig(),
            'magento-sources-reuse' => [
                'initial' => true,
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to use existing sources.',
                'question' => 'Do you want to use existing sources? %default%'
            ],
            'magento-backend-path' => [
                'initial' => true,
                'default' => 'admin',
                'description' => 'Magento backend path.',
                'question' => 'Please enter backend path %default%'
            ],
            'magento-admin-user' => [
                'initial' => true,
                'default' => 'admin',
                'description' => 'Admin username.',
                'question' => 'Please enter backend admin username %default%'
            ],
            'magento-admin-password' => [
                'initial' => true,
                'default' => 'admin123',
                'description' => 'Admin password.',
                'question' => 'Please enter backend admin password %default%'
            ],
            'rabbitmq-setup' => [
                'initial' => true,
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to install RabbitMQ.',
                'question' => 'Do you want to install RabbitMQ? %default%'
            ],
            'rabbitmq-host' => [
                'requireValue' => false,
                'default' => 'rabbit',
                'description' => 'RabbitMQ host.',
                'question' => 'Please specify RabbitMQ host %default%'
            ],
            'rabbitmq-port' => [
                'requireValue' => false,
                'default' => '5672',
                'description' => 'RabbitMQ port.',
                'question' => 'Please specify RabbitMQ port %default%'
            ],
            'magento-sample-data-install' => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to install Sample Data.',
                'question' => 'Do you want to install Sample Data? %default%'
            ]
        ];
    }
}
