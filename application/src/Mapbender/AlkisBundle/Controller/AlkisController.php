<?php

/**
 * TODO: License
 */

namespace Mapbender\AlkisBundle\Controller;

use Mapbender\CoreBundle\Component\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * PostNas controller.
 *
 * @author Paul Schmidt
 */
class AlkisController extends Controller
{

    /**
     * Calls alkis info images.
     *
     * @Route("/{slug}/element/{id}/{action}/{order}/{script}")
     */
    public function assetAction($slug, $id, $action, $order, $script)
    {
        if ($order === 'ico' || $order === 'pic') {
            $url = $this->getAlkisUrl() . $order . "/" . $script;
            return $this->redirect($url);
        } else {
            return new Response("", 404);
        }
    }

    /**
     * Main application controller.
     *
     * @Route("/{slug}/element/{id}/{action}/{script}.{extension}")
     */
    public function scriptAction($slug, $id, $action, $script, $extension)
    {
        if (strtolower($extension) === 'php') {
            $path = array(
                '_controller' => "MapbenderCoreBundle:Application:element",
                "slug" => $slug,
                "id" => $id,
                "action" => $action
            );
            $params = $this->container->get('request')->query->all();
            $params['__script__'] = $script . "." . $extension;
            $subRequest = $this->container->get('request')->duplicate($params, null, $path);
            return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        } else {
            $url = $this->getAlkisUrl() . $script . "." . $extension;
            return $this->redirect($url);
        }
    }

    private function getAlkisUrl()
    {
        $base = Application::getBaseUrl($this->container);
        $bundle = $base . "/bundles/mapbenderalkis";
        $alkis = $bundle . '/info/alkis/';
        return $alkis;
    }

}
