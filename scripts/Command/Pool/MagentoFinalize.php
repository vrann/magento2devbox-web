<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\Varnish as VarnishOptions;
use MagentoDevBox\Command\Options\Db as DbOptions;
use MagentoDevBox\Library\Db;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Magento final steps
 */
class MagentoFinalize extends AbstractCommand
{
    /**
     * @var string
     */
    private $dbConfigScope = 'default';

    /**
     * @var integer
     */
    private $dbConfigScopeId = 0;

    /**
     * @var string
     */
    private $dbConfigPath = 'web/unsecure/base_url';

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

        if ($this->requestOption(MagentoOptions::WARM_UP_STOREFRONT, $input, $output)) {
            $useVarnish = $input->getOption(VarnishOptions::FPC_SETUP);
            $tmpUrl = $this->prepareTmpUrl($useVarnish);
            $oldUrl = $this->modifyMagentoUrl($input, $tmpUrl);
            $this->executeCommands(['cd /tmp && wget -E -H -k -K -p ' . $tmpUrl], $output);
            $this->restoreMagentoUrl($input, $oldUrl);
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
            MagentoOptions::CRON_RUN => MagentoOptions::get(MagentoOptions::CRON_RUN),
            MagentoOptions::WARM_UP_STOREFRONT => MagentoOptions::get(MagentoOptions::WARM_UP_STOREFRONT),
            VarnishOptions::FPC_SETUP => VarnishOptions::get(VarnishOptions::FPC_SETUP),
            DbOptions::HOST => DbOptions::get(DbOptions::HOST),
            DbOptions::USER => DbOptions::get(DbOptions::USER),
            DbOptions::PASSWORD => DbOptions::get(DbOptions::PASSWORD),
            DbOptions::NAME => DbOptions::get(DbOptions::NAME)
        ];
    }

    /**
     * Prepare magento url for usage inside web container
     *
     * @param boolean $useVarnish
     * @return string
     */
    private function prepareTmpUrl($useVarnish)
    {
        $url = 'http://';
        $url .= ($useVarnish) ? 'varnish:6081' : 'localhost';
        $url .= '/';

        return $url;
    }

    /**
     * Change Magento url to temporary
     *
     * @param InputInterface $input
     * @param string $tmpUrl
     * @return string
     */
    private function modifyMagentoUrl(InputInterface $input, $tmpUrl)
    {
        $dbConnection = Db::getConnection(
            $input->getOption(DbOptions::HOST),
            $input->getOption(DbOptions::USER),
            $input->getOption(DbOptions::PASSWORD),
            $input->getOption(DbOptions::NAME)
        );
        $statement = $dbConnection->prepare(
            'SELECT `value` FROM `core_config_data` WHERE `scope`=? AND `scope_id`=? AND `path`=? LIMIT 1'
        );
        $statement->bindParam(1, $this->dbConfigScope, \PDO::PARAM_STR);
        $statement->bindParam(2, $this->dbConfigScopeId, \PDO::PARAM_INT);
        $statement->bindParam(3, $this->dbConfigPath, \PDO::PARAM_STR);
        $statement->execute();
        $oldValue = $statement->fetch()['value'];
        $statement = $dbConnection->prepare(
            'UPDATE `core_config_data` SET `value`=? WHERE `scope`=? AND `scope_id`=? AND `path`=?'
        );
        $statement->bindParam(1, $tmpUrl, \PDO::PARAM_STR);
        $statement->bindParam(2, $this->dbConfigScope, \PDO::PARAM_STR);
        $statement->bindParam(3, $this->dbConfigScopeId, \PDO::PARAM_INT);
        $statement->bindParam(4, $this->dbConfigPath, \PDO::PARAM_STR);
        $statement->execute();

        $magentoPath = $input->getOption(MagentoOptions::PATH);
        $this->executeCommands(
            sprintf(
                '%s/bin/magento cache:clean config',
                $magentoPath
            )
        );

        return $oldValue;
    }

    /**
     * Restores external Magento url
     *
     * @param InputInterface $input
     * @param string $originalUrl
     * @return void
     */
    private function restoreMagentoUrl(InputInterface $input, $originalUrl)
    {

        $dbConnection = Db::getConnection(
            $input->getOption(DbOptions::HOST),
            $input->getOption(DbOptions::USER),
            $input->getOption(DbOptions::PASSWORD),
            $input->getOption(DbOptions::NAME)
        );
        $statement = $dbConnection->prepare(
            'UPDATE `core_config_data` SET `value`=? WHERE `scope`=? AND `scope_id`=? AND `path`=?'
        );
        $statement->bindParam(1, $originalUrl, \PDO::PARAM_STR);
        $statement->bindParam(2, $this->dbConfigScope, \PDO::PARAM_STR);
        $statement->bindParam(3, $this->dbConfigScopeId, \PDO::PARAM_INT);
        $statement->bindParam(4, $this->dbConfigPath, \PDO::PARAM_STR);
        $statement->execute();

        $magentoPath = $input->getOption(MagentoOptions::PATH);
        $this->executeCommands(
            sprintf(
                '%s/bin/magento cache:clean config',
                $magentoPath
            )
        );
    }
}
