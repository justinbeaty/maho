<?php

define('MAHO_ROOT_DIR', __DIR__);

require 'vendor/autoload.php';

Mage::init();


// Simple test
$obj = new Varien_Object([
    'id' => null,
]);
$obj['foo'] = 'bar';

var_dump($obj->serialize());
