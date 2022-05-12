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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\Database;
use Contao\PageModel;
use Exception;
use numero2\MarketingSuite\Api\MarketingSuite as API;


class CMSLicenseMigration extends AbstractMigration {


    /**
     * @var Contao\CoreBundle\Framework\ContaoFramework;
     */
    private $framework;


    public function __construct( ContaoFramework $framework ) {

        $this->framework = $framework;
        $this->framework->initialize();
    }


    public function shouldRun(): bool {

        $oDB = Database::getInstance();

        if( $oDB->tableExists('tl_page') ) {

            $oPages = PageModel::findByType('root');
            $licenseVersion = CMSConfig::get('license_version');

            if( $oPages && version_compare(CMS_VERSION, $licenseVersion, '>') ) {

                foreach( $oPages as $oPage ) {

                    if( !empty($oPage->cms_root_license) && !empty($oPage->dns) ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }


    public function run(): MigrationResult {

        $oDB = Database::getInstance();

        if( $oDB->tableExists('tl_page') ) {

            $oPages = PageModel::findByType('root');

            if( $oPages ) {

                foreach( $oPages as $oPage ) {

                    if( !empty($oPage->cms_root_license) && !empty($oPage->dns) ) {

                        if( $oPage ) {

                            $oAPI = new API();

                            try {

                                if( $oAPI->checkLicense($oPage->cms_root_license, $oPage) ) {
                                    $oAPI->getFeatures($oPage->cms_root_license, $oPage);
                                }

                            } catch( Exception $e ) {
                            }
                        }
                    }
                }
            }

            CMSConfig::set('license_version', CMS_VERSION);
            CMSConfig::persist('license_version', CMS_VERSION);
        }

        return $this->createResult(true);
    }
}
