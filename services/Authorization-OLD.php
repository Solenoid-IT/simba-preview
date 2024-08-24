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

use \App\Stores\Connections\SMTP as SMTPConnectionStore;
use \App\Models\DB\local\simba_db\Authorization as AuthorizationDBModel;
use \App\Services\Client as ClientService;



class AuthorizationOLD extends Service
{
    # Returns [Response] | Throws [Exception]
    public static function start
    (
        ?string $callback_url = null,

        array   $data         = [],

        ?string $receiver     = null,
        ?string $type         = null,
        string  $custom_text  = '',

        int     $duration     = 60
    )
    {
        // (Getting the value)
        $app = WebApp::fetch();



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
        $current_timestamp    = time();
        $expiration_timestamp = $current_timestamp + $duration;



        // (Getting the value)
        $record =
        [
            'token'               => $token,

            'callback_url'        => $callback_url,

            'data'                => $data ? json_encode( $data ) : null,

            'datetime.insert'     => DateTime::fetch( $current_timestamp ),
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



        if ( $receiver )
        {// Value found
            // (Getting the value)
            $connection = SMTPConnectionStore::fetch()->connections['service'];

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
                            'client'       => ClientService::detect(),
                            'endpoint_url' => $app->request->url->fetch_base() . "/admin/authorization/$token",
                            'type'         => $type,

                            'custom_text'  => $custom_text
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
        }



        // Returning the value
        return
            new Response( new Status(200), [], [ 'token' => $token, 'exp_time' => $expiration_timestamp ] )
        ;
    }

    # Returns [Response] | Throws [Exception]
    public static function fetch (string $token)
    {
        // (Getting the value)
        $authorization = AuthorizationDBModel::fetch()->filter( [ [ 'token' => $token ] ] )->get();

        if ( $authorization === false )
        {// (Authorization not found)
            // Returning the value
            return
                new Response( new Status(404), [], [ 'error' => [ 'message' => 'Authorization not found' ] ] )
            ;
        }

        if ( strtotime( $authorization->datetime->expiration ) <= time() )
        {// (Authorization is expired)
            // Returning the value
            return
                new Response( new Status(403), [], [ 'error' => [ 'message' => 'Authorization is expired' ] ] )
            ;
        }



        // Returning the value
        return
            new Response( new Status(200), [], $authorization )
        ;
    }
}



?>