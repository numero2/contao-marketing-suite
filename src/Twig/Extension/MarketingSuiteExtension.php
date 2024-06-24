<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\Twig\Extension;

use numero2\MarketingSuite\Helper\Tag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;


final class MarketingSuiteExtension extends AbstractExtension {


    public function getFunctions(): array {

        return [
            new TwigFunction(
                'cms_tag_accepted',
                [Tag::class, 'isAccepted'],
            ),
            new TwigFunction(
                'cms_tag_not_accepted',
                [Tag::class, 'isNotAccepted'],
            ),
        ];
    }
}