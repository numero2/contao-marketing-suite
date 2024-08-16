<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;


return (new Configuration())
    ->ignoreErrorsOnPackage('contao/manager-plugin', [ErrorType::DEV_DEPENDENCY_IN_PROD])

    // ignore classes these will be checked during runtime
    // contao/calendar-bundle
    ->ignoreUnknownClasses([
        'Contao\CalendarBundle\ContaoCalendarBundle',
        'Contao\Events',
        'Contao\CalendarEventsModel',
        'Contao\CalendarModel',
    ])
    // contao/news-bundle
    ->ignoreUnknownClasses([
        'Contao\NewsBundle\ContaoNewsBundle',
        'Contao\News',
        'Contao\NewsArchiveModel',
        'Contao\NewsModel',
    ])
    // jeremykendall/php-domain-parser
    ->ignoreUnknownClasses([
        'Pdp\Parser',
        'Pdp\PublicSuffixListManager',
    ])
;