<?php
header('Content-Type:text/html;charset=utf-8');
include __DIR__.'/../Core/Autoload.php';
\Core\Autoload::init();
$app = new \App\Callback();
\Core\App::init($app);