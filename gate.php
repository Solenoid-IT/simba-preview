<?php



namespace App;



use \Solenoid\Core\App\App;
use \Solenoid\Core\App\WebApp;

use \Solenoid\Network\IPv4\IPv4;
use \Solenoid\Network\IPv4\Firewall;

use \App\Middlewares\Cors as CorsMiddleware;



class Gate implements \Solenoid\Core\Gate
{
    # Returns [bool]
    public static function run ()
    {
        switch ( App::fetch_context() )
        {
            case 'cli':
                // Printing the value
                echo "\n\nGate -> traversed\n\n\n";
            break;

            case 'http':
                // (Getting the value)
                $app = WebApp::fetch();

                if ( in_array( 'fw', $app->target->tags ) )
                {// (Route contains this tag)
                    // (Creating a Firewall)
                    $firewall = new Firewall
                    (
                        [],
                        [
                            '0.0.0.0/0'
                        ]
                    )
                    ;



                    // Returning the value
                    return $firewall->check( IPv4::select( $app->request->client_ip ) );
                }



                if ( CorsMiddleware::run() === false ) return false;
            break;
        }
    }
}



?>