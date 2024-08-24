<?php



namespace App\Controllers\Admin;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;

use \Solenoid\MySQL\Condition;
use \Solenoid\MySQL\Record;

use \App\Stores\Sessions\Store as SessionsStore;
use \App\Models\DB\local\simba_db\User as UserDBModel;
use \App\Models\DB\local\simba_db\Access as AccessDBModel;
use \App\Middlewares\RPC\Parser as RPC;



class AccessLog extends Controller
{
    # Returns [void]
    public function rpc ()
    {
        switch ( RPC::$verb )
        {
            case 'fetch_data':
                // (Getting the value)
                $session = SessionsStore::fetch()->sessions['user'];



                // (Getting the value)
                $user_id = $session->data['user'];



                // (Getting the value)
                $user = UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->get();

                if ( $user === false )
                {// (Record not found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'User not found' ] ] ) )
                    ;
                }



                // (Getting the value)
                $records = AccessDBModel::fetch()->filter( [ [ 'user' => $user_id ] ] )->list
                (
                    [],
                    false,
                    [ 'datetime.insert' => 'DESC' ],
                    true,
                    function (Record $record) use (&$session)
                    {
                        // (Getting the value)
                        $record->current_session = $record->session === $session->id;



                        // Returning the value
                        return $record;
                    }
                )
                ;



                // (Getting the value)
                $data =
                [
                    'user'             => $user,
                    'required_actions' => $session->data['set_password'] ? [ 'set_password' ] : [],

                    'records'          => $records
                ]
                ;



                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], $data ) )
                ;
            break;

            case 'empty':
                // (Getting the value)
                $user = UserDBModel::fetch()->filter( [ [ 'id' => SessionsStore::fetch()->sessions['user']->data['user'] ] ] )->find();

                if ( $user === false )
                {// (User not found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'User not found' ] ] ) )
                    ;
                }



                if ( AccessDBModel::fetch()->filter( [ [ 'user' => $user->id ] ] )->delete() === false )
                {// (Unable to delete the record)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to empty the access log for the user' ] ] ) )
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