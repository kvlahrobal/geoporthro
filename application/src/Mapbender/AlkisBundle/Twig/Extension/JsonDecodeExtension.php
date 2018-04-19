<?php

namespace Mapbender\AlkisBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

class JsonDecodeExtension extends \Twig_Extension {
    public function getFilters() {
        return array(
            'json_decode' => new \Twig_Filter_Method($this, 'decode', array('is_safe' => array('html')))
        );
    }

    public function getName() {
        return 'json_decode_extension';
    }

    public function decode($var) {
        return json_decode($var);
    }
}
