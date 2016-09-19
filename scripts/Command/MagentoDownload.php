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
        $useExistingSources = $this->requestOption('magento-sources-reuse', $input, $output);

        if (!$useExistingSources && $this->requestOption('magento-cloud-install', $input, $output)) {
            $this->installFromCloud($input, $output);
        }

        $magentoPath = $input->getOption('magento-path');
        $authFile = '/home/magento2/.composer/auth.json';
        $rootAuth = sprintf('%s/auth.json', $magentoPath);

        if (!file_exists($authFile) && !(file_exists($rootAuth))) {
            $this->generateAuthFile($authFile, $input, $output);
        }

        if (!$useExistingSources
            && !$this->requestOption('magento-cloud-install', $input, $output)
            && !file_exists(sprintf('%s/composer.json', $magentoPath))
        ) {
            $version = strtolower($this->requestOption('magento-edition', $input, $output)) == 'ee'
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

        if ($this->requestOption('magento-cloud-key-reuse', $input, $output)) {
            $keyName = $this->requestOption('magento-cloud-key-name', $input, $output);

            while (!file_exists(sprintf('/home/magento2/.ssh/%s', $keyName))) {
                if ($this->requestOption('magento-cloud-key-switch', $input, $output, true)) {
                    $keyName = $this->requestOption('magento-cloud-key-name', $input, $output, true);
                } else {
                    if ($this->requestOption('magento-cloud-key-create', $input, $output)) {
                        $keyName = $this->requestOption(
                            'magento-cloud-key-name',
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
                'magento-cloud-key-name',
                $input,
                $output,
                false,
                'New key will be created. Enter the name of the SSH key'
            );
            $this->executeCommands(sprintf('ssh-keygen -t rsa -N "" -f /home/magento2/.ssh/%s', $keyName), $output);
        }

        chmod(sprintf('/home/magento2/.ssh/%s', $keyName), 0600);
        $this->executeCommands(sprintf('echo "IdentityFile /home/magento2/.ssh/%s" >> /etc/ssh/ssh_config', $keyName), $output);

        if ($this->requestOption('magento-cloud-key-add', $input, $output)) {
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
        $project = $this->requestOption('magento-cloud-project-name', $input, $output);

        while (!$project) {
            if ($this->requestOption('magento-cloud-project-skip', $input, $output, true)) {
                $project = $this->requestOption('magento-cloud-project-name', $input, $output, true);
            } else {
                throw new \Exception(
                    'You selected to init project from the Magento Cloud, but haven\'t provided project name.'
                        . ' Please start from the beginning.'
                );
            }
        }

        $this->executeCommands('magento-cloud environment:list --project=' . $project, $output);
        $branch = $this->requestOption('magento-cloud-branch', $input, $output);

        while (!$branch) {
            if ($this->requestOption('magento-cloud-branch-skip', $input, $output, true)) {
                $project = $this->requestOption('magento-cloud-branch', $input, $output, true);
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
                $input->getOption('magento-path')
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
        $publicKey = $this->requestOption('composer-public-key', $input, $output);
        $privateKey = $this->requestOption('composer-private-key', $input, $output);
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
            static::OPTION_MAGENTO_PATH => $this->getMagentoPathConfig(),
            'magento-sources-reuse' => [
                'initial' => true,
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to use existing sources.',
                'question' => 'Do you want to use existing sources? %default%'
            ],
            'magento-cloud-install' => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to get sources from Magento Cloud.',
                'question' => 'Do you want to initialize from Magento Cloud? %default%'
            ],
            'magento-cloud-branch' => [
                'default' => 'master',
                'description' => 'Magento Cloud branch to clone from.',
                'question' => 'What branch do you want to clone from? %default%'
            ],
            'magento-cloud-key-reuse' => [
                'boolean' => true,
                'default' => true,
                'description' => 'Whether to use existing SSH key for Magento Cloud.',
                'question' => 'Do you want to use existing SSH key? %default%'
            ],
            'magento-cloud-key-create' => [
                'boolean' => true,
                'default' => true,
                'description' => 'Do you want to create new SSH key?',
                'question' => 'Do you want to create new SSH key? %default%'
            ],
            'magento-cloud-key-name' => [
                'default' => 'id_rsa',
                'description' => 'Name of the SSH key to use with Magento Cloud.',
                'question' => 'What is the name of the SSH key to use with the Magento Cloud? %default%'
            ],
            'magento-cloud-key-switch' => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'File with the key does not exists, do you want to enter different name? %default%'
            ],
            'magento-cloud-key-add' => [
                'boolean' => true,
                'default' => true,
                'description' => 'Whether to add SSH key to Magento Cloud.',
                'question' => 'Do you want to add key to the Magento Cloud? %default%'
            ],
            'magento-cloud-project-name' => [
                'description' => 'Magento Cloud project to clone.',
                'question' => 'Please select project to clone'
            ],
            'magento-cloud-project-skip' => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'You haven\'t entered project name. Do you want to continue? %default%'
            ],
            'magento-cloud-branch-skip' => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'You haven\'t entered branch name. Do you want to continue? %default%'
            ],
            'composer-public-key' => [
                'description' => 'Composer public key for Magento.',
                'question' => 'Enter your Magento public key'
            ],
            'composer-private-key' => [
                'description' => 'Composer private key for Magento.',
                'question' => 'Enter your Magento private key'
            ],
            'magento-edition' => [
                'default' => 'CE',
                'description' => 'Edition of Magento to install.',
                'question' => 'Which version of Magento you want to be installed (please, choose CE or EE) %default%'
            ]
        ];
    }
}
