<?php

namespace ArsGeografica\Signing\Tests;

use JMS\Serializer\Annotation\Type;


class TestClass
{
    /**
     * @Type("DateTime")
     */
    protected $when;

    /**
     * @Type("array")
     */
    protected $data;


    public function setWhen($when)
    {
        $this->when = $when;
        return $this;
    }

    public function getWhen()
    {
        return $this->when;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function toString()
    {
        return $this->getWhen()->format('c') . ' ' . implode(',', $this->getData());
    }

    public function equals($other)
    {

        return $this->toString() === $other->toString();
    }
}
