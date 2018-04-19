<?php

namespace Mapbender\AlkisBundle\Component;

class Flurstueck
{
    public $land;
    public $gemarkung;
    public $flur;
    public $zaehler;
    public $nenner;

    public function __construct($land, $gemarkung, $flur, $zaehler, $nenner)
    {
        $this->land      = $land;
        $this->gemarkung = $gemarkung;
        $this->flur      = $flur;
        $this->zaehler   = $zaehler;
        $this->nenner    = $nenner;
    }

    public function getNumber()
    {
        return
            $this->land .
            $this->gemarkung .
            $this->fill($this->flur, 3) .
            $this->fill($this->zaehler, 5) .
            $this->fill($this->nenner, 4);
    }

    public function getAlkisNotation()
    {
        return
            $this->land .
            $this->gemarkung .
            $this->fill($this->flur, 3) .
            $this->fill($this->zaehler, 5) .
            $this->fill($this->nenner, 4) .
            '__';
    }

    public function getAlkNotation()
    {
        return
            $this->land .
            $this->gemarkung . '-' .
            $this->fill($this->flur, 3) . '-' .
            $this->fill($this->zaehler, 5) . '/' .
            $this->fill($this->nenner, 4);
    }

    public function __toString()
    {
        return
            $this->getNumber() . ' ' .
            $this->getAlkisNotation() . ' ' .
            $this->getAlkNotation() . ' ' .
            $this->land . ' ' .
            $this->gemarkung . ' ' .
            $this->land . $this->gemarkung . ' ' .
            $this->flur . ' ' .
            $this->fill($this->flur, 3) . ' ' .
            $this->zaehler . ' ' .
            $this->fill($this->zaehler, 5) . ' ' .
            $this->nenner . ' ' .
            $this->fill($this->nenner, 4)
        ;
    }

    private function fill($number, $length, $fill = '0')
    {
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }
}
