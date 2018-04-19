<?php
namespace Mapbender\MapToolBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * Mapbender map tool bundle
 *
 */
class MapbenderMapToolBundle extends MapbenderBundle
{
    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
//            "Mapbender\MapToolBundle\Element\MapCoordinate",
            "Mapbender\MapToolBundle\Element\SearchCoordinate",
            "Mapbender\MapToolBundle\Element\ClickCoordinate"
        );
    }
}

