<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2022 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2022 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\BackendModule;

use Contao\Backend as CoreBackend;
use Contao\BackendModule as CoreBackendModule;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\Date;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as mepdohi;


class NewsSchedule extends CoreBackendModule {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'backend/modules/news_schedule';


    /**
     * Generate the module
     */
    protected function compile() {

        $this->loadLanguageFile('tl_news');
        mepdohi::geoguzo();

        $currArchive = Input::get('id') ?: null;

        if( !mepdohi::hasFeature('news_schedule') ||
            !( (!$currArchive && mepdohi::hasFeature('news_schedule_multiple')) || ($currArchive && mepdohi::hasFeature('news_schedule_single')) ) ) {

            throw new AccessDeniedException('This feature is not included in your Marketing Suite package.');
        }

        // add new news button
        if( $currArchive ) {
            $this->Template->newButton = '<a href="'.CoreBackend::addToUrl('act=create&amp;mode=2&amp;pid='.$currArchive, true, ['key']).'" class="header_new" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['tl_news']['new'][1]).'" accesskey="n" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['tl_news']['new'][0].'</a>';
        }
        $this->Template->backButton = '<a href="'.CoreBackend::addToUrl('', true, ['key']).'" class="header_back" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>';

        $month = Date::parse('m');
        $year = Date::parse('Y');
        if( !empty($_GET['month']) && !empty($_GET['year']) ) {

            $month = Input::get('month');
            $year = Input::get('year');
        }

        $time = strtotime( $year.'-'.$month.'-01' );

        // find important days
        $firstDay = strtotime('first day of this month 00:00', $time);
        $lastDay = strtotime('last day of this month 23:59', $time);

        // navigation next / previous month
        $this->Template->previous = CoreBackend::addToUrl("month=".Date::parse('m', $firstDay-86400)."&year=".Date::parse('Y', $firstDay-86400));
        $this->Template->next = CoreBackend::addToUrl("month=".Date::parse('m', $lastDay+86400)."&year=".Date::parse('Y', $lastDay+86400));

        // weekdays
        $headings = $GLOBALS['TL_LANG']['DAYS_SHORT'];
        array_push($headings, array_shift($headings));

        // full day list
        $todayStart = strtotime('00:00:00');
        $currentMonth = Date::parse('n', $firstDay);
        $rows = [];

        for( $iDay = $firstDay-7*86400; $iDay < $lastDay+7*86400 ; $iDay+=86400 ) {

            $week = Date::parse('W', $iDay);
            $dayIndex = Date::parse('w', $iDay);
            $day = Date::parse('j', $iDay);
            $monthIndex = Date::parse('n', $iDay);

            $rows[$week][$dayIndex] = ['day' => $day, 'class' => [] ];

            // add classes
            if( $monthIndex !== $currentMonth ) {
                $rows[$week][$dayIndex]['class'][] = 'otherMonth';
            }

            if( $todayStart <= $iDay && $iDay < $todayStart+86400 ) {
                $rows[$week][$dayIndex]['selected'] = true;
                $rows[$week][$dayIndex]['class'][] = 'today';
            }

            $rows[$week][$dayIndex]['class'] = implode(' ', $rows[$week][$dayIndex]['class']);
            $rows[$week][$dayIndex]['date'] = $iDay;
        }

        // remove unused weeks
        unset($rows[array_keys($rows)[0]]);
        unset($rows[array_reverse(array_keys($rows))[0]]);

        // get news for the given period
        $objResult = null;
        $objResult = Database::getInstance()->prepare("
            SELECT *
            FROM tl_news AS n
            WHERE
                (
                    ((?<=n.start AND n.start<?) OR (?<=n.date AND n.date<? AND n.start='')) OR
                    (n.date >= ? AND n.date <= ?)
                ) " . ($currArchive ? " AND n.pid = ?":"") ."
        ")->execute( $firstDay, $lastDay, $firstDay, $lastDay, $firstDay, $lastDay, ($currArchive?$currArchive:null) );

        $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

        while( $objResult->next() ) {

            $dayInCal = $objResult->start?:$objResult->date;

            $week = Date::parse('W', $dayInCal);
            $dayIndex = Date::parse('w', $dayInCal);

            if( !empty($rows[$week][$dayIndex]) ) {

                $data = $objResult->row();

                $data['class'] = 'unpublished';
                $data['facebook'] = false;

                if( $data['published'] && (empty($data['start']) || $data['start'] <= time()) && (empty($data['stop']) || time() <= $data['stop']) ) {
                    $data['class'] = 'published';
                }

                if( $data['published'] && (!empty($data['start']) && $data['start'] > time()) && (empty($data['stop']) || time() <= $data['stop']) ) {
                    $data['class'] = 'pending';
                }
                if( $data['cms_publish_facebook'] ) {
                    $data['facebook'] = true;
                }

                $editLabel = is_array($GLOBALS['TL_LANG']['tl_news']['edit'])?$GLOBALS['TL_LANG']['tl_news']['edit'][1]:$GLOBALS['TL_LANG']['tl_news']['edit'];
                $data['title'] = sprintf($editLabel, $data['id']);
                $data['facebookTitle'] = $GLOBALS['TL_LANG']['tl_news']['cms_publish_facebook'][1];

                if( !mepdohi::hasFeature('news_schedule_show_'.$data['class']) ) {
                    continue;
                }

                $data['href'] = $routePrefix . '?do=news&table=tl_content&id='.$data['id'];

                $rows[$week][$dayIndex]['elements'][] = Backend::parseWithTemplate('backend/modules/news_schedule_entry', $data);
            }
        }

        $this->Template->month = $month;
        $this->Template->year = $year;

        $this->Template->firstDay = $firstDay;
        $this->Template->lastDay = $lastDay;
        $this->Template->headings = $headings;
        $this->Template->rows = $rows;

        $this->Template->legends = [
            'unpublished' => $GLOBALS['TL_LANG']['tl_news']['cms_schedule_legends']['unpublished']
        ,   'published' => $GLOBALS['TL_LANG']['tl_news']['cms_schedule_legends']['published']
        ,   'pending' => $GLOBALS['TL_LANG']['tl_news']['cms_schedule_legends']['pending']
        ];
    }
}
