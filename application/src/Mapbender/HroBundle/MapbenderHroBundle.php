<?php

namespace Mapbender\HroBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * HRO Bundle
 */
class MapbenderHroBundle extends MapbenderBundle
{
    /**
     * @inheritdoc
     */
    public function getTemplates()
    {
        return array(
            'Mapbender\HroBundle\Template\Desktop',
            'Mapbender\HroBundle\Template\Mobile',
        );
    }
    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\HroBundle\Element\BaseSourceSwitcher',
            'Mapbender\HroBundle\Element\BaseSourceSwitcherDisplay',
        );
    }
}
