<?php



namespace App\Controllers\Admin;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;

use \Solenoid\MySQL\Condition;

use \App\Stores\Sessions\Store as SessionsStore;
use \App\Middlewares\RPC\Parser as RPC;
use \App\Models\local\simba_db\User as UserModel;
use \App\Models\local\simba_db\Visitor as VisitorModel;



class Visitor extends Controller
{
    # Returns [void]
    public function rpc ()
    {
        switch ( RPC::$verb )
        {
            case 'empty_log':
                // (Getting the value)
                $user_id = SessionsStore::fetch()->sessions['user']->data['user'];



                // (Getting the value)
                $user = UserModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->find();

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



                if ( VisitorModel::fetch()->delete() === false )
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