<?php



namespace App\Models\DB\local\simba_db;



use \Solenoid\MySQL\Model;

use \Solenoid\MySQL\Query;

use \App\Stores\Connection\MySQL as MySQLConnectionStore;



class Access extends Model
{
    private static self $instance;



    # Returns [self]
    private function __construct ()
    {
        // (Calling the function)
        parent::__construct( MySQLConnectionStore::fetch()->connections['local/simba_db'], 'simba_db', 'access' );
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



    # Returns [Cursor|false]
    public function view ()
    {
        // Returning the value
        return ( new Query( $this->connection ) )->from( $this->database, "view::$this->table::all" )->select_all()->run();
    }
}



?>