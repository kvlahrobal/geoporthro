<?php

namespace Mapbender\AlkisBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

class MapbenderAlkisBundle extends MapbenderBundle
{
    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\AlkisBundle\Element\BaseSearchOne',
            'Mapbender\AlkisBundle\Element\BaseSearchTwo',
            'Mapbender\AlkisBundle\Element\ThematicSearchOne',
            'Mapbender\AlkisBundle\Element\AlkisInfo',
        );
    }
}
