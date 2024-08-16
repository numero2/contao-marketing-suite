<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Input;
use Contao\ModuleModel;
use numero2\MarketingSuite\Backend\License as wpqrkl;


class StatisticListener {


    private $modules;


    /**
     * setup the dca to be used for the search statistic
     *
     * @Callback(table="tl_cms_statistic", target="config.onload")
     */
    public function setupSearchStatistic( $dc ): void {

        if( Input::get('do') !== "cms_tools" || Input::get('mod') !== "search_statistic" || Input::get('table') !== "tl_cms_statistic" ) {
            return;
        }

        if( !wpqrkl::hasFeature('search_statistic') ) {
            return;
        }

        // set filter
        $GLOBALS['TL_DCA']['tl_cms_statistic']['list']['sorting']['filter'][] = ['type=?', 'search'];

        // config remove ptable settings to show all entries given by filter above
        unset($GLOBALS['TL_DCA']['tl_cms_statistic']['config']['ptable']);
        unset($GLOBALS['TL_DCA']['tl_cms_statistic']['config']['dynamicPtable']);
        $dc->ptable = null;

        // list
        $GLOBALS['TL_DCA']['tl_cms_statistic']['list']['label']['fields'] = ['keywords', 'url', 'pid'];
        $GLOBALS['TL_DCA']['tl_cms_statistic']['list']['label']['label_callback'] = [self::class, 'searchStatisticLabels'];

        // fields
        $GLOBALS['TL_DCA']['tl_cms_statistic']['fields']['type']['filter'] = false;

        $GLOBALS['TL_DCA']['tl_cms_statistic']['fields']['pid']['filter'] = true;
        $GLOBALS['TL_DCA']['tl_cms_statistic']['fields']['pid']['foreignKey'] = 'tl_module.CONCAT(name, " [ID " ,id, "]")';

    }


    /**
     * Replace module id with its name and id
     *
     * @param array $row
     * @param string $label
     * @param Contao\DataContainer $dc
     * @param array $labels
     *
     * @return array
     */
    public function searchStatisticLabels( array $row, string $label, DataContainer $dc, array $labels ): array {

        $aModules = [];
        if( empty($this->modules) ) {
            $oModules = ModuleModel::findAll();
            if( $oModules ) {
                $aModules = $oModules->fetchEach('name');
            }
            $this->modules = $aModules;
        } else {
            $aModules = $this->modules;
        }

        $fieldName = 'pid';
        $fields = $GLOBALS['TL_DCA'][$dc->table]['list']['label']['fields'];
        $key = array_search($fieldName, $fields, true);

        if( !empty($labels[$key]) && array_key_exists($labels[$key], $aModules) ) {
            $labels[$key] = $aModules[$labels[$key]] . ' [ID ' . $labels[$key] . ']';
        }

        return $labels;
    }
}
