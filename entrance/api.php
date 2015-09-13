<?php

    header('Content-Type:text/html;charset=utf-8');
    define('API_URL' , 'http://' . $_SERVER["HTTP_HOST"] . '/');
    include __DIR__ . '/../Core/Autoload.php';
    \Core\Autoload::init();

    if (isset($_GET['debug']))
    {
        ini_set('display_errors' , 1);
        dump($_GET , $_POST);
    }
//API版本号
    $api_version = I('api_version');
//根据版本号调度不同的API模块
    switch ($api_version)
    {
        default :
            $app = new \App\Api();
            break;
    }

    \Core\App::init($app);
    