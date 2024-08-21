<?php



namespace App\Controllers;



use \Solenoid\Core\MVC\Controller;

use \App\Models\DB\local\simba_db\Session as SessionDBModel;



class Test extends Controller
{
    # Returns [void]
    public function get ()
    {
        // Returning the value
        return SessionDBModel::fetch()->filter( [ [ 'id' => 'ahcid' ] ] )->count();
    }
}



?>