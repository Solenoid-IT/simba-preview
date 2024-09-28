<?php



namespace App\Tasks\OnDemand;



use \Solenoid\Core\Task\Task;

use \Solenoid\Core\App\App;

use \Solenoid\HTTP\Client\Client;



class User extends Task
{
    public static array $tags = [];



    # Returns [void]
    public function create (string $group, string $user, string $email)
    {
        // (Getting the value)
        $app = App::get();



        // (Sending an http request)
        $response = Client::send
        (
            'https://' . $app->id . '/rpc',
            'RPC',
            [
                'Action: user::register',
                'Content-Type: application/json'
            ],
            json_encode
            (
                [
                    'group'         =>
                    [
                        'name'      => $group
                    ],

                    'user'          =>
                    [
                        'name'      => $user,
                        'email'     => $email,
                        'hierarchy' => 1
                    ]
                ]
            )
        )
        ;

        if ( $response->fetch_tail()->status->code !== 200 )
        {// (Request failed)
            // (Setting the value)
            $message = "Request failed :: " . $response->body['error']['message'];

            // Throwing an exception
            throw new \Exception($message);

            // (Closing the process
            exit;
        }



        // Printing the value
        echo "\n\nConfirm operation by email \"$email\" ...\n\n\n";
    }

    # Returns [void]
    public function create_test (string $email)
    {
        // (Creating the user)
        $this->create( 'simba', 'admin', $email );
    }
}



?>