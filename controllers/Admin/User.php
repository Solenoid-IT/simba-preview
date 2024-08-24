<?php



namespace App\Controllers\Admin;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;
use \Solenoid\KeyGen\Password;
use \Solenoid\Encryption\KeyPair;
use \Solenoid\Encryption\RSA;
use \Solenoid\IDK\IDK;
use \Solenoid\HTTP\URL;
use \Solenoid\MySQL\Condition;

use \App\Stores\Sessions\Store as SessionsStore;
use \App\Stores\Credentials as CredentialsStore;
use \App\Middlewares\User as UserMiddleware;
use \App\Middlewares\RPC\Parser as RPC;
use \App\Models\DB\local\simba_db\User as UserDBModel;
use \App\Models\DB\local\simba_db\Session as SessionDBModel;
use \App\Models\DB\local\simba_db\Access as AccessDBModel;
use \App\Services\Client as ClientService;
use \App\Services\Login as LoginService;
use \App\Services\Authorization as AuthorizationService;



class User extends Controller
{
    # Returns [void]
    public function rpc ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        switch ( RPC::$subject )
        {
            case 'profile':
                switch ( RPC::$verb )
                {
                    case 'change':
                        if ( UserMiddleware::run() === false ) return;



                        // (Getting the value)
                        $user_id = SessionsStore::fetch()->sessions['user']->data['user'];



                        // (Getting the value)
                        $record =
                        [
                            'profile.name'     => RPC::$input->name,
                            'profile.surname'  => RPC::$input->surname
                        ]
                        ;

                        switch ( RPC::$input->photo_action )
                        {
                            case '':
                                // (Doing nothing)
                            break;

                            case 'change':
                                // (Getting the value)
                                $record['profile.photo'] = json_encode( RPC::$input->photo );
                            break;

                            case 'remove':
                                // (Setting the value)
                                $record['profile.photo'] = null;
                            break;
                        }



                        if ( UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->update($record) === false )
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
                }
            break;

            case 'username':
                switch ( RPC::$verb )
                {
                    case 'change':
                        if ( UserMiddleware::run() === false ) return;



                        // (Getting the value)
                        $user_id = SessionsStore::fetch()->sessions['user']->data['user'];



                        // (Getting the value)
                        $user = UserDBModel::fetch()->filter( [ [ 'username' => RPC::$input->username ] ] )->find();

                        if ( $user && $user->id !== $user_id )
                        {// Match failed
                            // Returning the value
                            return
                                Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => 'Username is not available' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'username' => RPC::$input->username
                        ]
                        ;

                        if ( UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->update($record) === false )
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
                }
            break;

            case 'email':
                switch ( RPC::$verb )
                {
                    case 'change':
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
                            $authorization = $response->body['authorization'];



                            // (Getting the value)
                            $record =
                            [
                                'email' => $authorization['data']['request']['input']['email']
                            ]
                            ;

                            if ( UserDBModel::fetch()->filter( [ [ 'id' => $authorization['data']['request']['input']['user'] ] ] )->update($record) === false )
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
                        }
                        else
                        {// Value not found
                            if ( UserMiddleware::run() === false ) return;



                            // (Getting the values)
                            $session   = SessionsStore::fetch()->sessions['user'];
                            $user_id   = $session->data['user'];
                            $new_email = RPC::$input->email;



                            // (Getting the value)
                            $user = UserDBModel::fetch()->filter( [ [ 'email' => $new_email ] ] )->find();

                            if ( $user && $user->id !== $user_id )
                            {// Match failed
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => 'Email is not available' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $user = UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->find();

                            if ( $user === false )
                            {// Match failed
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'User not found' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $user_email = $user->email;



                            // (Starting the authorization)
                            $response = AuthorizationService::start
                            (
                                null,
                                [
                                    'request'           =>
                                    [
                                        'endpoint_path' => $app->request->url->path,
                                        'action'        => $app->request->headers['Action'],
                                        'input'         =>
                                        [
                                            'user'      => $user_id,
                                            'email'     => $new_email
                                        ]
                                    ]
                                ],
                                $user_email,
                                'EMAIL_CHANGE'
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
                                Server::send( new Response( new Status(200), [], [ 'receiver' => $user_email, 'exp_time' => $response->body['exp_time'] ] ) )
                            ;
                        }
                    break;
                }
            break;

            case 'security':
                switch ( RPC::$verb )
                {
                    case 'change':
                        if ( UserMiddleware::run() === false ) return;



                        // (Getting the value)
                        $session = SessionsStore::fetch()->sessions['user'];



                        // (Getting the value)
                        $user_id = $session->data['user'];



                        // (Setting the value)
                        $record = [];

                        if ( RPC::$input->password )
                        {// Value found
                            // (Getting the value)
                            $record['security.password'] = Password::create( RPC::$input->password )->hash();
                        }

                        if ( isset( RPC::$input->mfa ) )
                        {// Value found
                            // (Getting the value)
                            $record['security.mfa'] = RPC::$input->mfa;
                        }

                        if ( isset( RPC::$input->idk_authentication ) )
                        {// Value found
                            // (Getting the value)
                            $record['security.idk.authentication'] = RPC::$input->idk_authentication;
                        }

                        if ( !$record )
                        {// Value is empty
                            // Returning the value
                            return
                                Server::send( new Response( new Status(400), [], [ 'error' => [ 'message' => 'Bad request' ] ] ) )
                            ;
                        }

                        if ( UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->update($record) === false )
                        {// (Unable to update the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to change the user' ] ] ) )
                            ;
                        }



                        if ( RPC::$input->password )
                        {// Value found
                            if ( $session->data['set_password'] )
                            {// Value found
                                // (Removing the element)
                                unset( $session->data['set_password'] );
                            }
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200) ) )
                        ;
                    break;
                }
            break;

            case 'idk':
                switch ( RPC::$verb )
                {
                    case 'generate':
                        if ( UserMiddleware::run() === false ) return;



                        // (Getting the value)
                        $user_id = SessionsStore::fetch()->sessions['user']->data['user'];



                        // (Getting the value)
                        $key_pair = KeyPair::generate();

                        if ( $key_pair === false )
                        {// (Unable to generate a key-pair)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to generate a key-pair' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $idk = IDK::create( $user_id, $key_pair->private_key )->build( CredentialsStore::fetch()->data['IDK_PASSPHRASE'], true );

                        if ( $idk === false )
                        {// (Unable to build the IDK)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to build the IDK' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'security.idk.public_key' => $key_pair->public_key,
                            'security.idk.signature'  => base64_encode( RSA::select( 'idk' )->encrypt( $key_pair->public_key ) )
                        ]
                        ;

                        if ( UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->update($record) === false )
                        {// (Unable to update the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to change the user' ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [], [ 'idk' => $idk ] ) )
                        ;
                    break;
                }
            break;

            case 'session':
                switch ( RPC::$verb )
                {
                    case 'remove':
                        if ( UserMiddleware::run() === false ) return;



                        // (Getting the value)
                        $user_id = SessionsStore::fetch()->sessions['user']->data['user'];



                        foreach ( RPC::$input->list as $id )
                        {// Processing each entry
                            // (Getting the value)
                            $session_id = $id;



                            // (Getting the value)
                            $session = SessionDBModel::fetch()->filter( [ [ 'id' => $session_id ] ] )->find();

                            if ( $session === false )
                            {// (Session not found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Session not found' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $session_user_id = json_decode( $session->data, true )['user'];

                            if ( $session_user_id !== $user_id )
                            {// Match failed
                                // (Getting the value)
                                $user = UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->find();

                                if ( $user->hierarchy !== 1 )
                                {// Match failed
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(403), [], [ 'error' => [ 'message' => 'Operation not permitted' ] ] ) )
                                    ;
                                }
                            }




                            if ( SessionDBModel::fetch()->filter( [ [ 'id' => $session_id ] ] )->delete() === false )
                            {// (Unable to delete the record)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to remove the session' ] ] ) )
                                ;
                            }
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200) ) )
                        ;
                    break;

                    case 'validate':
                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [], [ 'valid' => UserMiddleware::run() !== false ] ) )
                        ;
                    break;
                }
            break;

            case 'user':
                switch ( RPC::$verb )
                {
                    case 'find':
                        if ( UserMiddleware::run() === false ) return;



                        // (Getting the value)
                        $user_id = SessionsStore::fetch()->sessions['user']->data['user'];



                        // (Getting the value)
                        $user = UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->get();

                        if ( $user === false )
                        {// (Record not found)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'User not found' ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [], [ 'user' => $user ] ) )
                        ;
                    break;

                    case 'login':
                        if ( RPC::$input->idk )
                        {// Value found
                            // (Getting the value)
                            $idk = IDK::read( RPC::$input->idk, CredentialsStore::fetch()->data['IDK_PASSPHRASE'], true );



                            if ( $idk->user )
                            {// Value found
                                // (Getting the value)
                                $user = UserDBModel::fetch()->filter( [ [ 'id' => $idk->user ] ] )->find();
                            }
                            else
                            if ( $idk->data['username'] )
                            {// Value found
                                // (Getting the value)
                                $user = UserDBModel::fetch()->filter( [ [ 'username' => $idk->data['username'] ] ] )->find();
                            }
                            else
                            {// Match failed
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(400), [], [ 'error' => [ 'message' => 'Bad request' ] ] ) )
                                ;
                            }



                            if ( $user === false )
                            {// (User not found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Login failed' ] ] ) )
                                ;
                            }



                            if ( !$user->security->idk->authentication )
                            {// Value is false
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Login failed' ] ] ) )
                                ;
                            }



                            if ( RSA::select( base64_decode( $user->security->idk->signature ) )->decrypt( $idk->key )->value !== 'idk' )
                            {// (Key is not valid)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Login failed' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $session = SessionsStore::fetch()->sessions['user'];



                            if ( !$session->start() )
                            {// (Unable to start the session)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => [ 'Unable to start the session' ] ] ] ) )
                                ;
                            }

                            if ( !$session->regenerate_id() )
                            {// (Unable to regenerate the session id)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => [ 'Unable to regenerate the session id' ] ] ] ) )
                                ;
                            }

                            if ( !$session->set_duration() )
                            {// (Unable to set the session duration)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => [ 'Unable to set the session duration' ] ] ] ) )
                                ;
                            }



                            // (Setting the value)
                            $session->data = [];



                            // (Getting the value)
                            $session->data['user'] = $user->id;



                            // (Setting the value)
                            $session->data['login_method'] = 'IDK';



                            // (Listening for the event)
                            $session->add_event_listener
                            (
                                'save',
                                function () use ($user, &$session)
                                {
                                    // (Getting the value)
                                    $client = ClientService::detect();



                                    // (Getting the value)
                                    $record =
                                    [
                                        'login_method'    => $session->data['login_method'],

                                        'ip.address'      => $client['ip']['address'],
                                        'ip.country.code' => $client['ip']['country']['code'],
                                        'ip.country.name' => $client['ip']['country']['name'],
                                        'ip.isp'          => $client['ip']['isp'],
                                        'user_agent'      => $client['user_agent'],
                                        'browser'         => $client['browser'],
                                        'os'              => $client['os'],
                                        'hw'              => $client['hw'],

                                        'user'            => $user->id,
                                        'session'         => $session->id
                                    ]
                                    ;

                                    if ( AccessDBModel::fetch()->insert( [ $record ] ) === false )
                                    {// (Unable to insert the record)
                                        // Returning the value
                                        return
                                            Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to to register the access' ] ] ) )
                                        ;
                                    }
                                }
                            )
                            ;



                            // Returning the value
                            return
                                Server::send( new Response( new Status(200), [], [ 'location' => LoginService::extract_location() ] ) )
                            ;
                        }
                        else
                        {// Value not found
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
                                $session = SessionDBModel::fetch()->filter( [ [ 'id' => $authorization->data->request->input->session ] ] )->find();

                                if ( $session === false )
                                {// (Session not found)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Session not found' ] ] ) )
                                    ;
                                }



                                // (Getting the value)
                                $login_method = json_decode( $session->data, true )['login_method'];



                                // (Getting the value)
                                $record =
                                [
                                    'data' => json_encode
                                    (
                                        [
                                            'user'         => $authorization->data->request->input->user,
                                            'login_method' => $login_method
                                        ]
                                    )
                                ]
                                ;

                                if ( SessionDBModel::fetch()->filter( [ [ 'id' => $session->id ] ] )->update($record) === false )
                                {// (Unable to update the record)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to change the session' ] ] ) )
                                    ;
                                }



                                // (Getting the value)
                                $client = $authorization->data->client;



                                // (Getting the value)
                                $record =
                                [
                                    'login_method'    => $login_method,

                                    'ip.address'      => $client->ip->address,
                                    'ip.country.code' => $client->ip->country->code,
                                    'ip.country.name' => $client->ip->country->name,
                                    'ip.isp'          => $client->ip->isp,
                                    'user_agent'      => $client->user_agent,
                                    'browser'         => $client->browser,
                                    'os'              => $client->os,
                                    'hw'              => $client->hw,

                                    'user'            => $authorization->data->request->input->user,
                                    'session'         => $authorization->data->request->input->session
                                ]
                                ;

                                if ( AccessDBModel::fetch()->insert( [ $record ] ) === false )
                                {// (Unable to insert the record)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to to register the access' ] ] ) )
                                    ;
                                }



                                // Returning the value
                                return
                                    Server::send( new Response( new Status(200) ) )
                                ;
                            }
                            else
                            {// Value not found
                                // (Getting the value)
                                $user = UserDBModel::fetch()->filter( [ [ 'username' => RPC::$input->username ] ] )->find();

                                if ( $user === false )
                                {// (User not found)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Login failed' ] ] ) )
                                    ;
                                }



                                if ( $user->security->idk->authentication )
                                {// Value is true
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Login failed' ] ] ) )
                                    ;
                                }
                                else
                                {// Value is false
                                    if ( !Password::create( RPC::$input->password )->verify( $user->security->password ) )
                                    {// (Password is not the same)
                                        // Returning the value
                                        return
                                            Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Login failed' ] ] ) )
                                        ;
                                    }
                                }



                                // (Getting the value)
                                $session = SessionsStore::fetch()->sessions['user'];



                                if ( !$session->start() )
                                {// (Unable to start the session)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => [ 'Unable to start the session' ] ] ] ) )
                                    ;
                                }

                                if ( !$session->regenerate_id() )
                                {// (Unable to regenerate the session id)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => [ 'Unable to regenerate the session id' ] ] ] ) )
                                    ;
                                }

                                if ( !$session->set_duration() )
                                {// (Unable to set the session duration)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => [ 'Unable to set the session duration' ] ] ] ) )
                                    ;
                                }



                                // (Setting the value)
                                $session->data = [];



                                if ( $user->security->mfa )
                                {// Value is true
                                    // (Starting the authorization)
                                    $response = AuthorizationService::start
                                    (
                                        null,
                                        [
                                            'request'           =>
                                            [
                                                'endpoint_path' => $app->request->url->path,
                                                'action'        => $app->request->headers['Action'],
                                                'input'         =>
                                                [
                                                    'user'      => $user->id,
                                                    'session'   => $session->id,
                                                    'fwd_route' => $app->request->cookies['fwd_route'] ?? false
                                                ]
                                            ],

                                            'client'            => ClientService::detect()
                                        ],

                                        $user->email,
                                        'LOGIN'
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



                                    // (Setting the value)
                                    $session->data['login_method'] = 'MFA';



                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(200), [], [ 'mfa' => true, 'exp_time' => $response->body['exp_time'] ] ) )
                                    ;
                                }
                                else
                                {// Value is false
                                    // (Getting the value)
                                    $session->data['user'] = $user->id;



                                    // (Setting the value)
                                    $session->data['login_method'] = 'BASIC';



                                    // (Listening for the event)
                                    $session->add_event_listener
                                    (
                                        'save',
                                        function () use ($user, &$session)
                                        {
                                            // (Getting the value)
                                            $client = ClientService::detect();
    
    
    
                                            // (Getting the value)
                                            $record =
                                            [
                                                'login_method'    => $session->data['login_method'],
    
                                                'ip.address'      => $client['ip']['address'],
                                                'ip.country.code' => $client['ip']['country']['code'],
                                                'ip.country.name' => $client['ip']['country']['name'],
                                                'ip.isp'          => $client['ip']['isp'],
                                                'user_agent'      => $client['user_agent'],
                                                'browser'         => $client['browser'],
                                                'os'              => $client['os'],
                                                'hw'              => $client['hw'],
    
                                                'user'            => $user->id,
                                                'session'         => $session->id
                                            ]
                                            ;
    
                                            if ( AccessDBModel::fetch()->insert( [ $record ] ) === false )
                                            {// (Unable to insert the record)
                                                // Returning the value
                                                return
                                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to to register the access' ] ] ) )
                                                ;
                                            }
                                        }
                                    )
                                    ;



                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(200), [], [ 'location' => LoginService::extract_location() ] ) )
                                    ;
                                }
                            }
                        }
                    break;

                    case 'logout':
                        if ( UserMiddleware::run() === false ) return;



                        // (Getting the value)
                        $session = SessionsStore::fetch()->sessions['user'];

                        if ( !$session->destroy() )
                        {// (Unable to destroy the session)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => [ 'Unable to destroy the session' ] ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200) ) )
                        ;
                    break;

                    case 'mark_changelog_as_read':
                        if ( UserMiddleware::run() === false ) return;



                        // (Getting the value)
                        $user_id = SessionsStore::fetch()->sessions['user']->data['user'];



                        // (Getting the value)
                        $record =
                        [
                            'datetime.changelog_mark_as_read' => \Solenoid\MySQL\DateTime::fetch()
                        ]
                        ;

                        if ( UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->update($record) === false )
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
                }
            break;

            case 'account':
                switch ( RPC::$verb )
                {
                    case 'recover':
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
                            //$authorization = $response->data['authorization'];



                            // Returning the value
                            return
                                Server::send( new Response( new Status(200) ) )
                            ;
                        }
                        else
                        {// Value not found
                            // (Getting the value)
                            $user = UserDBModel::fetch()->filter( [ [ 'email' => RPC::$input->email ] ] )->get();

                            if ( $user === false )
                            {// (User not found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(200) ) )
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
                                            'email'                   => RPC::$input->email
                                        ]
                                    ],

                                    'login'                           => true
                                ],

                                RPC::$input->email,
                                'LOGIN'
                            )
                            ;



                            // Returning the value
                            return
                                Server::send( new Response( new Status(200) ) )
                            ;
                        }
                    break;
                }
            break;
        }
    }
}



?>