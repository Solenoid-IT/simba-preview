<?php



namespace App\Tasks\OnDemand;



use \Solenoid\Core\Task\Task;

use \Solenoid\Core\App\App;

use \Solenoid\HTTP\Client\Client;



class User extends Task
{
    public static array $tags = [];



    # Returns [void]
    public function create (string $email, string $username)
    {
        // (Getting the value)
        $app = App::get();



        // (Sending an http request)
        $response = Client::send
        (
            'https://' . $app->id . '/admin/users',
            'RPC',
            [
                'Action: register'
            ],
            json_encode
            (
                [
                    'hierarchy' => 1,

                    'username'  => $username,
                    'email'     => $email
                ]
            )
        )
        ;

        if ( $response->fetch_tail()->status->code !== 200 )
        {// (Request failed)
            // (Setting the value)
            $message = "Request failed :: " . $response->body->error->message;

            // Throwing an exception
            throw new \Exception($message);

            // (Closing the process
            exit;
        }



        // (Getting the value)
        $seconds_left = $response->body->exp_time - time();



        // Printing the value
        echo "\n\nConfirm operation by email ( $seconds_left s ) !\n\n\n";
    }
}



?>