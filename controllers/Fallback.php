<?php



namespace App\Controllers;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;

use \App\Stores\Session\User as UserSessionStore;
use \App\Models\DB\local\simba_db\Document as DocumentDBModel;



class Fallback extends Controller
{
    # Returns [void]
    public function view ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        // (Getting the values)
        $path     = $app->request->url->path;
        $user_sid = $app->request->cookies['user'];



        if ( stripos( $path, '/robots.txt' ) === 0 )
        {// Match OK
            // (Getting the value)
            $base_url = $app->request->url->fetch_base();



            // Returning the value
            return
                Server::send
                (
                    new Response
                    (
                        new Status(200),
                        [
                            'Content-Type: text/plain'
                        ],
                        <<<EOD
                        User-agent: *
                        Allow: /

                        Disallow: /admin

                        Sitemap: $base_url/sitemap.xml
                        EOD
                    )
                )
            ;
        }



        if ( stripos( $path, '/sitemap.xml' ) === 0 )
        {// Match OK
            // (Getting the value)
            $base_url = $app->request->url->fetch_base();



            // (Setting the value)
            $list = [];

            // (Getting the value)
            $documents = DocumentDBModel::fetch()->query()
                ->condition_start()
                    ->where_field( null, 'datetime.option.active' )->is_not(null)
                        ->and()
                    ->where_field( null, 'datetime.option.sitemap' )->is_not(null)
                ->condition_end()

                ->select_all()

                ->run()

                ->list()
            ;

            foreach ( $documents as $document )
            {// Processing each entry
                // (Getting the values)
                $document_path        = $document->path;
                $document_update_date = $document->datetime->update ? date( 'Y-m-d', strtotime( $document->datetime->update ) ) : date( 'Y-m-d', strtotime( $document->datetime->insert ) );



                // (Appending the value)
                $list[] =
                    <<<EOD
                    <url>
                        <loc>$base_url/docs$document_path</loc>
                        <lastmod>$document_update_date</lastmod>
                    </url>
                    EOD
                ;
            }



            // (Getting the value)
            $entries = implode( '', $list );



            // Returning the value
            return
                Server::send
                (
                    new Response
                    (
                        new Status(200),
                        [
                            'Content-Type: application/xml'
                        ],
                        <<<EOD
                        <?xml version="1.0" encoding="UTF-8"?>

                        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                            $entries
                        </urlset>
                        EOD
                    )
                )
            ;
        }



        // (Setting the value)
        $suggested_url = '/';



        if ( $user_sid )
        {// (Cookie found)
            // (Getting the value)
            $session = UserSessionStore::fetch()->session;

            // (Starting the session)
            $session->start();

            if ( $session->data['user'] )
            {// Value found
                // (Setting the value)
                $suggested_url = '/admin';
            }
        }



        // Returning the value
        return
            Server::send
            (
                new Response
                (
                    new Status(404),
                    [],
                    $app->blade->build_html
                    (
                        'root/Fallback/view.blade.php',
                        [
                            'suggested_url' => $suggested_url
                        ]
                    )
                )
            )
        ;
    }
}



?>