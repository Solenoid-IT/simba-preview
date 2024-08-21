<?php



namespace App\Stores\Session;



use \Solenoid\Core\Store;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Session;
use \Solenoid\HTTP\SessionContent;
use \Solenoid\HTTP\Cookie;

use \Solenoid\KeyGen\Generator;
use \Solenoid\KeyGen\Token;

use \Solenoid\MySQL\Condition;
use \Solenoid\MySQL\DateTime;

use \App\Models\DB\local\simba_db\Session as SessionDBModel;



class User extends Store
{
    private static self $instance;



    public Session $session;
    public string  $cookie_domain;



    # Returns [self]
    private function __construct ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        // (Getting the values)
        $this->cookie_domain = '.' . $app->id;
        $this->session       = Session::create
        (
            [
                'validate_id' => function ( $id )
                {
                    // Returning the value
                    return preg_match( '/^[\w]+$/', $id ) === 1;
                },

                'generate_id' => function ()
                {
                    // Returning the value
                    return
                        Generator::start
                        (
                            function ($id)
                            {
                                // Returning the value
                                return SessionDBModel::fetch()->filter( [ [ 'id' => $id ] ] )->count() === 0;
                            },
                            function ()
                            {
                                // Returning the value
                                return Token::generate( 128 );
                            }
                        )
                    ;
                },

                'init' => function ( $id, $duration )
                {
                    // (Getting the values)
                    $creation   = time();
                    $expiration = $creation + $duration;



                    // Returning the value
                    return SessionContent::create( $creation, $expiration, [] );
                },

                'read' => function ( $id, $duration )
                {
                    // (Getting the value)
                    $session = SessionDBModel::fetch()->filter( [ [ 'id' => $id ] ] )->find();

                    if ( $session !== false )
                    {// (Record exists)
                        // (Getting the value)
                        $content =
                        [
                            'data'       => json_decode( $session->data, true ),

                            'creation'   => strtotime( $session->datetime->creation ),
                            'expiration' => strtotime( $session->datetime->expiration )
                        ]
                        ;

                        if ( time() - $content['creation'] >= $content['expiration'] )
                        {// (Session is expired)
                            // (Setting the value)
                            $content['data'] = [];
                        }
                    }
                    else
                    {// (Record does not exist)
                        // (Getting the values)
                        $creation   = time();
                        $expiration = $creation + $duration;

                        $content = [ 'creation' => $creation, 'expiration' => $expiration, 'data' => [] ];
                    }
    


                    // Returning the value
                    return SessionContent::create( $content['creation'], $content['expiration'], $content['data'] );
                },

                'write' => function ( $id, $content )
                {
                    // (Getting the value)
                    $values =
                    [
                        'id'                  => $id,

                        'data'                => json_encode( $content->data ),

                        'user'                => $content->data['user'],

                        'datetime.creation'   => DateTime::fetch( $content->creation ),
                        'datetime.expiration' => DateTime::fetch( $content->expiration )
                    ]
                    ;

                    if ( SessionDBModel::fetch()->set( $values, [ 'id' ] ) === false )
                    {// (Unable to set the record)
                        // (Setting the value)
                        $message = "Unable to set the session";

                        // Throwing an exception
                        throw new \Exception($message);

                        // Returning the value
                        return;
                    }
                },

                'change_id' => function ( $old, $new )
                {
                    if ( SessionDBModel::fetch()->filter( [ [ 'id' => $old ] ] )->update( [ 'id' => $new ] ) === false )
                    {// (Unable to update the record)
                        // (Setting the value)
                        $message = "Unable to change the session";

                        // Throwing an exception
                        throw new \Exception($message);

                        // Returning the value
                        return;
                    }
                },

                'set_expiration' => function ( $duration )
                {
                    // Returning the value
                    return $duration === null ? null : time() + $duration;
                },

                'destroy' => function ( $id )
                {
                    if ( SessionDBModel::fetch()->filter( [ [ 'id' => $id ] ] )->delete() === false )
                    {// (Unable to delete the record)
                        // (Setting the value)
                        $message = "Unable to remove the session";

                        // Throwing an exception
                        throw new \Exception($message);

                        // Returning the value
                        return;
                    }
                },
            ],
            new Cookie
            (
                'user',
                $this->cookie_domain,
                '/',
                true,
                true
            ),
            3600,
            true
        )
        ;
    }



    # Returns [self]
    public static function fetch ()
    {
        if ( !isset( self::$instance ) )
        {// Value not found
            // (Getting the value)
            self::$instance = new self();
        }



        // Returning the value
        return self::$instance;
    }
}



?>