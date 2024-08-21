<?php



namespace App\Controllers;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Status;
use \Solenoid\HTTP\Response;

use \Solenoid\Core\App\WebApp;

use \App\Middlewares\RPC\Parser as RPCParser;
use \App\Models\DB\local\simba_db\User as UserDBModel;
use \App\Services\Authorization as AuthorizationService;



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
                    case 'insert':
                        // (Getting the value)
                        $app = WebApp::fetch();

                        if ( $app->request->headers['Auth-Token'] )
                        {// (Authorization has been provided)
                            // (Getting the value)
                            $response = AuthorizationService::fetch( $app->request->headers['Auth-Token'] );

                            if ( $response->status->code !== 200 )
                            {// (Unable to fetch the authorization)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(200) ) )
                                ;
                            }



                            // (Getting the value)
                            $record = (array) $response->body->request->input;

                            if ( UserDBModel::fetch()->insert( [ $record ] ) === false )
                            {// (Unable to insert the record)
                                // Returning the value
                                return
                                    Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to insert the record (user)' ] ] ) )
                                ;
                            }



                            // Returning the value
                            return
                                Server::send( new Response( new Status(200), [], UserDBModel::fetch()->fetch_ids()[0] ) )
                            ;
                        }
                        else
                        {// (Authorization has not been provided)
                            // (Starting an authorization)
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
                                            'hierarchy'                       => RPCParser::$input['hierarchy'],

                                            'username'                        => RPCParser::$input['username'],
                                            'email'                           => RPCParser::$input['email'],

                                            'profile.name'                    => RPCParser::$input['profile']['name'],
                                            'profile.surname'                 => RPCParser::$input['profile']['surname'],
                                            'profile.photo'                   => RPCParser::$input['profile']['photo'],

                                            'security.password'               => RPCParser::$input['security']['password'],
                                            'security.mfa'                    => RPCParser::$input['security']['mfa'],
                                        ]
                                    ]
                                ],
                                RPCParser::$input['email'],
                                'user.insert',
                                ''
                            )
                            ;



                            // Returning the value
                            return Server::send( $response );
                        }
                    break;
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