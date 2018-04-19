<?php

namespace Mapbender\HroBundle\Element;

use Mapbender\CoreBundle\Component\Element;

class BaseSourceSwitcherDisplay extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "HRO BaseSourceSwitcherDisplay";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "Changes the url in common with the map or a group of maps.";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array(
            'js' => array(
                '@MapbenderHroBundle/Resources/public/mapbender.element.basesourceswitcherdisplay.js'
            ),
            'css' => array(
                '@MapbenderHroBundle/Resources/public/sass/element/basesourceswitcherdisplay.scss'
            )
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'some element',
            'tooltip' => 'tooltip',
            'target' => null);
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbBaseSourceSwitcherDisplay';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\HroBundle\Element\Type\BaseSourceSwitcherDisplayAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderHroBundle:ElementAdmin:basesourceswitcherdisplay.html.twig';
    }

    public function render()
    {
        return $this->container->get('templating')->render(
            'MapbenderHroBundle:Element:basesourceswitcherdisplay.html.twig',
            array(
                'id' => $this->getId(),
                'configuration' => $this->entity->getConfiguration(),
                'title' => $this->getTitle())
        );
    }
}
