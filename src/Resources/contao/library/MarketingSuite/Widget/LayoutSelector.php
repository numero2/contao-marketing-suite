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


namespace numero2\MarketingSuite\Widget;

use Contao\StringUtil;
use Contao\Widget;
use numero2\MarketingSuite\Backend;


class LayoutSelector extends Widget {


    protected $blnSubmitInput = true;
    protected $blnForAttribute = false;
    protected $strTemplate = 'be_widget';


    public function generate(): string {

        $aData = [
            'selected' => StringUtil::specialchars($this->value)
        ,   'options' => $this->options
        ,   'name' => $this->name
        ,   'id' => $this->id
        ,   'class' => ($this->class ? ' ' . $this->class : '')
        ,   'sprite' => (!empty($this->arrConfiguration['sprite']) ? $this->arrConfiguration['sprite'] : '')
        ];

        return Backend::parseWithTemplate('backend/widgets/layout_selector', $aData);
    }
}