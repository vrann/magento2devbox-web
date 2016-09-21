<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Sub;

require_once __DIR__ . '/../AbstractCommand.php';
require_once __DIR__ . '/../Options/Magento.php';
require_once __DIR__ . '/../Options/MagentoCloud.php';
require_once __DIR__ . '/../Options/Composer.php';

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\MagentoCloud as MagentoCloudOptions;
use MagentoDevBox\Command\Options\Composer as ComposerOptions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for downloading Magento sources
 */
class MagentoDownload extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:download')
            ->setDescription('Download Magento sources')
            ->setHelp('This command allows you to download Magento sources.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $useExistingSources = $this->requestOption(MagentoOptions::SOURCES_REUSE, $input, $output);

        if (!$useExistingSources && $this->requestOption(MagentoCloudOptions::INSTALL, $input, $output)) {
            $this->installFromCloud($input, $output);
        }

        $magentoPath = $input->getOption(MagentoOptions::PATH);
        $authFile = '/home/magento2/.composer/auth.json';
        $rootAuth = sprintf('%s/auth.json', $magentoPath);

        if (!file_exists($authFile) && !(file_exists($rootAuth))) {
            $this->generateAuthFile($authFile, $input, $output);
        }

        if (!$useExistingSources
            && !$this->requestOption(MagentoCloudOptions::INSTALL, $input, $output)
            && !file_exists(sprintf('%s/composer.json', $magentoPath))
        ) {
            $version = strtolower($this->requestOption(MagentoOptions::EDITION, $input, $output)) == 'ee'
                ? 'enterprise'
                : 'community';
            $this->executeCommands(
                sprintf(
                    'cd %s && composer create-project --repository-url=""https://repo.magento.com/""'
                        . ' magento/project-%s-edition .',
                    $magentoPath,
                    $version
                ),
                $output
            );
        } else {
            $this->executeCommands(sprintf('cd %s && composer install', $magentoPath), $output);
        }

        $output->writeln('To setup magento run <info>m2init magento:setup</info> command next');
    }

    /**
     * Download sources from Magento Cloud
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    private function installFromCloud(InputInterface $input, OutputInterface $output)
    {
        if (!$this->commandExist('magento-cloud')) {
            $this->executeCommands('php /home/magento2/installer', $output);
        }

        $this->executeCommands('magento-cloud', $output);

        if ($this->requestOption(MagentoCloudOptions::KEY_REUSE, $input, $output)) {
            $keyName = $this->requestOption(MagentoCloudOptions::KEY_NAME, $input, $output);

            while (!file_exists(sprintf('/home/magento2/.ssh/%s', $keyName))) {
                if ($this->requestOption(MagentoCloudOptions::KEY_SWITCH, $input, $output, true)) {
                    $keyName = $this->requestOption(MagentoCloudOptions::KEY_NAME, $input, $output, true);
                } else {
                    if ($this->requestOption(MagentoCloudOptions::KEY_CREATE, $input, $output)) {
                        $keyName = $this->requestOption(
                            MagentoCloudOptions::KEY_NAME,
                            $input,
                            $output,
                            true,
                            'New key will be created. Enter the name of the SSH key'
                        );
                        $this->executeCommands(sprintf('ssh-keygen -t rsa -N "" -f /home/magento2/.ssh/%s', $keyName), $output);
                    } else {
                        throw new \Exception(
                            'You selected to init project from the Magento Cloud,'
                            . ' but SSH key for the Cloud is missing. Start from the beginning.'
                        );
                    }
                }
            }
        } else {
            $keyName = $this->requestOption(
                MagentoCloudOptions::KEY_NAME,
                $input,
                $output,
                false,
                'New key will be created. Enter the name of the SSH key'
            );
            $this->executeCommands(sprintf('ssh-keygen -t rsa -N "" -f /home/magento2/.ssh/%s', $keyName), $output);
        }

        chmod(sprintf('/home/magento2/.ssh/%s', $keyName), 0600);
        $this->executeCommands(sprintf('echo "IdentityFile /home/magento2/.ssh/%s" >> /etc/ssh/ssh_config', $keyName), $output);

        if ($this->requestOption(MagentoCloudOptions::KEY_ADD, $input, $output)) {
            $this->executeCommands(sprintf('magento-cloud ssh-key:add /home/magento2/.ssh/%s.pub', $keyName), $output);
        }

        $verifySSHCommand = 'ssh -q -o "BatchMode=yes" idymogyzqpche-master-7rqtwti@ssh.us.magentosite.cloud "echo 2>&1"'
            . ' && echo $host SSH_OK || echo $host SSH_NOK';
        $this->executeCommands($verifySSHCommand, $output);
        $result = shell_exec(
            $verifySSHCommand
        );

        if (trim($result) == 'SSH_OK') {
            $output->writeln('SSH connection with the Magento Cloud can be established.');
        } else {
            throw new \Exception(
                'You selected to init project from the Magento Cloud, but SSH connection cannot be established.'
                    . ' Please start from the beginning.'
            );
        }

        $this->executeCommands('magento-cloud project:list', $output);
        $project = $this->requestOption(MagentoCloudOptions::PROJECT, $input, $output);

        while (!$project) {
            if ($this->requestOption(MagentoCloudOptions::PROJECT_SKIP, $input, $output, true)) {
                $project = $this->requestOption(MagentoCloudOptions::PROJECT, $input, $output, true);
            } else {
                throw new \Exception(
                    'You selected to init project from the Magento Cloud, but haven\'t provided project name.'
                        . ' Please start from the beginning.'
                );
            }
        }

        $this->executeCommands('magento-cloud environment:list --project=' . $project, $output);
        $branch = $this->requestOption(MagentoCloudOptions::BRANCH, $input, $output);

        while (!$branch) {
            if ($this->requestOption(MagentoCloudOptions::BRANCH_SKIP, $input, $output, true)) {
                $project = $this->requestOption(MagentoCloudOptions::BRANCH, $input, $output, true);
            } else {
                throw new \Exception(
                    'You selected to init project from the Magento Cloud, but haven\'t provided branch name.'
                    . ' Please start from the beginning.'
                );
            }
        }

        $this->executeCommands(
            sprintf(
                'git clone --branch %s %s@git.us.magento.cloud:%s.git %s',
                $branch,
                $project,
                $project,
                $input->getOption(MagentoOptions::PATH)
            ),
            $output
        );
    }

    /**
     * Generate auth.json file
     *
     * @param string $authFile
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function generateAuthFile($authFile, InputInterface $input, OutputInterface $output)
    {
        $publicKey = $this->requestOption(ComposerOptions::PUBLIC_KEY, $input, $output);
        $privateKey = $this->requestOption(ComposerOptions::PRIVATE_KEY, $input, $output);
        $output->writeln('Writing auth.json');
        $json = sprintf(
            '{"http-basic": {"repo.magento.com": {"username": "%s", "password": "%s"}}}',
            $publicKey,
            $privateKey
        );
        file_put_contents($authFile, $json);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::SOURCES_REUSE => MagentoOptions::get(MagentoOptions::SOURCES_REUSE),
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            MagentoOptions::EDITION => MagentoOptions::get(MagentoOptions::EDITION),
            MagentoCloudOptions::INSTALL => MagentoCloudOptions::get(MagentoCloudOptions::INSTALL),
            MagentoCloudOptions::KEY_REUSE => MagentoCloudOptions::get(MagentoCloudOptions::KEY_REUSE),
            MagentoCloudOptions::KEY_CREATE => MagentoCloudOptions::get(MagentoCloudOptions::KEY_CREATE),
            MagentoCloudOptions::KEY_NAME => MagentoCloudOptions::get(MagentoCloudOptions::KEY_NAME),
            MagentoCloudOptions::KEY_SWITCH => MagentoCloudOptions::get(MagentoCloudOptions::KEY_SWITCH),
            MagentoCloudOptions::KEY_ADD => MagentoCloudOptions::get(MagentoCloudOptions::KEY_ADD),
            MagentoCloudOptions::PROJECT => MagentoCloudOptions::get(MagentoCloudOptions::PROJECT),
            MagentoCloudOptions::PROJECT_SKIP => MagentoCloudOptions::get(MagentoCloudOptions::PROJECT_SKIP),
            MagentoCloudOptions::BRANCH => MagentoCloudOptions::get(MagentoCloudOptions::BRANCH),
            MagentoCloudOptions::BRANCH_SKIP => MagentoCloudOptions::get(MagentoCloudOptions::BRANCH_SKIP),
            ComposerOptions::PUBLIC_KEY => ComposerOptions::get(ComposerOptions::PUBLIC_KEY),
            ComposerOptions::PRIVATE_KEY => ComposerOptions::get(ComposerOptions::PRIVATE_KEY)
        ];
    }
}
