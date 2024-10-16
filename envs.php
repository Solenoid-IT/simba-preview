<?php



use \Solenoid\Core\Env;



// (Getting the value)
$envs =
[
    'cli'  =>
    [
        new Env( 'dev', Env::TYPE_DEV, [ 'vps' ] )
    ],

    'http' =>
    [
        new Env( 'dev', Env::TYPE_DEV, [ 'dev.simba.solenoid.it' ] ),
        new Env( 'prod', Env::TYPE_PROD, [ 'simba.solenoid.it' ] ),
    ]
]
;



?>