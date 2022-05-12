<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2021 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2021 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuiteBundle\Migration;

use Contao\CMSConfig;
use Contao\InstallationBundle\Database\Installer;
use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider; // Deprecated since Contao 4.11,
use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use numero2\MarketingSuite\Backend\License;
use numero2\MarketingSuiteBundle\Util\DoctrineUtil;


class CMSStatisticMigration extends AbstractMigration {


    /**
     * @var Doctrine\DBAL\Connection;
     */
    private $connection;

    /**
    * @var Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
    */
    private $schemaProvider;


    // TODO drop DcaSchemaProvider for second argument with Contao 5
    public function __construct( Connection $connection, $schemaProvider ) {

        $this->connection = $connection;

        if( !$schemaProvider instanceof SchemaProvider && !$schemaProvider instanceof DcaSchemaProvider ) {
            throw new InvalidArgumentException("SchemaProvider must be of type SchemaProvider DcaSchemaProvider");

        }

        $this->schemaProvider = $schemaProvider;
    }


    public function shouldRun(): bool {

        return false;
        $schemaManager = $this->connection->getSchemaManager();

        if( !$schemaManager->tablesExist(['tl_cms_statistic']) ) {
            return true;
        }

        return false;
    }


    public function run(): MigrationResult {

        $oInstaller = new Installer($this->connection, $this->schemaProvider);

        $aCommands = $oInstaller->getCommands(true);

        if( !empty($aCommands['CREATE']) ) {

            $commands = array_filter($aCommands['CREATE'], function( $command ) {
                return strpos($command, 'tl_cms_statistic') !== false;
            });

            if( !empty($commands) ) {
                foreach( $commands as $hash => $command ) {

                    $oInstaller->execCommand($hash);
                }
            }
        }

        $aQueryTable = [];

        // tl_cms_content_group -> clicks
        // tl_cms_content_group -> views
        // tl_cms_content_group -> reset
        $aQueryTable[] = [
            'table' => 'tl_cms_content_group'
        ,   'query' => "SELECT id, clicks, views, reset FROM tl_cms_content_group WHERE clicks>0 OR views>0"
        ];

        // tl_content -> cms_mi_views
        $aQueryTable[] = [
            'table' => 'tl_content'
        ,   'query' => "SELECT id, cms_mi_views AS views FROM tl_content WHERE cms_mi_views>0"
        ];

        // tl_content -> cms_ci_clicks
        // tl_content -> cms_ci_views
        // tl_content -> cms_ci_reset
        $aQueryTable[] = [
            'table' => 'tl_content'
        ,   'query' => "SELECT c.id, c.cms_ci_clicks AS clicks, c.cms_ci_views AS views, c.cms_ci_reset AS reset, IFNULL(a.pid,0) AS page
                        FROM tl_content AS c
                            LEFT JOIN tl_article AS a ON (a.id=c.pid AND c.ptable='tl_article')
                        WHERE c.cms_ci_clicks>0 OR c.cms_ci_views>0"
        ];

        // tl_page -> cms_mi_views
        // tl_page -> cms_mi_reset
        $aQueryTable[] = [
            'table' => 'tl_page'
        ,   'query' => "SELECT id, cms_mi_views AS views, cms_mi_reset AS reset FROM tl_page WHERE cms_mi_views"
        ];


        $stmtInsertStatistic = $this->connection->prepare(
            "INSERT INTO tl_cms_statistic (pid, ptable, tstamp, clicks, views, start, page)
            VALUES (:pid, :ptable, " . time() . ", :clicks, :views, :start, :page)
        ");

        foreach( $aQueryTable as $queryTable ) {
            $stmtRows = $this->connection->prepare($queryTable['query']);
            $res = $stmtRows->execute();

            if( $res && DoctrineUtil::rowCount($res, $stmtRows) ) {

                $aRows = DoctrineUtil::fetchAll($res, $stmtRows);

                foreach( $aRows as $aRow ) {

                    $aStats = [
                        'pid' => $aRow['id'],
                        'ptable' => $queryTable['table'],
                        'clicks' => $aRow['clicks'] ?? 0,
                        'views' => $aRow['views'] ?? 0,
                        'start' => $aRow['reset'] ?? 0,
                        'page' => $aRow['page'] ?? 0,
                    ];

                    $stmtInsertStatistic->execute($aStats);
                }
            }
        }

        return $this->createResult(true);
    }
}
