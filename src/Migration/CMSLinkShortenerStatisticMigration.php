<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\CommandCompiler;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use UAParser\Parser;


// Added when we start using ua-parser with Contao 5
class CMSLinkShortenerStatisticMigration extends AbstractMigration {


    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var Contao\CoreBundle\Migration\CommandCompiler
     */
    private $commandCompiler;


    public function __construct( Connection $connection, CommandCompiler $commandCompiler ) {

        $this->connection = $connection;
        $this->commandCompiler = $commandCompiler;
    }


    public function shouldRun(): bool {

        $schemaManager = $this->connection->getSchemaManager();

        if( !$schemaManager->tablesExist(['tl_cms_link_shortener_statistics']) ) {
            return false;
        }

        // check for 'os' values used prior to Contao 5
        $res = $this->connection
            ->prepare("SELECT count(1) AS count FROM tl_cms_link_shortener_statistics WHERE BINARY os in ('mac', 'win-ce', 'win', 'ios', 'android', 'blackberry', 'symbian', 'webos', 'unix', 'unknown')")
            ->execute();

        if( $res && $res->rowCount() ) {
            $count = $res->fetchOne();
            if( $count ) {
                return true;
            }
        }

        return false;
    }


    public function run(): MigrationResult {

        // update table to contain needed field device
        $schemaManager = $this->connection->getSchemaManager();
        $columns = $schemaManager->listTableColumns('tl_cms_link_shortener_statistics');

        if( !isset($columns['device']) ) {

            $aCommands = $this->commandCompiler->compileCommands();

            foreach( $aCommands as $command ) {
                if( preg_match('/^ALTER TABLE tl_cms_link_shortener_statistics (.*)ADD device /', $command) ) {
                    $this->connection->executeQuery($command);
                }
            }
        }

        $res = $this->connection
            ->prepare("SELECT DISTINCT user_agent FROM tl_cms_link_shortener_statistics WHERE os in ('mac', 'win-ce', 'win', 'ios', 'android', 'blackberry', 'symbian', 'webos', 'unix', 'unknown')")
            ->execute();

        if( $res && $res->rowCount() ) {

            $rows = $res->fetchAll();

            $stmtUpdate = $this->connection->prepare("UPDATE tl_cms_link_shortener_statistics SET os=:os, browser=:browser, device=:device WHERE user_agent=:user_agent");

            foreach( $rows as $row ) {

                $userAgent = $row['user_agent'];

                $parser = Parser::create();
                $oUserAgent = $parser->parse($userAgent);

                $res = $stmtUpdate->execute([
                    'os' => $oUserAgent->os->family,
                    'browser' => $oUserAgent->ua->family,
                    'device' => $oUserAgent->device->family,
                    'user_agent' => $userAgent,
                ]);
            }
        }

        return $this->createResult(true);
    }
}
