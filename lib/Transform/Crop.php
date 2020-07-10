<?php

namespace Doka\Transform;
use Doka\Helper\Vector;

class Crop {
    public $center;
    public $zoom;
    public $rotation;
    public $flip;
    public function __construct($options) {

        $center = $options['center'];
        $flip = $options['flip'];

        $this->aspectRatio = $options['aspectRatio'];
        $this->rotation = $options['rotation'];
        $this->zoom = $options['zoom'];
        $this->center = isset($center) ? new Vector($center['x'], $center['y']) : new Vector(.5, .5);
        $this->flip = isset($flip) ? new Flip($flip['horizontal'], $flip['vertical']) : new Flip();
    }
}