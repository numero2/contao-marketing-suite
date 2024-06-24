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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\Database;


class CMSFormMigration extends AbstractMigration {


    /**
    * @var Contao\CoreBundle\Framework\ContaoFramework
     */
    private $framework;


    public function __construct( ContaoFramework $framework ) {

        $this->framework = $framework;
        $this->framework->initialize();
    }


    public function shouldRun(): bool {

        $oDB = NULL;
        $oDB = Database::getInstance();

        if( $oDB->tableExists('tl_content') ) {

            $oResult = $oDB->execute("SELECT COUNT(1) AS count FROM tl_content WHERE type='form' AND ptable='tl_cms_content_group'");
            $oResult->fetchAllAssoc(); // fetch result to clear internal query buffer

            if( $oResult && $oResult->count ) {
                return true;
            }

            if( $oDB->fieldExists('cms_mi_label', 'tl_content') ) {

                $oResult = $oDB->execute("SELECT COUNT(1) AS count FROM tl_content WHERE type='form' AND cms_mi_label!=''");
                $oResult->fetchAllAssoc(); // fetch result to clear internal query buffer

                if( $oResult && $oResult->count ) {
                    return true;
                }
            }
        }

        if( $oDB->tableExists('tl_cms_marketing_item') ) {

            $oResult = $oDB->execute("SELECT COUNT(1) AS count FROM tl_cms_marketing_item WHERE type='form'");
            $oResult->fetchAllAssoc(); // fetch result to clear internal query buffer

            if( $oResult && $oResult->count ) {
                return true;
            }
        }

        return false;
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
