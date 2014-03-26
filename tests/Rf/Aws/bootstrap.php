<?php 

define('ROOT_DIR', realpath(dirname(__FILE__) . '/../../../'));

require ROOT_DIR . '/vendor/autoload.php';
require ROOT_DIR . '/src/Rf/Aws/AutoLoader.php';

Rf\Aws\AutoLoader::register();