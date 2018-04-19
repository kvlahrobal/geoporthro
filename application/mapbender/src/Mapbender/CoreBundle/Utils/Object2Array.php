<?php

namespace Mapbender\CoreBundle\Utils;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of Object2Array
 *
 * @author Paul Schmidt
 */
class Object2Array
{

    protected $container;

    protected $em;

    protected $withIdent;

    protected $withAssoc;

    protected $processed;

    /**
     *
     * @param ContainerInterface $container container
     */
    private function __construct(ContainerInterface $container, $withIdent, $withAssoc)
    {
        $this->container = $container;
        $this->withIdent = $withIdent;
        $this->withAssoc = $withAssoc;
        $this->em = $this->container->get('doctrine')->getManager();
        $this->processed = array();
    }

    public static function object2Array(ContainerInterface $container, $object, $withIdent = false, $withAssoc = false)
    {
        $ob2arr = new Object2Array($container, $withIdent, $withAssoc);
        return $ob2arr->handleValue($object);
    }

    private function add($value)
    {
        if (is_object($value)) {
            $realClass = EntityUtil::getRealClass($value);
            if (!isset($this->processed[$realClass])) {
                $this->processed[$realClass] = array();
            }
            $found = false;
            foreach ($this->processed[$realClass] as $object) {
                if ($value === $object) {
                    $found = true;
                    break;
                }
            }
            if ($this->isAdded($value) === false) {
                $this->processed[$realClass][] = $value;
            }
        }
    }

    private function isAdded($value)
    {
        if (is_object($value)) {
            $realClass = EntityUtil::getRealClass($value);
            if (!isset($this->processed[$realClass])) {
                return false;
            }
            foreach ($this->processed[$realClass] as $object) {
                if ($value === $object) {
                    return true;
                }
            }
        }
        return null;
    }

    /**
     * Normalizes an array.
     *
     * @param array $array array
     * @return array normalized array
     */
    private function handleArray($array)
    {
        $result = array();
        if (ArrayUtil::isAssoc($array)) {
            foreach ($array as $key => $item) {
                $result[$key] = $this->handleValue($item);
            }
        } else {
            foreach ($array as $item) {
                $result[] = $this->handleValue($item);
            }
        }
        return $result;
    }

    private function handleValue($value)
    {
        if ($value === null || is_integer($value) || is_float($value) || is_string($value) || is_bool($value)) {
            return $value;
        } elseif (is_array($value)) {
            return $this->handleArray($value);
        } elseif (is_object($value)) {
            $realClass = EntityUtil::getRealClass($value);
            try {
                $this->em->getRepository($realClass);
                return $this->normalizeEntity($value, $this->em->getClassMetadata($realClass));
            } catch (MappingException $e) {
                return $this->normalizeObject($value, new \ReflectionClass($realClass));
            }
        } else {
            return 'unsupported';
        }
    }

    private function normalizeEntity($object, ClassMetadata $classMeta, $data = array())
    {
        if ($this->em->contains($object)) {
            $this->em->refresh($object);
        }
        foreach ($classMeta->getFieldNames() as $fieldName) {
            if (!in_array($fieldName, $classMeta->getIdentifier())
                && $getMethod = $this->getReturnMethod($fieldName, $classMeta->getReflectionClass())) {
                $data[$fieldName] = $this->handleValue($getMethod->invoke($object));
            } elseif ($this->withIdent && in_array($fieldName, $classMeta->getIdentifier())) {
                $data[$fieldName] = $this->handleValue($getMethod->invoke($object));
            }
        }
        if ($this->withAssoc) {
            // TODO
//        foreach ($classMeta->getAssociationMappings() as $assocItem) {
//            $fieldName = $assocItem['fieldName'];
//            if ($getMethod = $this->getReturnMethod($fieldName, $classMeta->getReflectionClass())) {
//                $subObject = $getMethod->invoke($object);
//                if (!$subObject) {
//                    $data[$fieldName] = null;
//                } elseif ($subObject instanceof PersistentCollection) {
//                    $data[$fieldName] = array();
//                    foreach ($subObject as $item) {
//                        $ident = $this->handleIdentParams($item);
//                        $data[$fieldName][] = $ident;
//                        $itemRealClass = EntityUtil::getRealClass($item);
//                        $itemClassMeta = $this->em->getClassMetadata($itemRealClass);
//                        if (!$this->isInProcess($ident, $itemClassMeta)) {
//                            $this->normalizeEntity($item, $itemClassMeta);
//                        }
//                    }
//                } else {
//                    $data[$fieldName] = $this->handleIdentParams($subObject);
//                    $subObjectRealClass = EntityUtil::getRealClass($subObject);
//                    $subObjectClassMeta = $this->em->getClassMetadata($subObjectRealClass);
//                    if (!$this->isInProcess($data[$fieldName], $subObjectClassMeta)) {
//                        $this->normalizeEntity($subObject, $subObjectClassMeta);
//                    }
//                }
//            }
//        }

        }
        return $data;
    }

    private function normalizeObject($object, \ReflectionClass $class, $data = array())
    {
        foreach ($class->getProperties() as $property) {
            if ($getMethod = $this->getReturnMethod($property->getName(), $class)) {
                $data[$property->getName()] = $this->handleValue($getMethod->invoke($object));
            }
        }
        return $data;
    }
    
    private function getReturnMethod($fieldName, \ReflectionClass $class)
    {
        $method = null;
        if ($method = $this->getMethodName($fieldName, 'get', $class)) {
            return $method;
        } elseif ($method = $this->getMethodName($fieldName, 'is', $class)) {
            return $method;
        } elseif ($method = $this->getMethodName($fieldName, 'has', $class)) {
            return $method;
        }
    }

    private function getMethodName($fieldName, $prefix, \ReflectionClass $class)
    {
        $methodHash = "";
        foreach (preg_split("/_/", $fieldName) as $chunk) {
            $chunk = ucwords($chunk);
            $methodHash .= $chunk;
        }
        if ($class->hasMethod($prefix . $methodHash)) {
            return $class->getMethod($prefix . $methodHash);
        } else {
            return null;
        }
    }
}
