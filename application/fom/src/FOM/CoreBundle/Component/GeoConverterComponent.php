<?php
/**
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 24.02.2015 by WhereGroup GmbH & Co. KG
 */

namespace FOM\CoreBundle\Component;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GeoConverterComponent
 *
 * @package   FOM\CoreBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class GeoConverterComponent implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Convert WKT results to GeoJSON
     *
     * @param array  $rows
     * @param string $key WKT Key
     * @throws \exception
     *
     * @return array
     */
    public function wktsToGeoJson($rows, $key = 'GEOM')
    {
        foreach ($rows as &$row) {
            $row = $this->wktToGeoJson($row, $key);
        }
        return $rows;
    }

    /**
     * Convert WKT row to GeoJSON
     *
     * @param $key
     * @param $row
     * @return array
     * @throws \exception
     */
    public function wktToGeoJson(&$row, $key = 'GEOM')
    {
        $wkt = &$row[$key];
        unset($row[$key]);
        return array('type'       => 'Feature',
                     'properties' => $row,
                     'geometry'   => json_decode(\geoPHP::load($wkt, 'wkt')->out('json'), true
                     ));
    }

    public function wktResultsToFeatureCollection(&$rows, $properties = array(), $jsonEncode = false, $key = "GEOM")
    {
        foreach ($rows as &$row) {
            $row = $this->wktToGeoJson($row, $key);
        }

        $featureCollectionJson = array("properties" => $properties,
                                       "type"       => "FeatureCollection",
                                       "features"   => $rows);
        if ($jsonEncode) {
            $featureCollectionJson = json_encode($featureCollectionJson);
        }

        return $featureCollectionJson;

    }
}