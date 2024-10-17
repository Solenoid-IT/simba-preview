<?php



// (Including the files)
include_once( __DIR__ . '/autoload.php' );
include_once( __DIR__ . '/config.php' );
include_once( __DIR__ . '/gate.php' );



use \Solenoid\Core\App\App;
use \Solenoid\Core\App\SysApp;
use \Solenoid\Core\App\WebApp;

use \Solenoid\Core\Env;
use \Solenoid\Core\Storage;
use \Solenoid\Core\Logger;
use \Solenoid\Core\MVC\View;
use \Solenoid\Core\Routing\Target;
use \Solenoid\Perf\Analyzer;
use \Solenoid\HTTP\Request;



// (Getting the value)
$app_context = App::fetch_context();

switch ( $app_context )
{
    case 'cli':
        // (Adding the env)
        Env::add( 'dev', new Env( Env::TYPE_DEV, [ 'vps' ] ) );



        // (Creating a SysApp)
        $app = SysApp::init( $app_config, gethostname() );
    break;

    case 'http':
        // (Adding the envs)
        Env::add( 'dev', new Env( Env::TYPE_DEV, [ "dev.$app_config->id" ] ) );
        Env::add( 'prod', new Env( Env::TYPE_PROD, [ $app_config->id ] ) );



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
    function () use ($performance_analyzer, $app_context)
    {
        // (Closing the analyzer)
        $performance_analyzer->close();



        // (Pushing the message)
        Logger::select( "$app_context/activity" )->push( ( $app_context === 'cli' ? 'ahcid' : Request::fetch() ) . ' -> ' . $performance_analyzer );
    }
)
;
Target::on
(
    'error',
    function ($data) use ($app_context)
    {
        if ( $app_context === 'http' )
        {// Match OK
            // (Setting the status)
            http_response_code( 500 );
        }



        // (Pushing the message)
        Logger::select( "$app_context/error" )->push( ( $app_context === 'cli' ? 'ahcid' : Request::fetch() ) . ' -> ' . $data['message'] );
    }
)
;



// (Running the app)
$app->run();



?>