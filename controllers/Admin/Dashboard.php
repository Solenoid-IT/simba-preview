<?php



namespace App\Controllers\Admin;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;

use \Solenoid\MySQL\Condition;
use \Solenoid\MySQL\Record;

use \App\Stores\Sessions\Store as SessionsStore;
use \App\Models\DB\local\simba_db\Visitor as VisitorDBModel;
use \App\Models\DB\local\simba_db\User as UserDBModel;
use \App\Middlewares\RPC\Parser as RPC;



class Dashboard extends Controller
{
    # Returns [void]
    public function rpc ()
    {
        switch ( RPC::$verb )
        {
            case 'fetch_data':
                // (Getting the value)
                $session = SessionsStore::fetch()->sessions['user'];



                // (Getting the value)
                $user_id = $session->data['user'];



                // (Getting the value)
                $user = UserDBModel::fetch()->filter( [ [ 'id' => $user_id ] ] )->get();

                if ( $user === false )
                {// (Record not found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'User not found' ] ] ) )
                    ;
                }



                // (Getting the value)
                $report =
                [
                    'monthly' => VisitorDBModel::fetch()->list_monthly_report(),
                    'yearly'  => VisitorDBModel::fetch()->list_yearly_report()
                ]
                ;

                // (Getting the value)
                $charts =
                [
                    'current_month_visitors' =>
                    [
                        'labels' => array_map( function ($record) { return $record['day']; }, $report['monthly'] ),
                        'data'   => array_map( function ($record) { return $record['qty']; }, $report['monthly'] )
                    ],

                    'current_year_visitors' =>
                    [
                        'labels' => array_map( function ($record) { return $record['month']; }, $report['yearly'] ),
                        'data'   => array_map( function ($record) { return $record['qty']; }, $report['yearly'] )
                    ]
                ]
                ;



                // (Getting the value)
                $data =
                [
                    'user'             => $user,
                    'required_actions' => $session->data['set_password'] ? [ 'set_password' ] : [],

                    'charts'           => $charts,
                    'visitors'         => VisitorDBModel::fetch()->list
                    (
                        [],
                        false,
                        [ 'datetime.insert' => 'desc' ]
                    )
                ]
                ;



                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], $data ) )
                ;
            break;
        }
    }
}



?>