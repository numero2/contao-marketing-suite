<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2020 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuiteBundle\Migration;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\Database;


class CMSFormMigration extends AbstractMigration {


    /**
    * @var Contao\CoreBundle\Framework\ContaoFramework;
     */
    private $framework;


    public function __construct( ContaoFramework $framework ) {

        $this->framework = $framework;
        $this->framework->initialize();
    }


    public function shouldRun(): bool {

        $oDB = NULL;
        $oDB = Database::getInstance();

        $shouldRun = false;

        if( $oDB->tableExists('tl_content') ) {

            $oResult = $oDB->execute("SELECT COUNT(1) AS count FROM tl_content WHERE type='form' AND ptable='tl_cms_content_group'");

            if( $oResult && $oResult->count ) {
                $shouldRun = true;
            }

            if( $oDB->fieldExists('cms_mi_label', 'tl_content') ) {
                $oResult = $oDB->execute("SELECT COUNT(1) AS count FROM tl_content WHERE type='form' AND cms_mi_label!=''");

                if( $oResult && $oResult->count ) {
                    $shouldRun = true;
                }
            }
        }

        if( $oDB->tableExists('tl_cms_marketing_item') ) {
            $oResult = $oDB->execute("SELECT COUNT(1) AS count FROM tl_cms_marketing_item WHERE type='form'");

            if( $oResult && $oResult->count ) {
                $shouldRun = true;
            }
        }

        return $shouldRun;
    }


    public function run(): MigrationResult {

        $oDB = NULL;
        $oDB = Database::getInstance();

        if( $oDB->tableExists('tl_content') ) {

            $oDB->execute("UPDATE tl_content SET type='cms_form' WHERE type='form' AND ptable='tl_cms_content_group'");

            if( $oDB->fieldExists('cms_mi_label', 'tl_content') ) {
                $oDB->execute("UPDATE tl_content SET type='cms_form' WHERE type='form' AND cms_mi_label!=''");
            }
        }

        if( $oDB->tableExists('tl_cms_marketing_item') ) {
            $oDB->execute("UPDATE tl_cms_marketing_item SET type='cms_form' WHERE type='form'");
        }

        return $this->createResult(true);
    }
}
