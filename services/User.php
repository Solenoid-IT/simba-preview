<?php



namespace App\Services;



use \Solenoid\Core\Service;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Status;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\URL;
use \Solenoid\HTTP\Cookie;

use \App\Stores\Sessions\Store as SessionsStore;
use \App\Models\local\simba_db\User as UserModel;



class User extends Service
{
    # Returns [Response]
    public static function verify (?int $hierarchy = null)
    {
        // (Getting the value)
        $app = WebApp::fetch();



        // (Getting the value)
        $session = SessionsStore::fetch()->sessions['user'];



        if ( !$session->start() )
        {// (Unable to start the session)
            // Returning the value
            return
                new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to start the session' ] ] )
            ;
        }

        if ( !$session->regenerate_id() )
        {// (Unable to regenerate the session id)
            // Returning the value
            return
                new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to regenerate the session id' ] ] )
            ;
        }



        if ( $session->data['user'] )
        {// Value found
            if ( $session->data['idk_reset'] )
            {// Value found
                // Returning the value
                return
                    new Response( new Status(303), [ 'Location' => '/admin/user-activation' ] )
                ;
            }
            else
            {// Value not found
                // (Doing nothing)
            }
        }
        else
        {// Value not found
            if ( !$session->destroy() )
            {// (Unable to destroy the session)
                // Returning the value
                return
                    new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to destroy the session' ] ] )
                ;
            }



            // (Getting the value)
            $fwd_route = $app->request->cookies['route'] ?? ( $_SERVER['REQUEST_URI'] ?? '/admin' );

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
            ( new Cookie( 'fwd_route', '.' . $app->id, '/', true, true ) )->set( $fwd_route );



            // Returning the value
            return
                new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] )
            ;
        }



        if ( $hierarchy )
        {// Value found
            if ( !UserModel::fetch()->where( [ 'id', $session->data['user'] ], [ 'hierarchy', $hierarchy ] )->exists() )
            {// (Record not found)
                // Returning the value
                return
                    new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] )
                ;
            }
        }



        // Returning the value
        return
            new Response( new Status(200) )
        ;
    }
}



?>