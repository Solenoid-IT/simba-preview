<?php



// (Getting the value)
$root = __DIR__;



// (Getting the value)
$app_config = json_decode( file_get_contents( "$root/app.json" ) );



// (Including the file)
include_once( __DIR__ . '/middlewares/groups.php' );



// (Including the file)
include_once( __DIR__ . '/envs.php' );



# Returns [string]
function explicit_path (string $root, string $path)
{
    // Returning the value
    return preg_replace( '/^\./', $root, $path );
}



// (Getting the value)
$app_config =
[
    'basedir'          => __DIR__,

    'id'               => $app_config->id,
    'name'             => $app_config->name,

    'history'          => explicit_path( $root, $app_config->history ),
    'credentials'      => explicit_path( $root, $app_config->credentials ),

    'logs'             =>
    [
        'cli'          =>
        [
            'error'    => explicit_path( $root, $app_config->logs->cli->error ),
            'activity' => explicit_path( $root, $app_config->logs->cli->activity )
        ],

        'http'         =>
        [
            'error'    => explicit_path( $root, $app_config->logs->http->error ),
            'activity' => explicit_path( $root, $app_config->logs->http->activity )
        ]
    ],

    'storage'          =>
    [
        'folder_path'  => explicit_path( $root, $app_config->storage->folder_path ),
        'chroot'       => $app_config->storage->chroot
    ],

    'blade'            =>
    [
        'views'        => explicit_path( $root, $app_config->blade->views ),
        'cache'        => explicit_path( $root, $app_config->blade->cache )
    ],

    'timezone'         => $app_config->timezone,

    'envs'             => $envs,
    'gate'             => \App\Gate::class,
    'middlewares'      => $middlewares
]
;



?>