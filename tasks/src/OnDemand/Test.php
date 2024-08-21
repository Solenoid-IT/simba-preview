<?php



namespace App\Tasks\OnDemand;



use \Solenoid\Core\Task\Task;

use \Solenoid\Core\App\App;



class Test extends Task
{
    public static array $tags = [ 'test' ];



    # Returns [void]
    public function run (string $name, string $surname)
    {
        // (Getting the value)
        $app = App::get();



        // (Writing to the storage)
        $app->storage->write( '/a/b/c/d/e/file.txt', date('c') );

        // (Writing to the storage)
        $app->storage->write( '/../a/b/c/d/e/file-ext.txt', 'Hello World !!!' );



        // Printing the value
        echo "\n\nWelcome $name $surname !\n\n\n";
    }
}



?>