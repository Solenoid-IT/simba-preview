<?php



namespace App\Stores\Connections\SMTP;



use \Solenoid\Core\App\App;

use \Solenoid\SMTP\Connection;



class Store
{
    private static self $instance;



    public array $connections;



    # Returns [self]
    private function __construct ()
    {
        // (Getting the value)
        $app = App::get();



        // (Getting the value)
        $profiles = $app->fetch_credentials()['smtp']['profiles'];



        // (Setting the value)
        $this->connections = [];

        foreach ( $profiles as $profile => $credentials )
        {// Processing each entry
            // (Getting the value)
            $this->connections[ $profile ] = new Connection( $credentials['host'], $credentials['port'], $credentials['username'], $credentials['password'], $credentials['encryption_type'] );
        }
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