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

use Contao\CMSConfig;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\Database;
use numero2\MarketingSuite\Backend\License;


class CMSTestmodeMigration extends AbstractMigration {


    /**
     * @var Contao\CoreBundle\Framework\ContaoFramework
     */
    private $framework;


    public function __construct( ContaoFramework $framework ) {

        $this->framework = $framework;
        $this->framework->initialize();
    }


    public function shouldRun(): bool {

        // $oDB = NULL;
        // $oDB = Database::getInstance();
        //
        // if( !CMSConfig::get('testmode') && (!$oDB->tableExists('tl_page') || !$oDB->fieldExists('cms_root_license', 'tl_page') || License::hasNoLicense()) ) {
        //     return true;
        // }

        return false;
    }


    public function run(): MigrationResult {

        CMSConfig::set('testmode', '1');
        CMSConfig::persist('testmode', '1');

        return $this->createResult(true);
    }
}
