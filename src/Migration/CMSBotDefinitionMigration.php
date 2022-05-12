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


class CMSBotDefinitionMigration extends AbstractMigration {


    /**
     * @var Contao\CoreBundle\Framework\ContaoFramework
     */
    private $framework;

    /**
     * @var array
     */
    private $aBotAgents;


    public function __construct( ContaoFramework $framework ) {

        $this->framework = $framework;
        $this->framework->initialize();

        $this->aBotAgents = json_decode(file_get_contents(TL_ROOT.'/vendor/numero2/contao-marketing-suite/src/Resources/vendor/crawler-user-agents/crawler-user-agents.json'), true);
    }


    public function shouldRun(): bool {

        $oDB = NULL;
        $oDB = Database::getInstance();

        $fileMtime = filemtime(TL_ROOT.'/vendor/numero2/contao-marketing-suite/src/Resources/vendor/crawler-user-agents/crawler-user-agents.json');
        $mtime = CMSConfig::get('bot_agents_mtime');

        if( $mtime && $mtime >= $fileMtime ) {
            return false;
        }

        if( $oDB->tableExists('tl_cms_link_shortener_statistics') && !empty($this->aBotAgents) ) {

            $oResult = $oDB->execute("SELECT DISTINCT user_agent FROM tl_cms_link_shortener_statistics WHERE is_bot!='1' AND browser='other'");

            if( $oResult ) {

                $aResult = $oResult->fetchAllAssoc();

                foreach( $aResult as $row ) {
                    foreach( $this->aBotAgents as $aBotAgent ) {

                        if( preg_match('/'.$aBotAgent['pattern'].'/', $row['user_agent']) ) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }


    public function run(): MigrationResult {

        $oDB = NULL;
        $oDB = Database::getInstance();

        if( $oDB->tableExists('tl_cms_link_shortener_statistics') && !empty($this->aBotAgents) ) {

            $oResult = $oDB->execute("SELECT DISTINCT user_agent FROM tl_cms_link_shortener_statistics WHERE is_bot!='1' AND browser='other'");

            if( $oResult ) {

                $aResult = $oResult->fetchAllAssoc();

                foreach( $aResult as $row ) {
                    foreach( $this->aBotAgents as $aBotAgent ) {

                        if( preg_match('/'.$aBotAgent['pattern'].'/', $row['user_agent']) ) {
                            $oResult = $oDB->prepare("UPDATE tl_cms_link_shortener_statistics SET is_bot='1' where user_agent=?")
                                ->execute($row['user_agent']);
                        }
                    }
                }
            }
        }

        $fileMtime = filemtime(TL_ROOT.'/vendor/numero2/contao-marketing-suite/src/Resources/vendor/crawler-user-agents/crawler-user-agents.json');

        CMSConfig::set('bot_agents_mtime', $fileMtime);
        CMSConfig::persist('bot_agents_mtime', $fileMtime);

        return $this->createResult(true);
    }
}
