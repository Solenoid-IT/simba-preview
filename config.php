<?php



// (Getting the value)
$root = __DIR__;



// (Getting the value)
$app_config = json_decode( file_get_contents( "$root/app.json" ) );



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

    'timezone'         => $app_config->timezone
]
;



?>