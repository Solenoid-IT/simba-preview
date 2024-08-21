<?php



namespace App\Models\DB\local\simba_db;



use \Solenoid\MySQL\Model;

use \Solenoid\MySQL\Query;

use \App\Stores\Connection\MySQL as MySQLConnectionStore;



class Visitor extends Model
{
    private static self $instance;



    # Returns [self]
    private function __construct ()
    {
        // (Calling the function)
        parent::__construct( MySQLConnectionStore::fetch()->connections['local/simba_db'], 'simba_db', 'visitor' );
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



    # Returns [array<assoc>] | Throws [Exception]
    public function list_monthly_report (?string $year = null, ?string $month = null)
    {
        // (Getting the value)
        $time = time();



        if ( $year === null ) $year = date( 'Y', $time );
        if ( $month === null ) $month = date( 'm', $time );
    


        // (Setting the value)
        $query =
            <<<EOD
            SELECT
                YEAR(T.`datetime.insert`) AS `year`,
                MONTH(T.`datetime.insert`) AS `month`,
                DAY(T.`datetime.insert`) AS `day`,

                COUNT(*) AS `qty`
            FROM
                `$this->database`.`$this->table` T
            WHERE
                YEAR(T.`datetime.insert`) = {{ year }}
                    AND
                MONTH(T.`datetime.insert`) = {{ month }}
            GROUP BY
                YEAR(T.`datetime.insert`),
                MONTH(T.`datetime.insert`),
                DAY(T.`datetime.insert`)
            ORDER BY
                YEAR(T.`datetime.insert`) ASC,
                MONTH(T.`datetime.insert`) ASC,
                DAY(T.`datetime.insert`) ASC
            ;
            EOD
        ;

        // (Getting the value)
        $record_list = $this->connection->execute( $query, [ 'year' => $year, 'month' => $month ] )->fetch_cursor()->list();



        // (Getting the value)
        $last_day_of_month = (int) ( new \DateTime( "$year-$month" ) )->modify('last day of this month')->format('d');



        // (Setting the value)
        $list = [];

        for ( $i = 0; $i < $last_day_of_month; $i++ )
        {// Iterating each index
            // (Getting the value)
            $day = $i + 1;

            // (Getting the value)
            $result = array_values( array_filter( $record_list, function ($record) use ($day) { return $record->day === $day; } ) )[0];
            $result = $result ? (array) $result : null;



            // (Getting the value)
            $result =
                $result
                    ??
                [
                    'year'  => $record_list[0]->year,
                    'month' => $record_list[0]->month,
                    'day'   => $day,

                    'qty'   => 0
                ]
            ;
            $result =
            [
                'year'  => $result['year'],
                'month' => str_pad( $result['month'], 2, '0', STR_PAD_LEFT ),
                'day'   => str_pad( $result['day'], 2, '0', STR_PAD_LEFT ),

                'qty'   => $result['qty']
            ]
            ;



            // (Appending the value)
            $list[] = $result;
        }



        // Returning the value
        return $list;
    }

    # Returns [array<assoc>] | Throws [Exception]
    public function list_yearly_report (?string $year = null)
    {
        if ( $year === null ) $year = date('Y');



        // (Setting the value)
        $query =
            <<<EOD
            SELECT
                YEAR(T.`datetime.insert`) AS `year`,
                MONTH(T.`datetime.insert`) AS `month`,

                COUNT(*) AS `qty`
            FROM
                `$this->database`.`$this->table` T
            WHERE
                YEAR(T.`datetime.insert`) = {{ year }}
            GROUP BY
                YEAR(T.`datetime.insert`),
                MONTH(T.`datetime.insert`)
            ORDER BY
                YEAR(T.`datetime.insert`) ASC,
                MONTH(T.`datetime.insert`) ASC
            ;
            EOD
        ;

        

        // (Getting the value)
        $record_list = $this->connection->execute( $query, [ 'year' => $year ] )->fetch_cursor()->list();



        // (Setting the value)
        $list = [];

        for ( $i = 0; $i < 12; $i++ )
        {// Iterating each index
            // (Getting the value)
            $month = $i + 1;

            // (Getting the value)
            $result = array_values( array_filter( $record_list, function ($record) use ($month) { return $record->month === $month; } ) )[0];
            $result = $result ? (array) $result : null;



            // (Getting the value)
            $result =
                $result
                    ??
                [
                    'year'  => $record_list[0]->year,
                    'month' => $month,

                    'qty'   => 0
                ]
            ;
            $result =
            [
                'year'  => $result['year'],
                'month' => str_pad( $result['month'], 2, '0', STR_PAD_LEFT ),

                'qty'   => $result['qty']
            ]
            ;



            // (Appending the value)
            $list[] = $result;
        }



        // Returning the value
        return $list;
    }
}



?>