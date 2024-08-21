<?php



namespace App\Controllers;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Status;
use \Solenoid\HTTP\Response;

use \App\Middlewares\RPC\Parser as RPC;



class FormData extends Controller
{
    # Returns [void]
    public function get ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        // Printing the value
        echo $app->blade->build( 'components/multipart-formdata.blade.php' );;
    }

    # Returns [void]
    public function rpc ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        switch ( RPC::$subject )
        {
            case '':
                switch ( RPC::$verb )
                {
                    case 'transfer':
                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [ 'Content-Type: multipart/form-data' ], $app->request->body ) )
                        ;
                    break;
                }
            break;
        }
    }
}



?>