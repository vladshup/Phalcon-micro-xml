<?php
use Phalcon\Mvc\Micro;
use Phalcon\Db\Adapter\Pdo\Mysql as MysqlAdapter;

$app = new Micro();

$loader = new \Phalcon\Loader();

$loader->registerDirs(
    array(
        __DIR__ . '/models/',
        __DIR__ . '/lib/'
    )
)->register();




$app['view'] = function () {
    $view = new \Phalcon\Mvc\View\Simple();
    $view->setViewsDir('views/');
    return $view;
};

// Установка сервиса базы данных
$app['db'] = function () {
    return new MysqlAdapter(
        array(
            "host"     => "localhost",
            "username" => "ya_phalcon",
            "password" => "uv3qfm",
            "dbname"   => "ya_phalcon"
        )
    );
};

$app['view'] = function () {
    $view = new \Phalcon\Mvc\View\Simple();
    $view->setViewsDir('views/');
    return $view;
};

// Установка сервиса базы данных
$app['db'] = function () {
    return new MysqlAdapter(
        array(
            "host"     => "localhost",
            "username" => "ya_phalcon",
            "password" => "uv3qfm",
            "dbname"   => "ya_phalcon"
        )
    );
};

$app->get('/{name}', function ($name) use ($app) {

    $phql = "SELECT * FROM Categories";
    $arrs = array();
    $arr = array();
    $categories = $app->modelsManager->executeQuery($phql);
    //var_dump($categories);

    foreach ($categories as $category) {
        $arr['id'] = $category->id;
        $arr['name'] = $category->name;
        $arr['parentid'] = $category->parentid;
        $arr['uri'] = $category->uri;
        $arrs[] = $arr;
    }
    //var_dump($arrs);
    $mytree = new Tree();
    $html = $mytree->build($arrs);

    
    //var_dump($html);
    // Отрисовываем представление index.phtml с передачей в него переменных
    $hmenu = '';
    echo $app['view']->render('/index', array('content' => $html, 'hmenu' => trim($hmenu, "| ")));
    echo $name;
});



/*
$app->get('/{name}', function ($name) {
    echo "<h1>Welcome $name!</h1>";
});
*/


$app->handle();
