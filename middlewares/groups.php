<?php



use \App\Middlewares\RPC;
use \App\Middlewares\User as UserMiddleware;
use \App\Middlewares\Editor as EditorMiddleware;



// (Getting the value)
$middlewares =
[
    'RPC/Authenticator' =>
    [
        RPC\Authenticator::class
    ],

    'RPC/Parser' =>
    [
        RPC\Parser::class
    ],

    'User' =>
    [
        UserMiddleware::class
    ],

    'Editor' =>
    [
        EditorMiddleware::class
    ]
]
;



?>