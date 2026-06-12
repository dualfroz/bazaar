<?php

use Bazaar\Core\App;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = new App();
$app->boot();
$app->run();
