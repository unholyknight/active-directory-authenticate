<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin(
    'ActiveDirectoryAuthenticate',
    ['path' => '/active-directory-authenticate'],
    function (RouteBuilder $routes) {
        $routes->fallbacks('DashedRoute');
    }
);
