<?php
ini_set('display_errors',true);
include __DIR__.'/../Core/Autoload.php';
\Core\Autoload::init();
$webApp = new \App\Web();

\Core\App::init($webApp);
