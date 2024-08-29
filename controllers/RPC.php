<?php



namespace App\Controllers;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Status;
use \Solenoid\HTTP\Response;

use \Solenoid\MySQL\DateTime;

use \Solenoid\Core\App\WebApp;

use \App\Middlewares\RPC\Parser as RPCParser;
use \App\Models\local\simba_db\User as UserModel;
use \App\Models\local\simba_db\Group as GroupModel;
use \App\Services\Authorization as AuthorizationService;
use \App\Services\User as UserService;
use \App\Stores\Sessions\Store as SessionsStore;



class RPC extends Controller
{
    # Returns [void]
    public function rpc ()
    {
        switch ( RPCParser::$subject )
        {
            case '':
                switch ( RPCParser::$verb )
                {
                    case 'test':
                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [], RPCParser::$input ) )
                        ;
                    break;
                }
            break;

            case 'user':
                switch ( RPCParser::$verb )
                {
                    case 'register':
                        // (Getting the value)
                        $app = WebApp::fetch();



                        if ( $app->request->headers['Auth-Token'] )
                        {// (Authorization has been provided)
                            if ( $app->request->client_ip !== $app->request->server_ip )
                            {// (Request is not from localhost)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $response = AuthorizationService::fetch( $app->request->headers['Auth-Token'] );

                            if ( $response->status->code !== 200 )
                            {// (Unable to fetch the authorization)
                                // Returning the value
                                return
                                    Server::send( $response )
                                ;
                            }



                            // (Getting the value)
                            $authorization = $response->body;



                            if ( $authorization->data['request']['input']['group']['id'] )
                            {// Value found
                                // (Getting the value)
                                $group_id = $authorization->data['request']['input']['group']['id'];
                            }
                            else
                            {// Value not found
                                // (Getting the value)
                                $group = GroupModel::fetch()->where( 'name', $authorization->data['request']['input']['group']['name'] )->find();

                                if ( $group === false )
                                {// (Record not found)
                                    // (Getting the value)
                                    $record =
                                    [
                                        'name'            => $authorization->data['request']['input']['group']['name'],

                                        'datetime.insert' => DateTime::fetch(),
                                        'datetime.update' => null
                                    ]
                                    ;

                                    if ( GroupModel::fetch()->insert( [ $record ] ) === false )
                                    {// (Unable to insert the record)
                                        // Returning the value
                                        return
                                            Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to insert the record (group)' ] ] ) )
                                        ;
                                    }



                                    // (Getting the value)
                                    $group_id = GroupModel::fetch()->fetch_ids()[0];
                                }
                                else
                                {// (Record found)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => "['name'] already exists (group)" ] ] ) )
                                    ;
                                }
                            }



                            if ( UserModel::fetch()->where( [ [ 'group', $group_id ], [ 'name', $authorization->data['request']['input']['user']['name'] ] ] )->exists() )
                            {// (Record found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => "['group','name'] already exists (user)" ] ] ) )
                                ;
                            }

                            if ( UserModel::fetch()->where( 'email', $authorization->data['request']['input']['user']['email'] )->exists() )
                            {// (Record found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => "['email'] already exists (user)" ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $record = $authorization->data['request']['input']['user'];
                            $record =
                            [
                                'group'           => $group_id,
                                'name'            => $record['name'],

                                'email'           => $record['email'],

                                'hierarchy'       => $record['hierarchy'],

                                'datetime.insert' => DateTime::fetch(),
                                'datetime.update' => null
                            ]
                            ;

                            if ( UserModel::fetch()->insert( [ $record ] ) === false )
                            {// (Unable to insert the record)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to insert the record (user)' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $user_id = UserModel::fetch()->fetch_ids()[0];



                            // Returning the value
                            return
                                Server::send( new Response( new Status(200), [ 'Content-Type: application/json' ], $user_id ) )
                            ;
                        }
                        else
                        {// (Authorization has not been provided)
                            if ( $app->request->client_ip !== $app->request->server_ip )
                            {// (Request is not from localhost)
                                // (Verifying the user)
                                $response = UserService::verify( 1 );

                                if ( $response->status->code !== 200 )
                                {// (Session is not valid)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                    ;
                                }
                            }



                            // (Starting an authorization)
                            $response = AuthorizationService::start
                            (
                                [
                                    'request'           =>
                                    [
                                        'endpoint_path' => $app->request->url->path,
                                        'action'        => $app->request->headers['Action'],
                                        'input'         => RPCParser::$input
                                    ],

                                    'login'             => true
                                ],

                                $app->request->url->fetch_base() . '/admin/dashboard'
                            )
                            ;
                            
                            if ( $response->status->code !== 200 )
                            {// (Unable to start the authorization)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to start the authorization' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $exp_time = $response->body['exp_time'];



                            // (Sending the authorization)
                            $response = AuthorizationService::send( $response->body['token'], RPCParser::$input['user']['email'], RPCParser::$subject . '.' . RPCParser::$verb );
                            
                            if ( $response->status->code !== 200 )
                            {// (Unable to send the authorization)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to send the authorization' ] ] ) )
                                ;
                            }



                            // Returning the value
                            return Server::send( new Response( new Status(200), [], [ 'exp_time' => $exp_time ] ) );
                        }
                    break;

                    case 'fetch_data':
                        // (Verifying the user)
                        $response = UserService::verify();

                        if ( $response->status->code !== 200 )
                        {// (Verification is failed)
                            // Returning the value
                            return
                                Server::send( $response )
                            ;
                        }



                        // (Getting the value)
                        $session = SessionsStore::fetch()->sessions['user'];



                        // (Getting the value)
                        $user_id = $session->data['user'];



                        // (Getting the value)
                        $app = WebApp::fetch();



                        switch ( $app->request->headers['Route'] )
                        {
                            case '/admin/dashboard':
                                // (Getting the value)
                                $response = UserService::fetch_data( $user_id );

                                if ( $response->status->code !== 200 )
                                {// (Unable to fetch the data)
                                    // Returning the value
                                    return
                                        Server::send( $response )
                                    ;
                                }



                                // (Getting the value)
                                $data =
                                [
                                    'user' => $response->body
                                ]
                                ;
                            break;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [], $data ) )
                        ;
                    break;

                    /*case 'change_password':
                        // (Verifying the user)
                        $response = UserService::verify();

                        if ( $response->status->code !== 200 )
                        {// (Verification is failed)
                            // Closing the process
                            exit( Server::send( $response ) );
                        }



                        // (Getting the value)
                        $session = SessionsStore::fetch()->sessions['user'];



                        // (Getting the value)
                        $account_id = $session->data['user'];



                        // (Getting the value)
                        $entry =
                        [
                            #'password'        => ( new Password( $request->input['password'] ) )->hash( PASSWORD_BCRYPT ),
                            'password'        => password_hash( RPCParser::$input['password'], PASSWORD_BCRYPT ),
                            'update_datetime' => DateTime::fetch()
                        ]
                        ;

                        if ( !AccountModel::where( 'id', $account_id )->update( $entry ) )
                        {// (Unable to update the record)
                            // Closing the process
                            exit( Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to update the record (account)" ] ] ) ) );
                        }



                        // (Getting the value)
                        $entry =
                        [
                            'account'         => $account_id,
                            'action'          => str_replace( '::', '.', $request->action ),
                            'ip'              => $_SERVER['REMOTE_ADDR'],
                            'user_agent'      => $_SERVER['HTTP_USER_AGENT'],
                            'insert_datetime' => DateTime::fetch()
                        ]
                        ;

                        if ( !ActivityModel::create( $entry ) )
                        {// (Unable to insert the record)
                            // Closing the process
                            exit( Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) ) );
                        }



                        // Closing the process
                        exit( Server::send( new Response( new Status(200) ) ) );
                    break;

                    case 'login':
                        // (Getting the value)
                        $req = Request::fetch();



                        if ( $request->headers['Auth-Token'] )
                        {// Value found
                            if ( $req->client_ip !== $req->server_ip )
                            {// (Request is not from localhost)
                                // Closing the process
                                exit( Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) ) );
                            }



                            // (Getting the value)
                            $res = AuthorizationService::fetch( $request->headers['Auth-Token'] );

                            if ( $res->status->code !== 200 )
                            {// (Unable to fetch the authorization)
                                // Closing the process
                                exit( Server::send( $res ) );
                            }



                            // (Getting the value)
                            $authorization = $res->body;



                            // (Getting the value)
                            $entry =
                            [
                                'data'            => json_encode
                                (
                                    [
                                        'account' => $authorization->data['request']['input']['account']
                                    ]
                                ),

                                'update_datetime' => DateTime::fetch()
                            ]
                            ;

                            if ( !SessionModel::where( 'id', $authorization->data['request']['input']['session'] )->update( $entry ) )
                            {// (Unable to update the record)
                                // Closing the process
                                exit( Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to update the record (session)' ] ] ) ) );
                            }



                            // (Getting the value)
                            $entry =
                            [
                                'account'         => $authorization->data['request']['input']['account'],
                                'action'          => str_replace( '::', '.', $authorization->data['request']['action'] ),
                                'ip'              => $authorization->data['request']['input']['ip'],
                                'user_agent'      => $authorization->data['request']['input']['user_agent'],
                                'insert_datetime' => DateTime::fetch()
                            ]
                            ;

                            if ( !ActivityModel::create( $entry ) )
                            {// (Unable to insert the record)
                                // Closing the process
                                exit( Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) ) );
                            }



                            // (Getting the value)
                            $entry =
                            [
                                'ip'              => $authorization->data['request']['input']['ip'],
                                'user_agent'      => $authorization->data['request']['input']['user_agent'],
                                'browser'         => '',
                                'os'              => '',
                                'hw'              => '',
                                'account'         => $authorization->data['request']['input']['account'],
                                'session'         => $authorization->data['request']['input']['session'],
                                'login_method'    => 'MFA',
                                'insert_datetime' => DateTime::fetch()
                            ]
                            ;

                            if ( !AccessModel::create( $entry ) )
                            {// (Unable to insert the record)
                                // Closing the process
                                exit( Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (access)" ] ] ) ) );
                            }



                            // Closing the process
                            exit( Server::send( new Response( new Status(200) ) ) );
                        }
                        else
                        {// Value not found
                            // (Getting the values)
                            [ $user, $group ] = explode( '@', $request->input['login'] );
                            $password         = $request->input['password'];



                            // (Getting the value)
                            $user = UserModel::where( 'name', $user )->first();

                            if ( !$user )
                            {// (Record not found)
                                // Closing the process
                                exit( Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) ) );
                            }



                            // (Getting the value)
                            $group = GroupModel::where( 'name', $group )->first();

                            if ( !$group )
                            {// (Record not found)
                                // Closing the process
                                exit( Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) ) );
                            }



                            // (Getting the value)
                            $account = AccountModel::where( [ [ 'user', $user->id ], [ 'group', $group->id ] ] )->first();

                            if ( !$account )
                            {// (Record not found)
                                // Closing the process
                                exit( Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) ) );
                            }



                            if ( $account->password === null )
                            {// Value not found
                                // Closing the process
                                exit( Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) ) );
                            }

                            if ( !password_verify( $password, $account->password ) )
                            {// Match failed
                                // Closing the process
                                exit( Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) ) );
                            }



                            // (Getting the value)
                            $session = SessionsStore::fetch()->sessions['user'];

                            if ( !$session->start() )
                            {// (Unable to start the session)
                                // Closing the process
                                exit( Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to start the session' ] ] ) ) );
                            }

                            if ( !$session->regenerate_id() )
                            {// (Unable to regenerate the session id)
                                // Closing the process
                                exit( Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to regenerate the session id' ] ] ) ) );
                            }

                            if ( !$session->set_duration() )
                            {// (Unable to set the session duration)
                                // Closing the process
                                exit( Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to set the session duration' ] ] ) ) );
                            }



                            if ( 'MFA' )
                            {// Value is true
                                // (Getting the value)
                                $data =
                                [
                                    'request'            =>
                                    [
                                        'endpoint_path'  => $req->url->path,
                                        'action'         => $request->action,
                                        'input'          =>
                                        [
                                            'session'    => $session->id,
                                            'account'    => $account->id,

                                            'ip'         => $_SERVER['REMOTE_ADDR'],
                                            'user_agent' => $_SERVER['HTTP_USER_AGENT']
                                        ]
                                    ]
                                ]
                                ;

                                // (Starting the authorization)
                                $response = AuthorizationService::start( $data, $req->url->fetch_base() . '/admin/dashboard' );

                                if ( $response->status->code !== 200 )
                                {// (Unable to start the authorization)
                                    // Closing the process
                                    exit( Server::send( $response ) );
                                }



                                // (Getting the values)
                                $token    = $response->body['token'];
                                $exp_time = $response->body['exp_time'];



                                // (Sending the authorization)
                                $response = AuthorizationService::send( $token, $account->email, implode( '.', [ $request->subject, $request->verb ] ) );

                                if ( $response->status->code !== 200 )
                                {// (Unable to send the authorization)
                                    // Closing the process
                                    exit( Server::send( $response ) );
                                }



                                // (Setting the value)
                                $session->data =
                                [
                                    'authorization' => $token
                                ]
                                ;



                                // Closing the process
                                exit( Server::send( new Response( new Status(200), [], [ 'exp_time' => $exp_time ] ) ) );
                            }
                        }
                    break;

                    case 'login_wait':
                        // (Getting the value)
                        $session = SessionsStore::fetch()->sessions['user'];

                        if ( !$session->start() )
                        {// (Unable to start the session)
                            // Closing the process
                            exit( Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to start the session' ] ] ) ) );
                        }



                        // (Getting the value)
                        $token = $session->data['authorization'];



                        // (Setting the time limit)
                        set_time_limit(0);



                        // (Getting the value)
                        $start_timestamp = time();

                        while (true)
                        {// Processing each clock
                            // (Getting the value)
                            $current_timestamp = time();

                            if ( $current_timestamp - $start_timestamp >= 2 * 60 ) break;



                            if ( AuthorizationModel::where( 'token', $token )->count() === 0 )
                            {// (Authorization not found)
                                // Closing the process
                                exit( Server::send( new Response( new Status(200), [], [ 'location' => '/admin/dashboard' ] ) ) );
                            }



                            // (Waiting for the time)
                            sleep(2);
                        }



                        // Closing the process
                        exit( Server::send( new Response( new Status(408) ) ) );
                    break;

                    case 'logout':
                        // (Verifying the user)
                        $response = UserService::verify();

                        if ( $response->status->code !== 200 )
                        {// (Verification is failed)
                            // Closing the process
                            exit( Server::send( $response ) );
                        }



                        // (Getting the value)
                        $session = SessionsStore::fetch()->sessions['user'];



                        // (Setting the value)
                        $session->data = [];



                        if ( !$session->destroy() )
                        {// (Unable to destroy the session)
                            // Closing the process
                            exit( Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to destroy the session' ] ] ) ) );
                        }



                        // Closing the process
                        exit( Server::send( new Response( new Status(200) ) ) );
                    break;*/
                }
            break;
        }



        // (Getting the value)
        $app = WebApp::fetch();

        switch ( $app->env->type )
        {
            case 'dev':
                // Returning the value
                return
                    Server::send( new Response( new Status(404), [], [ 'RPC :: Action not found' ] ) )
                ;
            break;

            default:
                // Returning the value
                return
                    Server::send( new Response( new Status(200) ) )
                ;
        }
    }
}



?>