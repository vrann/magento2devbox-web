<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\MagentoCloud as MagentoCloudOptions;
use MagentoDevBox\Command\Options\Composer as ComposerOptions;
use MagentoDevBox\Library\Registry;
use MagentoDevBox\Library\xDebugSwitcher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for downloading Magento sources
 */
class MagentoDownload extends AbstractCommand
{
    /**
     * @var int
     */
    private $keysAvailabilityInterval = 40;

    /**
     * @var int
     */
    private $maxAttemptsCount = 10;

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
        $installFromCloud = $this->requestOption(MagentoCloudOptions::INSTALL, $input, $output);
        $installFromComposer = $this->requestOption(MagentoOptions::INSTALL_FROM_COMPOSER, $input, $output);

        $magentoPath = $input->getOption(MagentoOptions::PATH);
        $authFile = '/home/magento2/.composer/auth.json';
        $rootAuth = sprintf('%s/auth.json', $magentoPath);
        if (!file_exists($authFile) && !(file_exists($rootAuth))) {
            $this->generateAuthFile($authFile, $input, $output);
        }


        if ($useExistingSources) {
            xDebugSwitcher::switchOff();
            $composerJsonExists = file_exists(sprintf('%s/composer.json', $magentoPath));
            if ($composerJsonExists) {
                $this->executeCommands(sprintf('cd %s && composer install', $magentoPath), $output);
            }
            xDebugSwitcher::switchOn();
        } else if ($installFromCloud) {
            xDebugSwitcher::switchOff();
            $this->installFromCloud($input, $output);
            $composerJsonExists = file_exists(sprintf('%s/composer.json', $magentoPath));
            if ($composerJsonExists) {
                $this->executeCommands(sprintf('cd %s && composer install', $magentoPath), $output);
            }
            xDebugSwitcher::switchOn();
        } else if ($installFromComposer) {
            $edition = strtolower($this->requestOption(MagentoOptions::EDITION, $input, $output)) == 'ee'
                ? 'enterprise'
                : 'community';
            $version = $this->requestOption(MagentoOptions::VERSION, $input, $output);
            $version = strlen($version) > 0 ? ':' . $version : '';

            xDebugSwitcher::switchOff();
            $this->executeCommands(
                [
                    sprintf(
                        'cd %s && composer create-project --repository-url=https://repo.magento.com/'
                        . ' magento/project-%s-edition%s .',
                        $magentoPath,
                        $edition,
                        $version
                    )
                ],
                $output
            );
            xDebugSwitcher::switchOn();
        } else {
            throw new \Exception(
                'You should select where to get Magento sources: from Composer, from Cloud '
                . 'or to use sources in shared directory. Right now none of the options is selected'
                . ' Please start from the beginning.'
            );
        }

        if (!Registry::get(static::CHAINED_EXECUTION_FLAG)) {
            $output->writeln('To setup magento run <info>m2init magento:setup</info> command next');
        }

        Registry::set(MagentoOptions::SOURCES_REUSE, $useExistingSources);
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

        $project = $this->requestProjectName($input, $output);
        $branch = $this->requestBranchName($input, $output, $project);
        $keyName = $this->getSshKey($input, $output);

        chmod(sprintf('/home/magento2/.ssh/%s', $keyName), 0600);

        $this->executeCommands(
            sprintf('echo "IdentityFile /home/magento2/.ssh/%s" >> /etc/ssh/ssh_config', $keyName),
            $output
        );

        if ($this->requestOption(MagentoCloudOptions::KEY_ADD, $input, $output)) {
            $this->executeCommands(
                [
                    sprintf('magento-cloud ssh-key:add /home/magento2/.ssh/%s.pub', $keyName),
                    'magento-cloud ssh-key:list'
                ],
                $output
            );
        }

        $sshHost = $this->shellExec('magento-cloud environment:ssh --pipe -p ' . $project . ' -e ' . $branch);

        $command = sprintf(
            'ssh -q -o "BatchMode=yes" %s "echo 2>&1" && echo $host SSH_OK || echo $host SSH_NOK',
            $sshHost
        );

        $output->writeln($command);
        $attempt = 0;

        do {
            for ($i = 0; $i < $this->keysAvailabilityInterval; $i++) {
                $output->write('.');
                sleep(1);
            }
            $result = $this->shellExec($command);
        } while (trim($result) != 'SSH_OK' || $attempt++ > $this->maxAttemptsCount);

        $output->writeln("\n");

        if (trim($result) == 'SSH_OK') {
            $output->writeln('SSH connection with the Magento Cloud can be established.');
        } else {
            throw new \Exception(
                'You selected to init project from the Magento Cloud, but SSH connection cannot be established.'
                    . ' Please start from the beginning.'
            );
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
     * Wrapper for shell_exec
     *
     * @param $command
     * @return string
     */
    private function shellExec($command)
    {
        return shell_exec($command);
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
            MagentoOptions::INSTALL_FROM_COMPOSER => MagentoOptions::get(MagentoOptions::INSTALL_FROM_COMPOSER),
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            MagentoOptions::EDITION => MagentoOptions::get(MagentoOptions::EDITION),
            MagentoOptions::VERSION => MagentoOptions::get(MagentoOptions::VERSION),
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @throws \Exception
     */
    private function requestProjectName(InputInterface $input, OutputInterface $output)
    {
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
        return $project;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $project
     * @return string
     * @throws \Exception
     */
    private function requestBranchName(InputInterface $input, OutputInterface $output, $project)
    {
        $this->executeCommands('magento-cloud environment:list --project=' . $project, $output);
        $branch = $this->requestOption(MagentoCloudOptions::BRANCH, $input, $output);

        while (!$branch) {
            if ($this->requestOption(MagentoCloudOptions::BRANCH_SKIP, $input, $output, true)) {
                $branch = $this->requestOption(MagentoCloudOptions::BRANCH, $input, $output, true);
            } else {
                throw new \Exception(
                    'You selected to init project from the Magento Cloud, but haven\'t provided branch name.'
                    . ' Please start from the beginning.'
                );
            }
        }
        return $branch;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @throws \Exception
     */
    private function getSshKey(InputInterface $input, OutputInterface $output)
    {
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
                            'New SSH key will be generated and saved to the local file. Enter the name for local file'
                        );

                        $this->executeCommands(
                            sprintf('ssh-keygen -t rsa -N "" -f /home/magento2/.ssh/%s', $keyName),
                            $output
                        );
                    } else {
                        throw new \Exception(
                            'You selected to init project from the Magento Cloud,'
                            . ' but SSH key for the Cloud is missing. Start from the beginning.'
                        );
                    }
                }
            }
            return $keyName;
        } else {
            $keyName = $this->requestOption(
                MagentoCloudOptions::KEY_NAME,
                $input,
                $output,
                false,
                'New SSH key will be generated and saved to the local file. Enter the name for local file'
            );

            $this->executeCommands(sprintf('ssh-keygen -t rsa -N "" -f /home/magento2/.ssh/%s', $keyName), $output);
            return $keyName;
        }
    }
}
