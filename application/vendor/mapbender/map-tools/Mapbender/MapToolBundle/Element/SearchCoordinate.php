<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\MapToolBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Description of SearchCoordinate
 *
 * @author Paul Schmidt
 */
class SearchCoordinate extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.maptool.searchcoordinate.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.maptool.searchcoordinate.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            'mb.maptool.searchcoordinate.tag.coordinate',
            'mb.maptool.searchcoordinate.tag.map'
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'type' => null,
            'target' => null,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbMapCoordinate';
    }


    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\MapToolBundle\Element\Type\InputOutputCoordinateAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderMapToolBundle:ElementAdmin:inputoutputcoordinate.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderMapToolBundle/Resources/public/mapbender.element.mapcoordinate.js',
                '@MapbenderMapToolBundle/Resources/public/mapbender.container.info.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js'
            ),
            'css' => array('@MapbenderMapToolBundle/Resources/public/sass/element/mapbender.element.mapcoordinate.scss'),
            'trans' => array('MapbenderMapToolBundle:Element:searchcoordinate.json.twig')
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render(
                    'MapbenderMapToolBundle:Element:searchcoordinate.html.twig',
                    array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration()
                    )
        );
    }
}
