<?php

namespace Doka\Helper;

/**
 * Class Vector
 * @package Doka\Helper
 *
 * @property $x
 * @property $y
 */
class Vector {

    private $x;
    private $y;

    public function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __toString() {
        return '<br>x: ' . $this->x . '<br>y: ' . $this->y . '<br>';
    }
}
