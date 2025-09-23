<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\CommandCompiler;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;


// Added with version 3.0.0
class CMSStatisticMigration extends AbstractMigration {


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

        if( $this->fieldHasValueInTable('clicks', 'tl_cms_content_group') ) {
            return true;
        }
        if( $this->fieldHasValueInTable('views', 'tl_cms_content_group') ) {
            return true;
        }

        if( $this->fieldHasValueInTable('cms_mi_views', 'tl_content') ) {
            return true;
        }

        if( $this->fieldHasValueInTable('cms_ci_clicks', 'tl_content') ) {
            return true;
        }
        if( $this->fieldHasValueInTable('cms_ci_views', 'tl_content') ) {
            return true;
        }

        if( $this->fieldHasValueInTable('cms_mi_views', 'tl_page') ) {
            return true;
        }

        return false;
    }


    public function run(): MigrationResult {

        // create tl_cms_statistic if not exist
        $aCommands = $this->commandCompiler->compileCommands();

        foreach( $aCommands as $command ) {
            if( preg_match('/^CREATE TABLE tl_cms_statistic /', $command) ) {
                $this->connection->executeStatement($command);
            }
        }

        $aQueryTable = [];

        // tl_cms_content_group -> clicks
        // tl_cms_content_group -> views
        $aQueryTable[] = [
            'table' => 'tl_cms_content_group'
        ,   'query' => "SELECT id, clicks, views FROM tl_cms_content_group WHERE clicks>0 OR views>0"
        ,   'drops' => ['clicks', 'views']
        ];

        // tl_content -> cms_mi_views
        $aQueryTable[] = [
            'table' => 'tl_content'
        ,   'query' => "SELECT id, cms_mi_views AS views FROM tl_content WHERE cms_mi_views>0"
        ,   'drops' => ['cms_mi_views']
        ];

        // tl_content -> cms_ci_clicks
        // tl_content -> cms_ci_views
        $aQueryTable[] = [
            'table' => 'tl_content'
        ,   'query' => "SELECT c.id, c.cms_ci_clicks AS clicks, c.cms_ci_views AS views, IFNULL(a.pid,0) AS page
                        FROM tl_content AS c
                            LEFT JOIN tl_article AS a ON (a.id=c.pid AND c.ptable='tl_article')
                        WHERE c.cms_ci_clicks>0 OR c.cms_ci_views>0"
        ,   'drops' => ['cms_ci_clicks', 'cms_ci_views']
        ];

        // tl_page -> cms_mi_views
        $aQueryTable[] = [
            'table' => 'tl_page'
        ,   'query' => "SELECT id, cms_mi_views AS views, id AS page FROM tl_page WHERE cms_mi_views>0"
        ,   'drops' => ['cms_mi_views']
        ];


        $stmtInsertStatistic = $this->connection->prepare(
            "INSERT INTO tl_cms_statistic (pid, ptable, tstamp, type, page)
            VALUES (:pid, :ptable, " . time() . ", :type, :page)"
        );

        $strDrop = "ALTER TABLE %s DROP %s";

        foreach( $aQueryTable as $queryTable ) {

            $hasFieldToDrop = false;
            foreach( $queryTable['drops'] as $field ) {
                $hasFieldToDrop |= $this->fieldHasValueInTable($field, $queryTable['table']);
            }

            if( !$hasFieldToDrop ) {
                continue;
            }

            $res = $this->connection
                ->prepare($queryTable['query'])
                ->executeQuery();

            if( $res && $res->rowCount() ) {

                $aRows = $res->fetchAllAssociative();

                foreach( $aRows as $aRow ) {

                    if( array_key_exists('views', $aRow) ) {
                        for( $i=0; $i < intval($aRow['views']); $i++ ) {
                            $aStats = [
                                'pid' => $aRow['id'],
                                'ptable' => $queryTable['table'],
                                'type' => 'view',
                                'page' => $aRow['page'] ?? 0,
                            ];
                            $stmtInsertStatistic->executeStatement($aStats);
                        }
                    }

                    if( array_key_exists('clicks', $aRow) ) {
                        for( $i=0; $i < intval($aRow['clicks']); $i++ ) {
                            $aStats = [
                                'pid' => $aRow['id'],
                                'ptable' => $queryTable['table'],
                                'type' => 'click',
                                'page' => $aRow['page'] ?? 0,
                            ];
                            $stmtInsertStatistic->executeStatement($aStats);
                        }
                    }
                }

                foreach( $queryTable['drops'] as $field ) {
                    $this->connection->executeQuery(sprintf($strDrop, $queryTable['table'], $field));
                }
            }
        }

        return $this->createResult(true);
    }


    /**
     * check if the field exist in the table and if it has at least one entry bigger than 0
     *
     * @param string $field
     * @param string $table
     *
     * @return bool
     */
    private function fieldHasValueInTable( string $field, string $table ): bool {

        $schemaManager = $this->connection->createSchemaManager();

        if( !$schemaManager->tablesExist([$table]) ) {
            return false;
        }

        $columns = $schemaManager->listTableColumns($table);

        if( !isset($columns[$field]) ) {
            return false;
        }

        $res = $this->connection
            ->prepare("SELECT $field FROM $table WHERE $field>0 LIMIT 1")
            ->executeQuery();

        if( $res && $res->rowCount() ) {
            return true;
        }

        return false;
    }
}
