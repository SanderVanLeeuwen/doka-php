<?php

namespace Doka\Transform;

class Flip {
    public $horizontal;
    public $vertical;
    public function __construct($horizontal = false, $vertical = false) {
        $this->horizontal = $horizontal;
        $this->vertical = $vertical;
    }
}