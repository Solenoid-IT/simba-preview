<?php



// (Including the files)
include_once( __DIR__ . '/autoload.php' );
include_once( __DIR__ . '/config.php' );
include_once( __DIR__ . '/gate.php' );



use \Solenoid\Core\App\App;
use \Solenoid\Core\App\SysApp;
use \Solenoid\Core\App\WebApp;



switch ( App::fetch_context() )
{
    case 'cli':
        // (Creating a SysApp)
        $app = SysApp::init( $app_config, gethostname() );
    break;

    case 'http':
        // (Including the file)
        include_once( __DIR__ . '/routes.php' );

        // (Creating a WebApp)
        $app = WebApp::init( $app_config );
    break;
}



// (Registering the app)
App::register($app);



// (Running the app)
$app->run();



?>