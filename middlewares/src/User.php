<?php



namespace App\Middlewares;



use \Solenoid\Core\Middleware;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;
use \Solenoid\HTTP\Cookie;
use \Solenoid\HTTP\URL;

use \App\Stores\Sessions\Store as SessionsStore;



class User extends Middleware
{
    # Returns [bool] | Throws [Exception]
    public static function run ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        // (Getting the value)
        $session = SessionsStore::fetch()->sessions['user'];



        if ( !$session->start() )
        {// (Unable to start the session)
            // (Setting the value)
            $message = "Unable to start the session";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }

        if ( $app->request->method !== 'BIN' )
        {// (There is not a transfer in progress)
            if ( !$session->regenerate_id() )
            {// (Unable to regenerate the session id)
                // (Setting the value)
                $message = "Unable to regenerate the session id";

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }



        if ( $session->data['user'] )
        {// Value found
            if ( $session->data['idk_reset'] )
            {// Value found
                // (Sending the response)
                Server::send( new Response( new Status(303), [ 'Location' => '/admin/user-activation' ] ) );



                // Returning the value
                return false;
            }
            else
            {// Value not found
                
            }
        }
        else
        {// Value not found
            if ( !$session->destroy() )
            {// (Unable to destroy the session)
                // (Setting the value)
                $message = "Unable to destroy the session";

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }



            // (Getting the value)
            $fwd_route = $app->request->cookies['route'] ?? '/admin';

            if ( in_array( $fwd_route, [ '/admin/login', '/admin/logout' ] ) )
            {// Match OK
                // (Setting the value)
                $fwd_route = '/admin';
            }

            if ( $fwd_route === '/user' && $app->request->headers['Action'] === 'session::validate' )
            {// Match OK
                // (Setting the value)
                $fwd_route = '/admin';



                // (Getting the value)
                $referer = $app->request->headers['Referer'];

                if ( $referer )
                {// Value found
                    // (Getting the value)
                    $url = URL::parse( $referer );

                    // (Getting the value)
                    $fwd_route = $url->path . ( $url->query ? '?' . $url->query : '' );
                }
            }



            // (Setting the cookie)
            ( new Cookie( 'fwd_route', SessionsStore::fetch()->cookie_domain, '/', true, true ) )->set( $fwd_route );



            // (Sending the response)
            Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Authentication is failed' ] ] ) );



            // Returning the value
            return false;
        }
    }
}



?>