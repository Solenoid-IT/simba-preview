<?php



namespace App\Middlewares;



use \Solenoid\Core\Middleware;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;



class Cors extends Middleware
{
    # Returns [bool] | Throws [Exception]
    public static function run ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        if ( $app->env->type !== 'dev' ) return true;



        if ( $app->request->client_ip === $app->request->server_ip )
        {// Match OK
            // Returning the value
            return true;
        }

        if ( !$app->request->headers['Origin'] )
        {// Match failed
            // Returning the value
            return true;
        }



        // (Setting the cors)
        Server::set_cors( [ $app->request->headers['Origin'] ], [ 'GET', 'RPC', 'SSE' ], [ 'Dev-Sid', 'Auth-Token', 'Action', 'Content-Type' ], true );



        if ( $app->request->method === 'OPTIONS' )
        {// Match OK
            // (Sending the response)
            Server::send( new Response( new Status(200) ) );



            // Returning the value
            return false;
        }



        // Returning the value
        return true;
    }
}



?>