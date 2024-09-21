<?php



namespace App\Controllers;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\HTTP\Request;
use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Status;
use \Solenoid\HTTP\Response;

use \Solenoid\MySQL\DateTime;

use \Solenoid\Core\App\WebApp;

use \Solenoid\Encryption\KeyPair;
use \Solenoid\Encryption\RSA;
use \Solenoid\IDK\IDK;

use \Solenoid\RPC\Request as RPCRequest;

use \App\Middlewares\RPC\Parser as RPCParser;
use \App\Models\local\simba_db\User as UserModel;
use \App\Models\local\simba_db\Group as GroupModel;
use \App\Models\local\simba_db\Activity as ActivityModel;
use App\Models\local\simba_db\Hierarchy as HierarchyModel;
use \App\Models\local\simba_db\Session as SessionModel;
use \App\Services\Authorization as AuthorizationService;
use \App\Services\User as UserService;
use \App\Services\Client as ClientService;
use \App\Services\Login as LoginService;
use \App\Stores\Sessions\Store as SessionsStore;
use \App\Stores\Cookies\Store as CookiesStore;



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
                            Server::send( new Response( new Status(200), [], RPCRequest::fetch()->parse_body() ) )
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



                            // (Getting the value)
                            $input = RPCRequest::fetch()->parse_body();



                            // (Starting an authorization)
                            $response = AuthorizationService::start
                            (
                                [
                                    'request'           =>
                                    [
                                        'endpoint_path' => $app->request->url->path,
                                        'action'        => $app->request->headers['Action'],
                                        'input'         => $input
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



                            // (Sending the authorization)
                            $response = AuthorizationService::send( $response->body['token'], $input['user']['email'], RPCParser::$subject . '.' . RPCParser::$verb );
                            
                            if ( $response->status->code !== 200 )
                            {// (Unable to send the authorization)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to send the authorization' ] ] ) )
                                ;
                            }



                            // Returning the value
                            return Server::send( new Response( new Status(200) ) );
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



                        // (Setting the value)
                        $data = [];



                        switch ( $app->request->headers['Route'] )
                        {
                            case '/admin/dashboard':
                            case '/admin/activity_log':
                            case '/admin/users':
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
                                $data['user'] = $response->body;
                            break;
                        }



                        switch ( $app->request->headers['Route'] )
                        {
                            case '/admin/activity_log':
                                // (Getting the value)
                                $data['records'] = ActivityModel::fetch()->where( 'user', $user_id )->list();

                                foreach ( $data['records'] as &$record )
                                {// Processing each entry
                                    // (Getting the value)
                                    $record = (array) $record;



                                    // (Getting the value)
                                    $record['current_session'] = $record['session'] && $record['session'] !== $session->id;
                                }
                            break;

                            case '/admin/users':
                                // (Getting the value)
                                $user = UserModel::fetch()->where( 'id', $user_id )->find();

                                if ( !$user )
                                {// (Record not found)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Record not found (user)' ] ] ) )
                                    ;
                                }



                                // (Getting the value)
                                $hierarchies = HierarchyModel::fetch()->list();

                                foreach ( $hierarchies as $hierarchy )
                                {// Processing each entry
                                    // (Getting the value)
                                    $data['hierarchies'][ $hierarchy->id ] = $hierarchy;
                                }
                            


                                // (Getting the value)
                                $data['records'] = UserModel::fetch()->where( 'group', $user->group )->list
                                (
                                    transform_record: function ($record)
                                    {
                                        // (Getting the value)
                                        $record =
                                        [
                                            'name'        => $record->name,
                                            'email'       => $record->email,

                                            'hierarchy'   => $record->hierarchy,

                                            'birth'       =>
                                            [
                                                'name'    => $record->birth->name,
                                                'surname' => $record->birth->surname,
                                            ],

                                            'datetime'    =>
                                            [
                                                'insert'  => $record->datetime->insert
                                            ]
                                        ]
                                        ;



                                        // Returning the value
                                        return $record;
                                    }
                                )
                                ;
                            break;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [], $data ) )
                        ;
                    break;

                    case 'change_password':
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
                        $input = RPCRequest::fetch()->parse_body();



                        // (Getting the value)
                        $record =
                        [
                            'security.password' => password_hash( $input['password'], PASSWORD_BCRYPT ),

                            'datetime.update'   => DateTime::fetch()
                        ]
                        ;

                        if ( UserModel::fetch()->where( 'id', $user_id )->update( $record ) === false )
                        {// (Unable to update the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to update the record (user)" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $response = ClientService::detect();

                        if ( $response->status->code !== 200 )
                        {// (Unable to detect the client)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to detect the client" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'user'                 => $user_id,
                            'action'               => RPCParser::$subject . '.' . RPCParser::$verb,
                            'session'              => $session->id,
                            'ip'                   => $_SERVER['REMOTE_ADDR'],
                            'user_agent'           => $_SERVER['HTTP_USER_AGENT'],
                            'ip_info.country.code' => $response->body['ip']['country']['code'],
                            'ip_info.country.name' => $response->body['ip']['country']['name'],
                            'ip_info.isp'          => $response->body['ip']['isp'],
                            'ua_info.browser'      => $response->body['ua']['browser'],
                            'ua_info.os'           => $response->body['ua']['os'],
                            'ua_info.hw'           => $response->body['ua']['hw'],
                            'datetime.insert'      => DateTime::fetch()
                        ]
                        ;

                        if ( ActivityModel::fetch()->insert( [ $record ] ) === false )
                        {// (Unable to insert the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200) ) )
                        ;
                    break;

                    case 'change_mfa':
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
                        $input = RPCRequest::fetch()->parse_body();



                        // (Getting the value)
                        $record =
                        [
                            'security.mfa'    => $input['security.mfa'],

                            'datetime.update' => DateTime::fetch()
                        ]
                        ;

                        if ( UserModel::fetch()->where( 'id', $user_id )->update( $record ) === false )
                        {// (Unable to update the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to update the record (user)" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $response = ClientService::detect();

                        if ( $response->status->code !== 200 )
                        {// (Unable to detect the client)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to detect the client" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'user'                 => $user_id,
                            'action'               => RPCParser::$subject . '.' . RPCParser::$verb,
                            'session'              => $session->id,
                            'ip'                   => $_SERVER['REMOTE_ADDR'],
                            'user_agent'           => $_SERVER['HTTP_USER_AGENT'],
                            'ip_info.country.code' => $response->body['ip']['country']['code'],
                            'ip_info.country.name' => $response->body['ip']['country']['name'],
                            'ip_info.isp'          => $response->body['ip']['isp'],
                            'ua_info.browser'      => $response->body['ua']['browser'],
                            'ua_info.os'           => $response->body['ua']['os'],
                            'ua_info.hw'           => $response->body['ua']['hw'],
                            'datetime.insert'      => DateTime::fetch()
                        ]
                        ;

                        if ( ActivityModel::fetch()->insert( [ $record ] ) === false )
                        {// (Unable to insert the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200) ) )
                        ;
                    break;

                    case 'change_idk':
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
                        $input = RPCRequest::fetch()->parse_body();



                        // (Getting the value)
                        $idk_authentication = $input['security.idk.authentication'];

                        if ( $idk_authentication )
                        {// Value is true
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
                            $app = WebApp::fetch();



                            // (Getting the value)
                            $idk = ( new IDK( $user_id, $key_pair->private_key ) )->build( $app->fetch_credentials()['idk']['passphrase'], true );

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
                                'security.idk.authentication' => $idk_authentication,
                                'security.idk.public_key'     => $key_pair->public_key,
                                'security.idk.signature'      => base64_encode( RSA::select( 'idk' )->encrypt( $key_pair->public_key ) ),

                                'datetime.update'             => DateTime::fetch()
                            ]
                            ;
                        }
                        else
                        {// Value is false
                            // (Setting the value)
                            $idk = '';



                            // (Getting the value)
                            $record =
                            [
                                'security.idk.authentication' => $idk_authentication,
                                'security.idk.public_key'     => null,
                                'security.idk.signature'      => null,

                                'datetime.update'             => DateTime::fetch()
                            ]
                            ;
                        }



                        if ( UserModel::fetch()->where( 'id', $user_id )->update( $record ) === false )
                        {// (Unable to update the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to update the record (user)" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $response = ClientService::detect();

                        if ( $response->status->code !== 200 )
                        {// (Unable to detect the client)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to detect the client" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'user'                 => $user_id,
                            'action'               => RPCParser::$subject . '.' . RPCParser::$verb,
                            'session'              => $session->id,
                            'ip'                   => $_SERVER['REMOTE_ADDR'],
                            'user_agent'           => $_SERVER['HTTP_USER_AGENT'],
                            'ip_info.country.code' => $response->body['ip']['country']['code'],
                            'ip_info.country.name' => $response->body['ip']['country']['name'],
                            'ip_info.isp'          => $response->body['ip']['isp'],
                            'ua_info.browser'      => $response->body['ua']['browser'],
                            'ua_info.os'           => $response->body['ua']['os'],
                            'ua_info.hw'           => $response->body['ua']['hw'],
                            'datetime.insert'      => DateTime::fetch()
                        ]
                        ;

                        if ( ActivityModel::fetch()->insert( [ $record ] ) === false )
                        {// (Unable to insert the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [ 'Content-Type: text/plain' ], $idk ) )
                        ;
                    break;

                    case 'login':
                        // (Getting the value)
                        $request = Request::fetch();



                        if ( $request->headers['Auth-Token'] )
                        {// Value found
                            if ( $request->client_ip !== $request->server_ip )
                            {// (Request is not from localhost)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $res = AuthorizationService::fetch( $request->headers['Auth-Token'] );

                            if ( $res->status->code !== 200 )
                            {// (Unable to fetch the authorization)
                                // Returning the value
                                return
                                    Server::send( $res )
                                ;
                            }



                            // (Getting the value)
                            $authorization = $res->body;



                            // (Getting the value)
                            $record =
                            [
                                'data'            => json_encode
                                (
                                    [
                                        'user'    => $authorization->data['request']['input']['user']
                                    ]
                                ),

                                'datetime.update' => DateTime::fetch()
                            ]
                            ;

                            if ( SessionModel::fetch()->where( 'id', $authorization->data['request']['input']['session'] )->update( $record ) === false )
                            {// (Unable to update the record)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to update the record (session)' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $response = ClientService::detect( $authorization->data['request']['input']['ip'], $authorization->data['request']['input']['user_agent'] );

                            if ( $response->status->code !== 200 )
                            {// (Unable to detect the client)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to detect the client" ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $record =
                            [
                                'user'                 => $authorization->data['request']['input']['user'],
                                'action'               => str_replace( '::', '.', $authorization->data['request']['action'] ),
                                'session'              => $authorization->data['request']['input']['session'],
                                'ip'                   => $authorization->data['request']['input']['ip'],
                                'user_agent'           => $authorization->data['request']['input']['user_agent'],
                                'ip_info.country.code' => $response->body['ip']['country']['code'],
                                'ip_info.country.name' => $response->body['ip']['country']['name'],
                                'ip_info.isp'          => $response->body['ip']['isp'],
                                'ua_info.browser'      => $response->body['ua']['browser'],
                                'ua_info.os'           => $response->body['ua']['os'],
                                'ua_info.hw'           => $response->body['ua']['hw'],
                                'datetime.insert'      => DateTime::fetch()
                            ]
                            ;

                            if ( !ActivityModel::fetch()->insert( [ $record ] ) )
                            {// (Unable to insert the record)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) )
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
                            $input = RPCRequest::fetch()->parse_body();



                            // (Getting the values)
                            [ $user, $group ] = explode( '@', $input['login'] );
                            $password         = $input['password'];



                            // (Getting the value)
                            $group = GroupModel::fetch()->where( 'name', $group )->find();

                            if ( !$group )
                            {// (Record not found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $user = UserModel::fetch()->where( [ [ 'group', $group->id ], [ 'name', $user ] ] )->find();

                            if ( !$user )
                            {// (Record not found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                ;
                            }



                            if ( $user->security->idk->authentication )
                            {// Value not found
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                ;
                            }



                            if ( $user->security->password === null )
                            {// Value not found
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                ;
                            }

                            if ( !password_verify( $password, $user->security->password ) )
                            {// Match failed
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $session = SessionsStore::fetch()->sessions['user'];

                            if ( !$session->start() )
                            {// (Unable to start the session)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to start the session' ] ] ) )
                                ;
                            }

                            if ( !$session->regenerate_id() )
                            {// (Unable to regenerate the session id)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to regenerate the session id' ] ] ) )
                                ;
                            }

                            if ( !$session->set_duration() )
                            {// (Unable to set the session duration)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to set the session duration' ] ] ) )
                                ;
                            }



                            // (Setting the value)
                            $session->data = [];



                            if ( $user->security->mfa === 1 )
                            {// (Login method is MFA)
                                // (Getting the value)
                                $data =
                                [
                                    'request'            =>
                                    [
                                        'endpoint_path'  => $request->url->path,
                                        'action'         => $request->headers['Action'],
                                        'input'          =>
                                        [
                                            'session'    => $session->id,
                                            'user'       => $user->id,

                                            'ip'         => $_SERVER['REMOTE_ADDR'],
                                            'user_agent' => $_SERVER['HTTP_USER_AGENT']
                                        ]
                                    ]
                                ]
                                ;

                                // (Starting the authorization)
                                $response = AuthorizationService::start( $data );

                                if ( $response->status->code !== 200 )
                                {// (Unable to start the authorization)
                                    // Returning the value
                                    return
                                        Server::send( $response )
                                    ;
                                }



                                // (Getting the value)
                                $token = $response->body['token'];



                                // (Sending the authorization)
                                $response = AuthorizationService::send( $token, $user->email, implode( '.', [ RPCParser::$subject, RPCParser::$verb ] ) );

                                if ( $response->status->code !== 200 )
                                {// (Unable to send the authorization)
                                    // Returning the value
                                    return
                                        Server::send( $response )
                                    ;
                                }



                                // (Getting the value)
                                $session->data['authorization'] = $token;



                                // Returning the value
                                return
                                    Server::send( new Response( new Status(200) ) )
                                ;
                            }
                            else
                            {// (Login method is BASIC)
                                // (Getting the value)
                                $session->data['user'] = $user->id;




                                // Returning the value
                                return
                                    Server::send( new Response( new Status(200), [], [ 'location' => LoginService::extract_location() ] ) )
                                ;
                            }
                        }
                    break;

                    case 'login_wait':
                        // (Getting the value)
                        $session = SessionsStore::fetch()->sessions['user'];

                        if ( !$session->start() )
                        {// (Unable to start the session)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to start the session' ] ] ) )
                            ;
                        }

                        
 
                        // (Getting the value)
                        $token = $session->data['authorization'];

                        if ( !$token )
                        {// Value not found
                            if ( !$session->destroy() )
                            {// (Unable to destroy the session)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to destroy the session' ] ] ) )
                                ;
                            }


                            // Returning the value
                            return
                                Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                            ;
                        }



                        // (Setting the time limit)
                        set_time_limit(60);



                        while (true)
                        {// Processing each clock
                            // (Getting the value)
                            $response = AuthorizationService::fetch( $token );

                            if ( $response->status->code === 404 )
                            {// (Authorization not found)
                                // (Closing the session)
                                $session->close();



                                // (Removing the cookie)
                                CookiesStore::fetch()->cookies['fwd_route']->set( '', -1 );



                                // Returning the value
                                return
                                    Server::send( new Response( new Status(200), [], [ 'location' => LoginService::extract_location() ] ) )
                                ;
                            }



                            // (Waiting for the time)
                            sleep(2);
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(408) ) )
                        ;
                    break;

                    case 'login_with_idk':
                        // (Getting the value)
                        $app = WebApp::fetch();



                        // (Getting the value)
                        $idk = IDK::read( $app->request->body, $app->fetch_credentials()['idk']['passphrase'], true );



                        if ( $idk->user )
                        {// Value found
                            // (Getting the value)
                            $user = UserModel::fetch()->where( 'id', $idk->user )->find();
                        }
                        else
                        if ( $idk->data['username'] )
                        {// Value found
                            // (Getting the value)
                            $user = UserModel::fetch()->where( 'username', $idk->data['username'] )->find();
                        }
                        else
                        {// Match failed
                            // Returning the value
                            return
                                Server::send( new Response( new Status(400), [], [ 'error' => [ 'message' => 'IDK is not valid' ] ] ) )
                            ;
                        }



                        if ( $user === false )
                        {// (User not found)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                            ;
                        }



                        if ( !$user->security->idk->authentication === 1 )
                        {// Value is false
                            // Returning the value
                            return
                                Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                            ;
                        }



                        if ( RSA::select( base64_decode( $user->security->idk->signature ) )->decrypt( $idk->key )->value !== 'idk' )
                        {// (Key is not valid)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
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



                        // (Listening for the event)
                        $session->add_event_listener
                        (
                            'save',
                            function () use ($user, &$session)
                            {
                                // (Getting the value)
                                $response = ClientService::detect();

                                if ( $response->status->code !== 200 )
                                {// (Unable to detect the client)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to detect the client" ] ] ) )
                                    ;
                                }



                                // (Getting the value)
                                $record =
                                [
                                    'user'                 => $user->id,
                                    'action'               => RPCParser::$subject . '.' . RPCParser::$verb,
                                    'session'              => $session->id,
                                    'ip'                   => $_SERVER['REMOTE_ADDR'],
                                    'user_agent'           => $_SERVER['HTTP_USER_AGENT'],
                                    'ip_info.country.code' => $response->body['ip']['country']['code'],
                                    'ip_info.country.name' => $response->body['ip']['country']['name'],
                                    'ip_info.isp'          => $response->body['ip']['isp'],
                                    'ua_info.browser'      => $response->body['ua']['browser'],
                                    'ua_info.os'           => $response->body['ua']['os'],
                                    'ua_info.hw'           => $response->body['ua']['hw'],
                                    'datetime.insert'      => DateTime::fetch()
                                ]
                                ;

                                if ( ActivityModel::fetch()->insert( [ $record ] ) === false )
                                {// (Unable to insert the record)
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) )
                                    ;
                                }
                            }
                        )
                        ;



                        // (Removing the cookie)
                        CookiesStore::fetch()->cookies['fwd_route']->set( '', -1 );



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200), [], [ 'location' => LoginService::extract_location() ] ) )
                        ;
                    break;

                    case 'logout':
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



                        // (Setting the value)
                        $session->data = [];



                        if ( !$session->destroy() )
                        {// (Unable to destroy the session)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to destroy the session' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $response = ClientService::detect();

                        if ( $response->status->code !== 200 )
                        {// (Unable to detect the client)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to detect the client" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'user'                 => $user_id,
                            'action'               => RPCParser::$subject . '.' . RPCParser::$verb,
                            'session'              => null,
                            'ip'                   => $_SERVER['REMOTE_ADDR'],
                            'user_agent'           => $_SERVER['HTTP_USER_AGENT'],
                            'ip_info.country.code' => $response->body['ip']['country']['code'],
                            'ip_info.country.name' => $response->body['ip']['country']['name'],
                            'ip_info.isp'          => $response->body['ip']['isp'],
                            'ua_info.browser'      => $response->body['ua']['browser'],
                            'ua_info.os'           => $response->body['ua']['os'],
                            'ua_info.hw'           => $response->body['ua']['hw'],
                            'datetime.insert'      => DateTime::fetch()
                        ]
                        ;

                        if ( ActivityModel::fetch()->insert( [ $record ] ) === false )
                        {// (Unable to insert the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200) ) )
                        ;
                    break;

                    case 'change_name':
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
                        $user = UserModel::fetch()->where( 'id', $user_id )->find();

                        if ( !$user )
                        {// (Record not found)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Record not found (user)' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $input = RPCRequest::fetch()->parse_body();



                        if ( UserModel::fetch()->where( [ [ 'group', $user->group ], [ 'name', $input['name'] ] ] )->exists() )
                        {// (Record found)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => "['group','name'] already exists (user)" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'name'            => $input['name'],

                            'datetime.update' => DateTime::fetch()
                        ]
                        ;

                        if ( UserModel::fetch()->where( 'id', $user_id )->update( $record ) === false )
                        {// (Unable to update the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to update the record (user)" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $response = ClientService::detect();

                        if ( $response->status->code !== 200 )
                        {// (Unable to detect the client)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to detect the client" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'user'                 => $user_id,
                            'action'               => RPCParser::$subject . '.' . RPCParser::$verb,
                            'session'              => null,
                            'ip'                   => $_SERVER['REMOTE_ADDR'],
                            'user_agent'           => $_SERVER['HTTP_USER_AGENT'],
                            'ip_info.country.code' => $response->body['ip']['country']['code'],
                            'ip_info.country.name' => $response->body['ip']['country']['name'],
                            'ip_info.isp'          => $response->body['ip']['isp'],
                            'ua_info.browser'      => $response->body['ua']['browser'],
                            'ua_info.os'           => $response->body['ua']['os'],
                            'ua_info.hw'           => $response->body['ua']['hw'],
                            'datetime.insert'      => DateTime::fetch()
                        ]
                        ;

                        if ( ActivityModel::fetch()->insert( [ $record ] ) === false )
                        {// (Unable to insert the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200) ) )
                        ;
                    break;

                    case 'change_email':
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



                            // (Starting an authorization)
                            $response = AuthorizationService::start
                            (
                                [
                                    'request'           =>
                                    [
                                        'endpoint_path' => $authorization->data['request']['endpoint_path'],
                                        'action'        => 'user::confirm_new_email',
                                        'input'         => $authorization->data['request']['input']
                                    ]
                                ]
                            )
                            ;
                            
                            if ( $response->status->code !== 200 )
                            {// (Unable to start the authorization)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to start the authorization' ] ] ) )
                                ;
                            }



                            // (Sending the authorization)
                            $response = AuthorizationService::send( $response->body['token'], $authorization->data['request']['input']['new_value'], 'user.confirm_new_email' );
                            
                            if ( $response->status->code !== 200 )
                            {// (Unable to send the authorization)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to send the authorization' ] ] ) )
                                ;
                            }



                            // Returning the value
                            return
                                Server::send( new Response( new Status(200) ) )
                            ;
                        }
                        else
                        {// (Authorization has not been provided)
                            // (Verifying the user)
                            $response = UserService::verify();

                            if ( $response->status->code !== 200 )
                            {// (Session is not valid)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $session = SessionsStore::fetch()->sessions['user'];
    
    
    
                            // (Getting the value)
                            $user_id = $session->data['user'];
    
    
    
                            // (Getting the value)
                            $user = UserModel::fetch()->where( 'id', $user_id )->find();
    
                            if ( !$user )
                            {// (Record not found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Record not found (user)' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $input = RPCRequest::fetch()->parse_body();



                            if ( UserModel::fetch()->where( 'email', $input['email'] )->exists() )
                            {// (Record found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => "['email'] already exists (user)" ] ] ) )
                                ;
                            }



                            // (Starting an authorization)
                            $response = AuthorizationService::start
                            (
                                [
                                    'request'           =>
                                    [
                                        'endpoint_path' => $app->request->url->path,
                                        'action'        => $app->request->headers['Action'],
                                        'input'         =>
                                        [
                                            'user'      => $user_id,
                                            'new_value' => $input['email']
                                        ]
                                    ],

                                    'display'           => 'Confirm operation by email <b>' . $input['email'] . '</b> ...'
                                ]
                            )
                            ;
                            
                            if ( $response->status->code !== 200 )
                            {// (Unable to start the authorization)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to start the authorization' ] ] ) )
                                ;
                            }



                            // (Sending the authorization)
                            $response = AuthorizationService::send( $response->body['token'], $user->email, RPCParser::$subject . '.' . RPCParser::$verb );
                            
                            if ( $response->status->code !== 200 )
                            {// (Unable to send the authorization)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to send the authorization' ] ] ) )
                                ;
                            }



                            // Returning the value
                            return Server::send( new Response( new Status(200) ) );
                        }
                    break;

                    case 'confirm_new_email':
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



                            // (Getting the value)
                            $record =
                            [
                                'email' => $authorization->data['request']['input']['new_value']
                            ]
                            ;

                            if ( !UserModel::fetch()->where( 'id', $authorization->data['request']['input']['user'] )->update( $record ) )
                            {// (Unable to update the record)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to update the record (user)' ] ] ) )
                                ;
                            }



                            // Returning the value
                            return
                                Server::send( new Response( new Status(200) ) )
                            ;
                        }
                    break;

                    case 'change_birth_data':
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
                        $input = RPCRequest::fetch()->parse_body();



                        // (Getting the value)
                        $record =
                        [
                            'birth.name'      => $input['birth.name'],
                            'birth.surname'   => $input['birth.surname'],

                            'datetime.update' => DateTime::fetch()
                        ]
                        ;

                        if ( UserModel::fetch()->where( 'id', $user_id )->update( $record ) === false )
                        {// (Unable to update the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to update the record (user)" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $response = ClientService::detect();

                        if ( $response->status->code !== 200 )
                        {// (Unable to detect the client)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to detect the client" ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'user'                 => $user_id,
                            'action'               => RPCParser::$subject . '.' . RPCParser::$verb,
                            'session'              => null,
                            'ip'                   => $_SERVER['REMOTE_ADDR'],
                            'user_agent'           => $_SERVER['HTTP_USER_AGENT'],
                            'ip_info.country.code' => $response->body['ip']['country']['code'],
                            'ip_info.country.name' => $response->body['ip']['country']['name'],
                            'ip_info.isp'          => $response->body['ip']['isp'],
                            'ua_info.browser'      => $response->body['ua']['browser'],
                            'ua_info.os'           => $response->body['ua']['os'],
                            'ua_info.hw'           => $response->body['ua']['hw'],
                            'datetime.insert'      => DateTime::fetch()
                        ]
                        ;

                        if ( ActivityModel::fetch()->insert( [ $record ] ) === false )
                        {// (Unable to insert the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to insert the record (activity)" ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200) ) )
                        ;
                    break;

                    case 'recover':
                        // (Getting the value)
                        $request = Request::fetch();



                        if ( $request->headers['Auth-Token'] )
                        {// Value found
                            if ( $request->client_ip !== $request->server_ip )
                            {// (Request is not from localhost)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) )
                                ;
                            }



                            // (Getting the value)
                            $res = AuthorizationService::fetch( $request->headers['Auth-Token'] );

                            if ( $res->status->code !== 200 )
                            {// (Unable to fetch the authorization)
                                // Returning the value
                                return
                                    Server::send( $res )
                                ;
                            }



                            // (Getting the value)
                            $authorization = $res->body;



                            // Returning the value
                            return
                                Server::send( new Response( new Status(200), [ 'Content-Type: application/json' ], $authorization->data['request']['input']['user'] ) )
                            ;
                        }
                        else
                        {// Value not found
                            // (Getting the value)
                            $input = RPCRequest::fetch()->parse_body();



                            // (Getting the value)
                            $user = UserModel::fetch()->where( 'email', $input['email'] )->find();

                            if ( !$user )
                            {// (Record not found)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(200) ) )
                                ;
                            }

                        

                            // (Getting the value)
                            $data =
                            [
                                'request'            =>
                                [
                                    'endpoint_path'  => $request->url->path,
                                    'action'         => $request->headers['Action'],
                                    'input'          =>
                                    [
                                        'user'       => $user->id,

                                        'ip'         => $_SERVER['REMOTE_ADDR'],
                                        'user_agent' => $_SERVER['HTTP_USER_AGENT']
                                    ]
                                ],

                                'login'              => true
                            ]
                            ;

                            // (Starting the authorization)
                            $response = AuthorizationService::start( $data, $request->url->fetch_base() . '/admin/dashboard' );

                            if ( $response->status->code !== 200 )
                            {// (Unable to start the authorization)
                                // Returning the value
                                return
                                    Server::send( $response )
                                ;
                            }



                            // (Getting the value)
                            $token = $response->body['token'];



                            // (Sending the authorization)
                            $response = AuthorizationService::send( $token, $user->email, str_replace( '::', '.', $request->headers['Action'] ) );

                            if ( $response->status->code !== 200 )
                            {// (Unable to send the authorization)
                                // Returning the value
                                return
                                    Server::send( $response )
                                ;
                            }



                            // Returning the value
                            return
                                Server::send( new Response( new Status(200) ) )
                            ;
                        }
                    break;

                    case 'terminate_session':
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
                        $input = RPCRequest::fetch()->parse_body();



                        // (Getting the value)
                        $activity = ActivityModel::fetch()->where( [ [ 'user', $user_id ], [ 'id', $input['id'] ] ] )->find();

                        if ( !$activity )
                        {// (Record not found)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Record not found (activity)' ] ] ) )
                            ;
                        }



                        if ( !SessionModel::fetch()->where( 'id', $activity->session )->delete() )
                        {// (Unable to delete the records)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [  'error' => [ 'message' => "Unable to delete the records (session)" ] ] ) )
                            ;
                        }



                        // Returning the value
                        return
                            Server::send( new Response( new Status(200) ) )
                        ;
                    break;

                    /*case 'add':
                        // (Verifying the user)
                        $response = UserService::verify( 1 );

                        if ( $response->status->code !== 200 )
                        {// (Verification is failed)
                            // Closing the process
                            exit( Server::send( $response ) );
                        }



                        // (Getting the value)
                        $session = SessionsStore::fetch()->sessions['user'];



                        // (Getting the value)
                        $user_id = $session->data['user'];



                        // (Getting the value)
                        $user = UserModel::where( 'id', $user_id )->first();

                        if ( !$user )
                        {// Value not found
                            // Closing the process
                            exit( Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Record not found (user)' ] ] ) ) );
                        }



                        if ( UserModel::where( [ [ 'group', $user->group ], [ 'name', $rpc->input['name'] ] ] )->count() > 0 )
                        {// (Record found)
                            // Closing the process
                            exit( Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => "['name'] already exists (user)" ] ] ) ) );
                        }

                        if ( UserModel::where( [ [ 'group', $user->group ], [ 'email', $rpc->input['email'] ] ] )->count() > 0 )
                        {// (Record found)
                            // Closing the process
                            exit( Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => "['email'] already exists (user)" ] ] ) ) );
                        }



                        // (Getting the value)
                        $app = AppStore::fetch();



                        // (Sending an http request)
                        $response = Client::send
                        (
                            'https://' . $app->app['id'] . '/rpc',
                            'POST',
                            [
                                'Action: user::register',
                                'Content-Type: application/json',

                                'User-Agent: FEV'
                            ],
                            json_encode
                            (
                                [
                                    'group'         =>
                                    [
                                        'id'        => $user->group
                                    ],

                                    'user'          =>
                                    [
                                        'name'      => $rpc->input['name'],
                                        'email'     => $rpc->input['email'],
                                        'hierarchy' => $rpc->input['hierarchy']
                                    ]
                                ]
                            )
                        )
                        ;

                        // Closing the process
                        exit( Server::send( new Response( new Status( $response->fetch_tail()->status->code ), [], $response->body ) ) );
                    break;

                    case 'remove':
                        // (Getting the value)
                        $request = Request::fetch();



                        if ( $request->headers['Auth-Token'] )
                        {// (Authorization has been provided)
                            if ( $request->client_ip !== $request->server_ip )
                            {// (Request is not from localhost)
                                // Closing the process
                                exit( Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) ) );
                            }



                            // (Getting the value)
                            $response = AuthorizationService::fetch( $request->headers['Auth-Token'] );

                            if ( $response->status->code !== 200 )
                            {// (Unable to fetch the authorization)
                                // Closing the process
                                exit( Server::send( $response ) );
                            }



                            // (Getting the value)
                            $authorization = $response->body;



                            // (Getting the value)
                            $user_id = $authorization->data['request']['input']['user'];

                            if ( !UserModel::where( 'id', $user_id )->delete() )
                            {// (Unable to delete the record)
                                // Closing the process
                                exit( Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to delete the record (user)' ] ] ) ) );
                            }



                            // Closing the process
                            exit( Server::send( new Response( new Status(200) ) ) );
                        }
                        else
                        {// (Authorization has not been provided)
                            // (Verifying the user)
                            $response = UserService::verify();

                            if ( $response->status->code !== 200 )
                            {// (Session is not valid)
                                // Closing the process
                                exit( Server::send( new Response( new Status(401), [], [ 'error' => [ 'message' => 'Client not authorized' ] ] ) ) );
                            }



                            // (Getting the value)
                            $session = SessionsStore::fetch()->sessions['user'];
    
    
    
                            // (Getting the value)
                            $user_id = $session->data['user'];
    
    
    
                            // (Getting the value)
                            $user = UserModel::where( 'id', $user_id )->first();
    
                            if ( !$user )
                            {// (Record not found)
                                // Closing the process
                                exit( Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Record not found (user)' ] ] ) ) );
                            }



                            // (Starting an authorization)
                            $response = AuthorizationService::start
                            (
                                [
                                    'request'           =>
                                    [
                                        'endpoint_path' => $request->url->path,
                                        'action'        => $request->headers['Action'],
                                        'input'         =>
                                        [
                                            'user'      => $user_id
                                        ]
                                    ]
                                ]
                            )
                            ;
                            
                            if ( $response->status->code !== 200 )
                            {// (Unable to start the authorization)
                                // Closing the process
                                exit( Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to start the authorization' ] ] ) ) );
                            }



                            // (Getting the value)
                            $exp_time = $response->body['exp_time'];



                            // (Sending the authorization)
                            $response = AuthorizationService::send( $response->body['token'], $user->email, $rpc->subject . '.' . $rpc->verb );
                            
                            if ( $response->status->code !== 200 )
                            {// (Unable to send the authorization)
                                // Closing the process
                                exit( Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to send the authorization' ] ] ) ) );
                            }



                            // Closing the process
                            exit( Server::send( new Response( new Status(200) ) ) );
                        }
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
                    Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'RPC :: Action not found' ] ] ) )
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