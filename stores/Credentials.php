<?php



namespace App\Stores;



use \Solenoid\Core\Store;

use \Solenoid\Core\App\App;



class Credentials extends Store
{
    private static self $instance;



    public array $data;



    # Returns [self]
    private function __construct ()
    {
        // (Getting the value)
        $app = App::get();



        // (Getting the value)
        $this->data =
        [
            'IDK_PASSPHRASE' => $app->fetch_credentials()['idk']['passphrase']
        ]
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