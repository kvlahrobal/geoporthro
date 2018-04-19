<?php

namespace FOM\ManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Generic Manager interface controller.
 *
 * Provides module list and menu for manager interface
 *
 * @author Christian Wygoda
 */
class ManagerController extends Controller
{
    /**
     * Simply redirect to the applications list.
     *
     * @Route("/")
     * @Method("GET")
     */
    public function indexAction()
    {
        $controllers = $this->getManagerControllersDefinition();
        return $this->redirect($this->generateUrl($controllers[0]['route']));
    }

    /**
     * Renders the navigation menu
     *
     * @Template
     */
    public function menuAction($request)
    {
        $current_route = $request->attributes->get('_route');
        $menu          = $this->getManagerControllersDefinition();

        $this->setActive($menu, $current_route);

        return array('menu' => $menu);
    }

    private function setActive(&$routes, $currentRoute) {
        if(empty($routes)) return false;

        $return = false;

        foreach ($routes as &$route) {
            if($currentRoute === $route['route']) {
                $route['active'] = true;
                $return = true;
            }

            if(isset($route['subroutes']) && $this->setActive($route['subroutes'], $currentRoute)) {
                $route['active'] = true;
                $return = true;
            }
        }

        return $return;
    }

    protected function pruneSubroutes(&$container)
    {
        $securityContext = $this->get('security.context');
        if(is_array($container) && array_key_exists('subroutes', $container)) {
            foreach($container['subroutes'] as $idx2 => &$route) {
                if(array_key_exists('enabled', $route)) {
                    $closure = $route['enabled'];
                    if(!$closure($securityContext)) {
                        unset($container['subroutes'][$idx2]);
                        continue;
                    }
                }
                $this->pruneSubroutes($route);
            }
        }
    }

    protected function getManagerControllersDefinition()
    {
        $manager_controllers = array();
        $securityContext = $this->get('security.context');
        foreach($this->get('kernel')->getBundles() as $bundle) {
            if(is_subclass_of($bundle, 'FOM\ManagerBundle\Component\ManagerBundle')) {
                $controllers = $bundle->getManagerControllers();
                if($controllers) {
                    foreach($controllers as $idx => &$controller) {
                        // Remove disabled main routes
                        if(array_key_exists('enabled', $controller)) {
                            $closure = $controller['enabled'];
                            if(!$closure($securityContext)) {
                                unset($controllers[$idx]);
                                continue;
                            }
                        }
                        $this->pruneSubroutes($controllers[$idx]);
                    }
                    $manager_controllers = array_merge($manager_controllers, $controllers);
                }
            }
        }

        usort($manager_controllers, function($a, $b) {
            if($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($a['weight'] < $b['weight']) ? -1 : 1;
        });

        if(count($manager_controllers) === 0) {
            throw new \RuntimeException('No manager controllers registered');
        }

        return $manager_controllers;
    }
}

