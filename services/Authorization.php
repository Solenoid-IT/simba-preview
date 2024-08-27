<?php



namespace App\Services;



use \Solenoid\Core\Service;

use \Solenoid\Core\App\WebApp;

use \Solenoid\KeyGen\Generator;
use \Solenoid\KeyGen\Token;
use \Solenoid\MySQL\DateTime;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;
use \Solenoid\SMTP\Mail;
use \Solenoid\SMTP\MailBox;
use \Solenoid\SMTP\MailBody;
use \Solenoid\SMTP\Retry;

use \App\Stores\Connections\SMTP as SMTPConnectionsStore;
use \App\Models\DB\local\simba_db\Authorization as AuthorizationDBModel;
use \App\Services\Client as ClientService;



class Authorization extends Service
{
    # Returns [Response]
    public static function start (?array $data = null, ?string $callback_url = null, int $duration = 60)
    {
        // (Getting the value)
        $token = Generator::start
        (
            function ( $key )
            {
                // Returning the value
                return AuthorizationDBModel::fetch()->filter( [ [ 'token' => $key ] ] )->count() === 0;
            },

            function ()
            {
                // Returning the value
                return Token::generate( 128 );
            }
        )
        ;

        if ( $token === false )
        {// (Unable to generate the token)
            // Returning the value
            return
                new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to generate the token' ] ])
            ;
        }



        // (Getting the values)
        $creation_timestamp   = time();
        $expiration_timestamp = $creation_timestamp + $duration;



        // (Getting the value)
        $record =
        [
            'token'               => $token,

            'data'                => $data ? json_encode( $data ) : null,

            'callback_url'        => $callback_url,

            'datetime.insert'     => DateTime::fetch( $creation_timestamp ),
            'datetime.expiration' => DateTime::fetch( $expiration_timestamp )
        ]
        ;

        if ( AuthorizationDBModel::fetch()->insert( [ $record ] ) === false )
        {// (Unable to insert the record)
            // Returning the value
            return
                new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to insert the record (authorization)' ] ] )
            ;
        }



        // Returning the value
        return
            new Response( new Status(200), [], [ 'token' => $token, 'exp_time' => $expiration_timestamp ] )
        ;
    }

    # Returns [Response]
    public static function send (string $token, string $receiver, string $type)
    {
        // (Getting the value)
        $app = WebApp::fetch();



        // (Getting the value)
        $connection = SMTPConnectionsStore::fetch()->connections['service'];

        // (Creating a Mail)
        $mail = new Mail
        (
            new MailBox( $app->fetch_credentials()['smtp']['profiles']['service']['username'], $app->name ),

            [
                new MailBox( $receiver )
            ],

            [],
            [],
            [],

            $app->name . ' - Authorization Required',
            new MailBody
            (
                '',

                $app->blade->build
                (
                    'components/mail/authorization.blade.php',
                    [
                        'app_name'     => $app->name,
                        'type'         => $type,
                        'client'       => ClientService::detect(),
                        'endpoint_url' => $app->request->url->fetch_base() . "/admin/authorization/$token"
                    ]
                )
            )
        )
        ;

        if ( !$connection->send( $mail, new Retry() ) )
        {// (Unable to send the mail)
            // Returning the value
            return
                new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to send the mail' ] ] )
            ;
        }



        // Returning the value
        return
            new Response( new Status(200) )
        ;
    }

    # Returns [Response]
    public static function fetch (string $token)
    {
        // (Getting the value)
        $authorization = AuthorizationDBModel::fetch()->filter( [ [ 'token' => $token ] ] )->get();

        if ( $authorization === false )
        {// (Authorization not found)
            // Returning the value
            return
                new Response( new Status(401), [], [ 'error' => [ 'message' => 'Authorization is not valid' ] ] )
            ;
        }

        if ( strtotime( $authorization->datetime->expiration ) <= time() )
        {// (Authorization is expired)
            // Returning the value
            return
                new Response( new Status(401), [], [ 'error' => [ 'message' => 'Authorization is not valid' ] ] )
            ;
        }



        // Returning the value
        return
            new Response( new Status(200), [], $authorization )
        ;
    }

    # Returns [Response]
    public static function remove (string $token)
    {
        if ( AuthorizationDBModel::fetch()->filter( [ [ 'token' => $token ] ] )->delete() === false )
        {// (Unable to delete the record)
            // Returning the value
            return
                new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to delete the record (authorization)' ] ] )
            ;
        }



        // Returning the value
        return
            new Response( new Status(200) )
        ;
    }
}



?>