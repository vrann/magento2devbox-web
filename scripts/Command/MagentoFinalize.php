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
        $magentoPath = $this->requestOption('magento-path', $input, $output);
        $this->executeCommands(sprintf('cd %s && php bin/magento deploy:mode:set developer', $magentoPath), $output);

        if ($this->requestOption('magento-static-deploy', $input, $output)) {
            $this->executeCommands(
                sprintf('cd %s && php bin/magento setup:static-content:deploy', $magentoPath),
                $output
            );
        } elseif ($this->requestOption('magento-grunt-compile', $input, $output)) {
            $this->executeCommands(
                [
                    sprintf(
                        'cd %s && cp Gruntfile.js.sample Gruntfile.js && cp package.json.sample package.json',
                        $magentoPath
                    ),
                    sprintf('cd %s && npm install && grunt refresh', $magentoPath)
                ],
                $output
            );
        }

        if ($this->requestOption('magento-di-compile', $input, $output)) {
            $this->executeCommands(sprintf('cd %s && php bin/magento setup:di:compile', $magentoPath), $output);
        }

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

        chmod('/var/www/magento2/bin/magento', 0750);

        $output->writeln('To open magento go to <info>http://localhost:1748</info> Admin area: <info>http://localhost:1748/admin</info>, login: <info>admin</info>, password: <info>admin123</info>');
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            'magento-path' => [
                'initial' => true,
                'default' => '/var/www/magento2',
                'description' => 'Path to source folder for Magento',
                'question' => 'Please enter path to source folder for Magento %default%'
            ],
            'magento-static-deploy' => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to pre-deploy all static contents.',
                'question' => 'Do you want to pre-deploy all static assets? %default%'
            ],
            'magento-grunt-compile' => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to compile CSS out of LESS via Grunt.',
                'question' => 'Do you want to compile CSS out of LESS via Grunt? %default%'
            ],
            'magento-di-compile' => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to create generated files beforehand.',
                'question' => 'Do you want to create generated files beforehand? %default%'
            ]
        ];
    }
}
