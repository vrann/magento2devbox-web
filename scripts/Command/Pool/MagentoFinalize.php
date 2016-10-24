<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Magento final steps
 */
class MagentoFinalize extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:finalize')
            ->setDescription('Prepare Magento for usage')
            ->setHelp('This command allows you to perform final steps for Magento usage.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $magentoPath = $input->getOption(MagentoOptions::PATH);
        $this->executeCommands(sprintf('cd %s && php bin/magento deploy:mode:set developer', $magentoPath), $output);

        if ($this->requestOption(MagentoOptions::STATIC_CONTENTS_DEPLOY, $input, $output)) {
            $this->executeCommands(
                sprintf('cd %s && php bin/magento setup:static-content:deploy', $magentoPath),
                $output
            );
        } elseif ($this->requestOption(MagentoOptions::GRUNT_COMPILE, $input, $output)) {
            $this->executeCommands(
                [
                    sprintf(
                        'cd %s && cp Gruntfile.js.sample Gruntfile.js && cp package.json.sample package.json',
                        $magentoPath
                    ),
                    sprintf('cd %s && npm install && grunt refresh --force', $magentoPath)
                ],
                $output
            );
        }

        if ($this->requestOption(MagentoOptions::DI_COMPILE, $input, $output)) {
            $this->executeCommands(sprintf('cd %s && php bin/magento setup:di:compile', $magentoPath), $output);
        }

        if ($this->requestOption(MagentoOptions::CRON_RUN, $input, $output)) {
            $crontab = implode(
                "\n",
                [
                    sprintf(
                        '* * * * * /usr/local/bin/php %s/bin/magento cron:run | grep -v "Ran jobs by schedule"'
                        . ' >> %s/var/log/magento.cron.log',
                        $magentoPath,
                        $magentoPath
                    ),
                    sprintf(
                        '* * * * * /usr/local/bin/php %s/update/cron.php >> %s/var/log/update.cron.log',
                        $magentoPath,
                        $magentoPath
                    ),
                    sprintf(
                        '* * * * * /usr/local/bin/php %s/bin/magento setup:cron:run >> %s/var/log/setup.cron.log',
                        $magentoPath,
                        $magentoPath
                    )
                ]
            );
            file_put_contents("/home/magento2/crontab.sample", $crontab . "\n");
            $this->executeCommands(['crontab /home/magento2/crontab.sample', 'crontab -l'], $output);
        }

        // setup configs for integration tests
        copy(
            sprintf('%s/dev/tests/integration/phpunit.xml.dist', $magentoPath),
            sprintf('%s/dev/tests/integration/phpunit.xml', $magentoPath)
        );
        copy(
            sprintf('%s/dev/tests/integration/etc/config-global.php.dist', $magentoPath),
            sprintf('%s/dev/tests/integration/etc/config-global.php', $magentoPath)
        );
        copy(
            sprintf('%s/dev/tests/integration/etc/install-config-mysql.travis.php.dist', $magentoPath),
            sprintf('%s/dev/tests/integration/etc/install-config-mysql.travis.php', $magentoPath)
        );

        chmod(sprintf('%s/bin/magento', $magentoPath), 0750);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            MagentoOptions::STATIC_CONTENTS_DEPLOY => MagentoOptions::get(MagentoOptions::STATIC_CONTENTS_DEPLOY),
            MagentoOptions::GRUNT_COMPILE => MagentoOptions::get(MagentoOptions::GRUNT_COMPILE),
            MagentoOptions::DI_COMPILE => MagentoOptions::get(MagentoOptions::DI_COMPILE),
            MagentoOptions::CRON_RUN => MagentoOptions::get(MagentoOptions::CRON_RUN)
        ];
    }
}
