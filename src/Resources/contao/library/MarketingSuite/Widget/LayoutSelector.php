<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\Widget;

use Contao\StringUtil;
use Contao\Widget;
use numero2\MarketingSuite\Backend;


class LayoutSelector extends Widget {


    protected $blnSubmitInput = true;
    protected $blnForAttribute = false;
    protected $strTemplate = 'be_widget';


    public function generate(): string {

        $value = StringUtil::specialchars($this->value);

        if( !$value && $this->options ) {
            $value = $this->options[0]['value'];
        }

        $aData = [
            'selected' => $value
        ,   'options' => $this->options
        ,   'name' => $this->name
        ,   'id' => $this->id
        ,   'class' => ($this->class ? ' ' . $this->class : '')
        ,   'sprite' => (!empty($this->arrConfiguration['sprite']) ? $this->arrConfiguration['sprite'] : '')
        ];

        return Backend::parseWithTemplate('backend/widgets/layout_selector', $aData);
    }
}
