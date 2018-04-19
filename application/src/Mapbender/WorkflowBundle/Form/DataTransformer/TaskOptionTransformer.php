<?php

namespace Mapbender\WorkflowBundle\Form\DataTransformer;

use Mapbender\CoreBundle\Utils\ArrayObject;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class TaskOptionTransformer transforms a values between different representations
 *
 * @author Paul Schmidt
 */
class TaskOptionTransformer implements DataTransformerInterface
{

    /**
     * Transforms an object to an array.
     *
     * @param mixed $data object | array
     * @return array a transformed object
     */
    public function transform($data = null)
    {
        if (!$data) {
            return null;
        }
        $data->setTask(null);
        $taskOptionArr = ArrayObject::objectToArray($data);
        return $taskOptionArr;
    }

    /**
     * Transforms an array into an object
     *
     * @param array $data array with data for an object of the 'classname'
     * @return object of the 'classname'
     */
    public function reverseTransform($data)
    {
        if (null === $data) {
            return "";
        }
        unset($data['task']);
        $obj = ArrayObject::arrayToObject("Mapbender\WorkflowBundle\Entity\TaskOption", $data);
        return $obj;
    }
}
