<?php



use \Solenoid\Core\Routing\Router;
use \Solenoid\Core\Routing\Target;



use \App\Controllers\Test;
use \App\Controllers\TestArgs;
use \App\Controllers\Fallback;

use \App\Controllers\RPC;
use \App\Controllers\SPA;
use \App\Controllers\Admin;
use \App\Controllers\User;
use \App\Controllers\Docs;
use \App\Controllers\FormData;



// (Creating a Router)
$router = new Router
(
    [
        '/rpc' =>
        [
            'RPC' => Target::link( RPC::class, 'rpc' )->set_middlewares(['RPC/Parser'])
        ],



        '/test' =>
        [
            'GET' => Target::link( Test::class, 'get' )->set_tags( [ 'fw' ] )
        ],

        '/test/[ x ]/[ y ]/[ z ]' =>
        [
            'GET' => Target::define( function ($app) { return $app->target->args; } )
        ],

        '/test/[ action ]/[ input ]' =>
        [
            'GET' => Target::link( Test::class, 'get' )->set_middlewares( ['User'] )
        ],

        '/test_args/[ str ]/[ int ]' =>
        [
            'GET' => Target::link( TestArgs::class, 'get' )
        ],

        '/test/error' =>
        [
            'GET' => Target::define( function () { throw new \Exception('exception test'); } )
        ],



        '/' =>
        [
            'GET' => Target::link( SPA::class, 'get' )
        ],

        '/admin' =>
        [
            'GET' => Target::link( SPA::class, 'get' )
        ],

        '/admin/login' =>
        [
            'GET' => Target::link( SPA::class, 'get' )
        ],



        '/admin/dashboard' =>
        [
            'RPC' => Target::link( Admin\Dashboard::class, 'rpc' )->set_middlewares( ['RPC/Parser', 'User'] )
        ],

        '/admin/user' =>
        [
            'RPC' => Target::link( Admin\User::class, 'rpc' )->set_middlewares( ['RPC/Parser'] )
        ],



        '/admin/docs'  =>
        [
            'GET' => Target::link( SPA::class, 'get' ),
            'RPC' => Target::link( Admin\Docs::class, 'rpc' )->set_middlewares( ['RPC/Parser', 'User'] )
        ],

        '/admin/tags'  =>
        [
            'GET' => Target::link( SPA::class, 'get' ),
            'RPC' => Target::link( Admin\Tags::class, 'rpc' )->set_middlewares( ['RPC/Parser', 'User'] )
        ]
        ,
    
    
    
        '/admin/access_log' =>
        [
            'GET' => Target::link( SPA::class, 'get' ),
            'RPC' => Target::link( Admin\AccessLog::class, 'rpc' )->set_middlewares( ['RPC/Parser', 'User'] )
        ]
        ,
    
    
    
        '/admin/users' =>
        [
            'GET' => Target::link( SPA::class, 'get' ),
            'RPC' => Target::link( Admin\Users::class, 'rpc' )->set_middlewares( ['RPC/Parser'] )
        ]
        ,
    
    
    
        '/admin/visitor' =>
        [
            'RPC' => Target::link( Admin\Visitor::class, 'rpc' )->set_middlewares( ['RPC/Parser', 'User'] )
        ],

        '/user' =>
        [
            'RPC' => Target::link( User::class, 'rpc' )->set_middlewares( ['RPC/Parser'] )
        ],
    
    
    
        '/admin/authorization' =>
        [
            'SSE' => Target::link( Admin\Authorization::class, 'sse' )
        ],

        '/admin/authorization/[ token ]/[ action ]' =>
        [
            'GET' => Target::link( Admin\Authorization::class, 'get' )
        ],
    
    
    
        '/docs' =>
        [
            'GET' => Target::link( SPA::class, 'get' ),
            'RPC' => Target::link( Docs::class, 'rpc' )->set_middlewares( ['RPC/Parser'] )
        ],
    
        '/\/docs(\/.+)/' =>
        [
            'GET' => Target::link( Docs::class, 'get' )
        ],
    
    
    
        '/perf' =>
        [
            'GET' => Target::define( function () {} )
        ],
    
    
    
        '/history.json' =>
        [
            'GET' => Target::define( function ($app) { return $app->fetch_history(); } )
        ],



        '/FormData' =>
        [
            'GET' => Target::link( FormData::class, 'get' ),
            'RPC' => Target::link( FormData::class, 'rpc' )->set_middlewares(['RPC/Parser'])
        ],
    ],

    Target::link( Fallback::class, 'view' )
)
;



?>