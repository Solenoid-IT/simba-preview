<?php



namespace App\Controllers\Admin;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;

use \Solenoid\MySQL\Condition;

use \App\Stores\Session\User as UserSessionStore;
use \App\Middlewares\RPC\Parser as RPC;
use \App\Models\DB\local\simba_db\User as UserDBModel;
use \App\Models\DB\local\simba_db\Visitor as VisitorDBModel;



class Visitor extends Controller
{
    # Returns [void]
    public function rpc ()
    {
        switch ( RPC::$verb )
        {
            case 'empty_log':
                // (Getting the value)
                $user_id = UserSessionStore::fetch()->session->data['user'];



                // (Getting the value)
                $user = UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->find();

                if ( $user === false )
                {// (User not found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'User not found' ] ] ) )
                    ;
                }



                if ( $user->hierarchy !== 1 )
                {// (Match failed)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(403), [], [ 'error' => [ 'message' => 'Operation not permitted' ] ] ) )
                    ;
                }



                if ( VisitorDBModel::fetch()->delete() === false )
                {// (Unable to delete the records)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to remove the visitors' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200) ) )
                ;
            break;
        }
    }
}



?>