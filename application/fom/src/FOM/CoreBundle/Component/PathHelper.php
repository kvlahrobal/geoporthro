<?php

/**
 * Created by Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>.
 * Edited by Christian Wygoda
 *
 * Date: 21.05.14
 * Time: 15:14
 */

namespace FOM\CoreBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Templating\TemplateReference;

/**
 * PathHelper service class
 *
 * Usage example:
 *
 *     $this->container->get('fom.pathhelper')->getBundleWebPath()
 */
class PathHelper
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get bundle web assets absolute path
     *
     * @param string    $uri    Path to concatenate with the result path
     * @param bool      $create Create directory if not exists
     *
     * @return string Absolute path
     */
    public function getBundleWebPath($uri = "/", $create = true)
    {
        return $this->getBundlePath('/../web/', $uri, $create);
    }

    /**
     * Get bundle data absolute path
     *
     * @param string    $uri    Path to concatenate with the result path
     * @param bool      $create Create directory if not exists
     *
     * @return string Absolute path
     */
    public function getBundleDataPath($uri = "/", $create = true)
    {
        return $this->getBundlePath('/../data/', $uri, $create);
    }

    /**
     * Get bundle web relative path
     *
     * @return string Relative Path
     */
    private function getBundleRelativePath() {
        /**
         * @var $request           Request
         */
        $request = $this->container->get("request");
        preg_match_all('/[^\\\\]+/', $request->attributes->get('_controller'), $matches);

        if (is_array($matches)) {
            $names      = & $matches[0];
            $bundleName = $names[0] . $names[1];
        } else {
            $bundleName = str_replace('\\', '', $request->attributes->get('_template')->get('bundle'));
        }

        $bundlePath = strtolower(preg_replace('/Bundle$/', '', $bundleName));

        return "bundles/" . $bundlePath;
    }

    /**
     * Get bundle data path
     */
    private function getBundlePath($path, $uri, $create = true)
    {
        /** @var  $kernel Kernel */

        $kernel = $this->container->get('kernel');
        $path   = $kernel->getRootDir() . $path . $this->getBundleRelativePath($this->container) . $uri;
        if ($create && !is_dir($path)) {
            mkdir($path, 0770, true);
        }
        return $path;
    }
}
