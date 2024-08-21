<?php



namespace App\Services;



use \Solenoid\Core\Service;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Cookie;

use \App\Stores\Session\User as UserSessionStore;



class Login extends Service
{
    # Returns [string]
    public static function extract_location ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        // (Setting the value)
        $location = '/admin';



        // (Getting the value)
        $fwd_route = $app->request->cookies['fwd_route'] ?? $location;

        if ( stripos( $fwd_route, '/admin' ) !== 0 )
        {// Match failed
            // (Getting the value)
            $fwd_route = $location;
        }



        // (Getting the value)
        $location = $fwd_route;



        // (Deleting the cookie)
        Cookie::delete( 'fwd_route', UserSessionStore::fetch()->cookie_domain, '/' );



        // Returning the value
        return $location;
    }
}



?>