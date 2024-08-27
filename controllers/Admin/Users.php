<?php



namespace App\Controllers\Admin;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;

use \Solenoid\HTTP\URL;

use \App\Stores\Sessions\Store as SessionsStore;
use \App\Models\local\simba_db\User as UserModel;
use \App\Models\local\simba_db\Hierarchy as HierarchyModel;
use \App\Services\Authorization as AuthorizationService;
use \App\Middlewares\User as UserMiddleware;
use \App\Middlewares\RPC\Parser as RPC;



class Users extends Controller
{
    # Returns [void]
    public function rpc ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        switch ( RPC::$verb )
        {
            case 'fetch_data':
                if ( UserMiddleware::run() === false ) return;



                // (Getting the value)
                $session = SessionsStore::fetch()->sessions['user'];



                // (Getting the value)
                $user_id = $session->data['user'];



                // (Getting the value)
                $user = UserModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->get();

                if ( $user === false )
                {// (Record not found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'User not found' ] ] ) )
                    ;
                }



                if ( $user->hierarchy !== 1 )
                {// (User is not a root)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(403), [], [ 'error' => [ 'message' => 'Operation not permitted' ] ] ) )
                    ;
                }



                // (Setting the value)
                $hierarchies = [];

                foreach ( HierarchyModel::fetch()->list() as $hierarchy )
                {// Processing each entry
                    // (Getting the value)
                    $hierarchies[ $hierarchy->id ] = $hierarchy;
                }



                // (Getting the value)
                $data =
                [
                    'user'             => $user,
                    'required_actions' => $session->data['set_password'] ? [ 'set_password' ] : [],

                    'records'          => UserModel::fetch()->filter()->get_list(),
                    'hierarchies'      => $hierarchies
                ]
                ;



                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], $data ) )
                ;
            break;



            case 'register':
                if ( RPC::$input->authorization )
                {// Value found
                    // (Fetching the authorization)
                    $response = AuthorizationService::fetch( RPC::$input->authorization );

                    if ( $response->status->code !== 200 )
                    {// (Unable to fetch the authorization)
                        // Returning the value
                        return
                            Server::send( $response )
                        ;
                    }



                    // (Getting the value)
                    $authorization = $response->body;



                    // (Getting the value)
                    $record = (array) $authorization->data->request->input;

                    if ( UserModel::fetch()->insert( [ $record ] ) === false )
                    {// (Unable to insert the record)
                        // Returning the value
                        return
                            Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to register the user' ] ] ) )
                        ;
                    }



                    // Returning the value
                    return
                        Server::send( new Response( new Status(200) ) )
                    ;
                }
                else
                {// Value not found
                    if ( $app->request->client_ip === $app->request->server_ip )
                    {// (Request is from localhost)
                        // (Getting the value)
                        $username_user = UserModel::fetch()->filter( [ [ 'username' => RPC::$input->username ] ] )->get();

                        if ( $username_user !== false )
                        {// (Username found)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => 'Username is not available' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $email_user = UserModel::fetch()->filter( [ [ 'email' => RPC::$input->email ] ] )->get();

                        if ( $email_user !== false )
                        {// (Email found)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => 'Email is not available' ] ] ) )
                            ;
                        }



                        // (Setting the value)
                        $base_url = $app->request->url->fetch_base();



                        // (Getting the value)
                        $origin = $app->request->headers['Origin'];

                        if ( $origin )
                        {// Value found
                            if ( in_array( URL::parse($origin)->host, $app->env->hosts ) )
                            {// Match OK
                                // (Getting the value)
                                $base_url = $origin;
                            }
                        }



                        // (Starting the authorization)
                        $response = AuthorizationService::start
                        (
                            $base_url . '/admin',
                            [
                                'request'                         =>
                                [
                                    'endpoint_path'               => $app->request->url->path,
                                    'action'                      => $app->request->headers['Action'],
                                    'input'                       =>
                                    [
                                        'hierarchy'               => RPC::$input->hierarchy,

                                        'username'                => RPC::$input->username,
                                        'email'                   => RPC::$input->email,

                                        'profile.name'            => RPC::$input->profile->name,
                                        'profile.surname'         => RPC::$input->profile->surname,
                                    ]
                                ],

                                'login'                           => true
                            ],

                            RPC::$input->email,
                            'USER_CREATION'
                        )
                        ;

                        if ( $response->status->code !== 200 )
                        {// (Unable to start the authorization)
                            // Returning the value
                            return
                                Server::send( $response )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [], [ 'receiver' => RPC::$input->email, 'exp_time' => $response->body['exp_time'] ] ) )
                        ;
                    }
                    else
                    {// (Request is from outside)
                        if ( UserMiddleware::run() === false ) return;



                        // (Getting the value)
                        $session = SessionsStore::fetch()->sessions['user'];



                        // (Getting the value)
                        $user_id = $session->data['user'];
                        $user    = UserModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->get();

                        if ( $user->hierarchy !== 1 )
                        {// (User is not a root)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(403), [], [ 'error' => [ 'message' => 'Operation not permitted' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $username_user = UserModel::fetch()->filter( [ [ 'username' => RPC::$input->username ] ] )->get();

                        if ( $username_user !== false )
                        {// (Username found)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => 'Username is not available' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $email_user = UserModel::fetch()->filter( [ [ 'email' => RPC::$input->email ] ] )->get();

                        if ( $email_user !== false )
                        {// (Email found)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => 'Email is not available' ] ] ) )
                            ;
                        }



                        // (Setting the value)
                        $base_url = $app->request->url->fetch_base();



                        // (Getting the value)
                        $origin = $app->request->headers['Origin'];

                        if ( $origin )
                        {// Value found
                            if ( in_array( URL::parse($origin)->host, $app->env->hosts ) )
                            {// Match OK
                                // (Getting the value)
                                $base_url = $origin;
                            }
                        }



                        // (Starting the authorization)
                        $response = AuthorizationService::start
                        (
                            $base_url . '/admin',
                            [
                                'request'                         =>
                                [
                                    'endpoint_path'               => $app->request->url->path,
                                    'action'                      => $app->request->headers['Action'],
                                    'input'                       =>
                                    [
                                        'hierarchy'               => RPC::$input->hierarchy,

                                        'username'                => RPC::$input->username,
                                        'email'                   => RPC::$input->email,

                                        'profile.name'            => RPC::$input->profile->name,
                                        'profile.surname'         => RPC::$input->profile->surname,
                                    ]
                                ],

                                'login'                           => true
                            ],

                            RPC::$input->email,
                            'USER_CREATION'
                        )
                        ;

                        if ( $response->status->code !== 200 )
                        {// (Unable to start the authorization)
                            // Returning the value
                            return
                                Server::send( $response )
                            ;
                        }



                        // (Getting the value)
                        $session->data['authorization'] = $response->body['token'];



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [], [ 'receiver' => RPC::$input->email, 'exp_time' => $response->body['exp_time'] ] ) )
                        ;
                    }
                }
            break;

            case 'unregister':
                if ( UserMiddleware::run() === false ) return;



                // (Getting the value)
                $session = SessionsStore::fetch()->sessions['user'];



                // (Getting the value)
                $user_id = $session->data['user'];
                $user    = UserModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->get();

                if ( $user->hierarchy !== 1 )
                {// (User is not a root)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(403), [], [ 'error' => [ 'message' => 'Operation not permitted' ] ] ) )
                    ;
                }



                if ( UserModel::fetch()->condition_start()->where_field( null, 'id' )->in( RPC::$input->list )->condition_end()->delete() === false )
                {// (Unable to delete the records)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to remove the users' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200) ) )
                ;
            break;



            case 'change':
                if ( UserMiddleware::run() === false ) return;



                // (Getting the value)
                $session = SessionsStore::fetch()->sessions['user'];



                // (Getting the value)
                $user_id = $session->data['user'];
                $user    = UserModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->get();

                if ( $user->hierarchy !== 1 )
                {// (User is not a root)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(403), [], [ 'error' => [ 'message' => 'Operation not permitted' ] ] ) )
                    ;
                }



                // (Getting the value)
                $username_user = UserModel::fetch()->filter( [ [ 'username' => RPC::$input->username ] ] )->get();

                if ( $username_user && $username_user->id !== RPC::$input->id )
                {// (Username is not available)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => 'Username is not available' ] ] ) )
                    ;
                }



                // (Getting the value)
                $record =
                [
                    'hierarchy'        => RPC::$input->hierarchy,

                    'username'         => RPC::$input->username,

                    'profile.name'     => RPC::$input->profile->name,
                    'profile.surname'  => RPC::$input->profile->surname
                ]
                ;

                if ( UserModel::fetch()->filter( [ [ 'id' => RPC::$input->id ] ] )->update( $record ) === false )
                {// (Unable to update the record)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to change the user' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200) ) )
                ;
            break;



            case 'find':
                if ( UserMiddleware::run() === false ) return;



                // (Getting the value)
                $session = SessionsStore::fetch()->sessions['user'];



                // (Getting the value)
                $user_id = $session->data['user'];
                $user    = UserModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->get();

                if ( $user->hierarchy !== 1 )
                {// (User is not a root)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(403), [], [ 'error' => [ 'message' => 'Operation not permitted' ] ] ) )
                    ;
                }



                // (Setting the value)
                $valid_keys = [ 'id', 'username' ];



                // (Getting the value)
                $key = RPC::$input->key;

                if( !in_array( $key, $valid_keys ) )
                {// Match failed
                    // Returning the value
                    return
                        Server::send( new Response( new Status(400), [], [ 'error' => [ 'message' => 'Bad request' ] ] ) )
                    ;
                }



                // (Getting the value)
                $user = UserModel::fetch()->filter( [ [ 'id' => RPC::$input->value ] ] )->get();

                if ( $user === false )
                {// (User not found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'User not found' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], $user ) )
                ;
            break;
        }
    }
}



?>