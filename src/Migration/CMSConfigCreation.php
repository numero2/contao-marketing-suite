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
use Contao\CoreBundle\Migration\MigrationResult;


class CMSConfigCreation extends AbstractMigration {


    /**
     * @var string
     */
    private $projectDir;


    public function __construct( string $projectDir ) {

        $this->projectDir = $projectDir;
    }


    public function shouldRun(): bool {

        return !file_exists($this->projectDir . '/system/config/cmsconfig.php');
    }


    public function run(): MigrationResult {

        file_put_contents($this->projectDir . '/system/config/cmsconfig.php', "<?php\n\n### INSTALL SCRIPT START ###\n### INSTALL SCRIPT STOP ###\n");

        return $this->createResult(true);
    }
}
