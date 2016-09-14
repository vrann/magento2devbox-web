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
 * Command for Magento final steps
 */
class MagentoInstallAll extends AbstractCommand
{
    /**
     * @var array
     */
    private $optionsConfig;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:install-all')
            ->setDescription('Setup Magento and all components')
            ->setHelp('This command allows you to setup Magento and all components.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->executeCommand('magento:download', $input, $output);
        $this->executeCommand('magento:setup', $input, $output);
        $this->executeCommand('magento:setup:redis', $input, $output);
        $this->executeCommand('magento:setup:varnish', $input, $output);
        $this->executeCommand('magento:prepare', $input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        $optionsConfig = [];

        if ($this->optionsConfig === null) {
            /** @var AbstractCommand $command */
            foreach ($this->getApplication()->all() as $command) {
                if (!$command instanceof self) {
                    $optionsConfig = array_replace($optionsConfig, $command->getOptionsConfig());
                }
            }

            foreach ($optionsConfig as $optionName => $optionConfig) {
                $optionsConfig[$optionName]['initial'] = false;
            }

            $this->optionsConfig = $optionsConfig;
        }

        return $this->optionsConfig;
    }

    private function executeCommand($name, InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->get($name);

        //...

    }
}
