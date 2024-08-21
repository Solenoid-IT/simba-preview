<?php



namespace App\Services;



use \Solenoid\Core\Service;

use \Solenoid\Core\App\WebApp;



class Client extends Service
{
    # Returns [assoc] | Throws [Exception]
    public static function detect ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        // (Getting the values)
        $client_ip = $app->request->client_ip;
        $b64e_ua   = base64_encode( $app->request->headers['User-Agent'] );



        // (Getting the values)
        $ip = json_decode( file_get_contents( "https://api.solenoid.it/ip-info/1/$client_ip"  ), true );
        $ua = json_decode( file_get_contents( "https://api.solenoid.it/ua-info/1/$b64e_ua" ), true );



        // (Getting the value)
        $data =
        [
            'ip'              =>
            [
                'address'     => $ip['ip']['address'],
                'country'     =>
                [
                    'code'    => $ip['geolocation']['country']['code'],
                    'name'    => $ip['geolocation']['country']['name']
                ],
                'isp'         => $ip['connection']['isp'],
            ],

            'user_agent'      => $ua['user_agent'],

            'browser'         => $ua['browser']['summary'],
            'os'              => $ua['os']['summary'],
            'hw'              => $ua['hw']['summary']
        ]
        ;



        // Returning the value
        return $data;
    }
}



?>