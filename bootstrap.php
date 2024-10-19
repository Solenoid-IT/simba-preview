<?php



// (Including the files)
include_once( __DIR__ . '/autoload.php' );
include_once( __DIR__ . '/config.php' );
include_once( __DIR__ . '/gate.php' );
include_once( __DIR__ . '/middlewares/groups.php' );



use \Solenoid\Core\App\App;
use \Solenoid\Core\App\SysApp;
use \Solenoid\Core\App\WebApp;

use \Solenoid\Core\Env;
use \Solenoid\Core\Storage;
use \Solenoid\Core\Logger;
use \Solenoid\Core\Credentials;
use \Solenoid\Core\MVC\View;
use \Solenoid\Core\Routing\Target;
use \Solenoid\Perf\Analyzer;
use \Solenoid\HTTP\Request;



// (Detecting the mode)
App::detect_mode();

switch ( App::$mode )
{
    case 'cli':
        // (Adding the env)
        Env::add( 'dev', new Env( Env::TYPE_DEV, [ 'vps' ] ) );



        // (Creating a SysApp)
        #$app = SysApp::init( $app_config, gethostname() );# ahcid to implementt
    break;

    case 'http':
        // (Adding the envs)
        Env::add( 'dev', new Env( Env::TYPE_DEV, [ $app_config['id'] ] ) );
        Env::add( 'prod', new Env( Env::TYPE_PROD, [ preg_replace( '/^dev\./', '', $app_config['id'] ) ] ) );



        // (Including the file)
        include_once( __DIR__ . '/routes.php' );

        // (Creating a WebApp)
        $app = new WebApp( $app_config );
    break;
}



// (Adding the storage)
Storage::add( 'local', new Storage( __DIR__ . '/storage', true ) );



// (Adding the loggers)
Logger::add( 'cli/activity', new Logger( __DIR__ . '/logs/cli/activity.log' ) );
Logger::add( 'cli/error', new Logger( __DIR__ . '/logs/cli/error.log' ) );



// (Adding the loggers)
Logger::add( 'http/activity', new Logger( __DIR__ . '/logs/http/activity.log' ) );
Logger::add( 'http/error', new Logger( __DIR__ . '/logs/http/error.log' ) );



// (Configuring the credentials)
Credentials::config( __DIR__ . '/credentials' );



// (Configuring the views)
View::config( __DIR__ . '/views', __DIR__ . '/views/_cache' );



// (Creating an Analyzer)
$performance_analyzer = new Analyzer();



// (Listening for the events)
Target::on
(
    'before-gate',
    function () use ($performance_analyzer)
    {
        // (Opening the analyzer)
        $performance_analyzer->open();
    }
)
;
Target::on
(
    'after-gate',
    function () use ($performance_analyzer)
    {
        // (Closing the analyzer)
        $performance_analyzer->close();



        // (Pushing the message)
        Logger::select( App::$mode . '/activity' )->push( ( App::$mode === 'cli' ? 'ahcid' : Request::fetch() ) . ' -> ' . $performance_analyzer );
    }
)
;
Target::on
(
    'error',
    function ($data)
    {
        if ( App::$mode === 'http' )
        {// Match OK
            // (Setting the status)
            http_response_code( 500 );
        }



        // (Pushing the message)
        Logger::select( App::$mode . '/error' )->push( ( App::$mode === 'cli' ? 'ahcid' : Request::fetch() ) . ' -> ' . $data['message'] );
    }
)
;



// (Running the app)
$app->run();



?>