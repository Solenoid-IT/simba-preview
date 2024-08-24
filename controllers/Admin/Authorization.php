<?php



namespace App\Controllers\Admin;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;
use \Solenoid\HTTP\Client\Client;

use \Solenoid\SSE\Server as SSEServer;
use \Solenoid\SSE\Event as SSEEvent;

use \Solenoid\MySQL\Condition;

use \App\Stores\Sessions\Store as SessionsStore;
use \App\Models\DB\local\simba_db\Authorization as AuthorizationDBModel;
use \App\Models\DB\local\simba_db\User as UserDBModel;



class Authorization extends Controller
{
    # Returns [void]
    public function get (string $token)
    {
        // (Setting the header)
        header('Content-Type: application/json');



        // (Getting the value)
        $app = WebApp::fetch();



        // (Getting the value)
        $action = $app->request->url->fetch_params()['action'];



        if ( $action === 'accept' )
        {// Match OK
            // (Getting the value)
            $authorization = AuthorizationDBModel::fetch()->filter( [ [ 'token' => $token ] ] )->get();

            if ( $authorization === false )
            {// (Authorization not found)
                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], [ 'message' => 'Request has been processed' ] ) )
                ;
            }



            if ( $authorization->data['request'] )
            {// (Authorization contains a request to make)
                // (Sending an http request)
                $response = Client::send
                (
                    $app->request->url->fetch_base() . $authorization->data['request']['endpoint_path'],
                    'RPC',
                    [
                        'Action: ' . $authorization->data['request']['action'],
                        'Content-Type: application/json',

                        "Auth-Token: $token"
                    ],
                    $authorization->data['request']['input']
                )
                ;

                if ( $response->fetch_tail()->status->code !== 200 )
                {// (Request failed)
                    // Returning the value
                    return
                        Server::send( new Response( new Status( $response->fetch_tail()->status->code ), [], $response->body ) )
                    ;
                }
            }



            if ( $authorization->data['login'] )
            {// (Authorization contains a login to do)
                // (Getting the value)
                $user = UserDBModel::fetch()->filter( [ [ 'email' => $authorization->data['request']['input']['email'] ] ] )->find();

                if ( $user === false )
                {// (User not found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Record not found (user)' ] ] ) )
                    ;
                }



                // (Getting the value)
                $session = SessionsStore::fetch()->sessions['user'];



                if ( !$session->start() )
                {// (Unable to start the session)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], json_encode( [ 'error' => [ 'message' => [ 'Unable to start the session' ] ] ] ) ) )
                    ;
                }

                if ( !$session->regenerate_id() )
                {// (Unable to regenerate the session id)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], json_encode( [ 'error' => [ 'message' => [ 'Unable to regenerate the session id' ] ] ] ) ) )
                    ;
                }

                if ( !$session->set_duration() )
                {// (Unable to set the session duration)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], json_encode( [ 'error' => [ 'message' => [ 'Unable to set the session duration' ] ] ] ) ) )
                    ;
                }



                // (Setting the value)
                $session->data = [];



                // (Getting the value)
                $session->data['user'] = $user->id;



                // (Setting the value)
                $session->data['set_password'] = true;



                /*

                // (Setting the header)
                header('Content-Type: text/html');

                // Printing the value
                echo
                    <<<EOD
                    <script>

                        // (Setting the location)
                        window.location.href = '/admin';

                    </script>
                    EOD
                ;

                */
            }
        }



        if ( AuthorizationDBModel::fetch()->filter( [ [ 'token' => $token ] ] )->delete() === false )
        {// (Unable to delete the record)
            // Returning the value
            return
                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to delete the authorization' ] ] ) )
            ;
        }



        if ( $action === 'accept' )
        {// Match OK
            if ( $authorization->callback_url )
            {// Value found
                // Returning the value
                return
                    Server::send( new Response( new Status(303), [ 'Location: ' . $authorization->callback_url ] ) )
                ;
            }
        }



        // Returning the value
        return
            Server::send( new Response( new Status(200), [], [ 'message' => 'Request has been processed' ] ) )
        ;
    }

    # Returns [void]
    public function sse ()
    {
        // (Getting the value)
        $session = SessionsStore::fetch()->sessions['user'];



        // (Starting the session)
        $session->start();



        // (Getting the value)
        $token = $session->data['authorization'];

        if ( !isset( $token ) )
        {// Value not found
            // (Destroying the session)
            $session->destroy();



            // Returning the value
            return
                Server::send( new Response( new Status(401), [ 'Content-Type: application/json' ], [ 'error' => [ 'message' => 'Authorization not found' ] ] ) )
            ;
        }



        // (Starting the server)
        SSEServer::create()->start
        (
            function () use (&$session, $token)
            {
                // (Waiting for the seconds)
                sleep( 1 );



                // (Getting the value)
                $authorization = AuthorizationDBModel::fetch()->filter( [ [ 'token' => $token ] ] )->get();

                if ( $authorization === false )
                {// (Authorization not found)
                    // (Closing the session)
                    $session->close();



                    // Returning the value
                    return
                        SSEEvent::create( 'close', json_encode( [ 'time' => time(), 'location' => '/admin' ] ) )
                    ;
                }
                else
                {// (Authorization found)
                    if ( strtotime( $authorization->datetime->expiration ) <= time() )
                    {// (Authorization is not valid)
                        // (Deleting the record)
                        AuthorizationDBModel::fetch()->delete( ( new Condition() )->filter( [ [ 'token' => $authorization->token ] ] ) );
                    }
                }
            }
        )
        ;
    }
}



?>