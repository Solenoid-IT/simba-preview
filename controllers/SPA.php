<?php



namespace App\Controllers;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\Core\App\WebApp;



class SPA extends Controller
{
    # Returns [void]
    public function get ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        // (Printing the value)
        echo $app->blade->build_html( '../web/build/index.blade.php' );
    }
}



?>