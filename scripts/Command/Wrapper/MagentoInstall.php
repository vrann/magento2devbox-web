<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Wrapper;

require_once __DIR__ . '/../../AbstractCommand.php';

use MagentoDevBox\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Command for Magento final steps
 */
class MagentoInstall extends AbstractCommand
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
        $this->setName('magento:install')
            ->setDescription('Setup Magento and all components')
            ->setHelp('This command allows you to setup Magento and all components.');
    }

    /**
     * Perform delayed configuration
     *
     * @return void
     */
    public function postConfigure()
    {
        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws CommandNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->executeWrappedCommands(
            [
                'magento:download',
                'magento:setup',
                'magento:setup:redis',
                'magento:setup:varnish',
                'magento:setup:integration-tests',
                'magento:finalize'
            ],
            $input,
            $output
        );
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
                if ($command instanceof AbstractCommand && !$command instanceof self) {
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

    /**
     * Execute wrapped commands
     *
     * @param array|string $commandNames
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws CommandNotFoundException
     */
    private function executeWrappedCommands($commandNames, InputInterface $input, OutputInterface $output)
    {
        $commandNames = (array)$commandNames;

        foreach ($commandNames as $commandName) {
            $this->executeWrappedCommand($commandName, $input, $output);
        }
    }

    /**
     * Execute wrapped command
     *
     * @param string $commandName
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws CommandNotFoundException
     */
    private function executeWrappedCommand($commandName, InputInterface $input, OutputInterface $output)
    {
        /** @var AbstractCommand $command */
        $command = $this->getApplication()->get($commandName);
        $arguments = [null, $commandName];

        foreach ($command->getOptionsConfig() as $optionName => $optionConfig) {
            if (!$this->getConfigValue('virtual', $optionConfig, false)
                && $input->hasParameterOption('--' . $optionName)
            ) {
                $arguments[] = sprintf('--%s=%s', $optionName, $input->getOption($optionName));
            }
        }

        $commandInput = new ArgvInput($arguments);
        $commandInput->setInteractive($input->isInteractive());
        $command->run($commandInput, $output);
    }
}
