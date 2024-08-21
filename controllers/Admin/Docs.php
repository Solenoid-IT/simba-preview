<?php



namespace App\Controllers\Admin;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\Core\App\WebApp;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;
use \Solenoid\HTML\Document as HTMLDocument;

use \Solenoid\MySQL\DateTime;

use \App\Stores\Session\User as UserSessionStore;
use \App\Models\DB\local\simba_db\User as UserDBModel;
use \App\Models\DB\local\simba_db\Document as DocumentDBModel;
use \App\Models\DB\local\simba_db\Tag as TagDBModel;
use \App\Models\DB\local\simba_db\DocumentTag as DocumentTagDBModel;
use \App\Services\SPA as SPAService;
use \App\Middlewares\Editor as EditorMiddleware;
use \App\Middlewares\RPC\Parser as RPC;



class Docs extends Controller
{
    # Returns [void]
    public function rpc ()
    {
        // (Getting the value)
        $app = WebApp::fetch();



        switch ( RPC::$verb )
        {
            case 'fetch_data':
                // (Getting the value)
                $session = UserSessionStore::fetch()->session;



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
                $data =
                [
                    'user'             => $user,
                    'required_actions' => $session->data['set_password'] ? [ 'set_password' ] : [],

                    'records'          => DocumentDBModel::fetch()->filter()->view(),
                    'template'         => base64_encode( $app->blade->build_html( 'root/Admin/Docs/template.blade.php', [ 'host' => SPAService::fetch_be_host() ] ) )
                ]
                ;



                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], $data ) )
                ;
            break;



            case 'register':
                if ( EditorMiddleware::run() === false ) return;



                // (Getting the value)
                $user_id = UserSessionStore::fetch()->session->data['user'];



                // (Getting the value)
                $path = '/' . preg_replace( '/^\//', '', RPC::$input->path );

                if ( DocumentDBModel::fetch()->filter( [ [ 'path' => $path ] ] )->find() !== false )
                {// (Document found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => 'Document already exists :: Duplicate entry `path`', 'subject' => 'path' ] ] ) )
                    ;
                }



                // (Getting the values)
                $content = base64_decode( RPC::$input->content );



                // (Getting the values)
                $html_document = HTMLDocument::read( $content );

                $title = $html_document->execute( '/html/head/title' );
                $title = $title ? trim( $title[0]->nodeValue ) : '';

                $description = $html_document->execute( '/html/head/meta[@name="description"]/@content' );
                $description = $description ? trim( $description[0]->value ) : '';

                $keywords = $html_document->execute( '/html/head/meta[@name="keywords"]/@content' );
                $keywords = $keywords ? $keywords[0]->value : false;
                $keywords = $keywords ? explode( ', ', $keywords ) : [];



                // (Getting the value)
                $record =
                [
                    'owner'       => $user_id,

                    'path'        => $path,

                    'title'       => $title,
                    'description' => $description,

                    'content'     => $content
                ]
                ;

                if ( DocumentDBModel::fetch()->insert( [ $record ] ) === false )
                {// (Unable to insert the record)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to register the document' ] ] ) )
                    ;
                }



                // (Getting the value)
                $document_id = DocumentDBModel::fetch()->fetch_ids()[0];



                foreach ( $keywords as $keyword )
                {// Processing each entry
                    // (Getting the value)
                    $tag = TagDBModel::fetch()->filter( [ [ 'value' => $keyword ] ] )->find();
                    
                    if ( $tag )
                    {// (Tag found)
                        // (Getting the value)
                        $tag_id = $tag->id;
                    }
                    else
                    {// (Tag not found)
                        // (Getting the value)
                        $record =
                        [
                            'value' => $keyword
                        ]
                        ;

                        if ( TagDBModel::fetch()->insert( [ $record ] ) === false )
                        {// (Unable to register the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to register the tag' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $tag_id = TagDBModel::fetch()->fetch_ids()[0];
                    }



                    // (Getting the value)
                    $record =
                    [
                        'document' => $document_id,
                        'tag'      => $tag_id
                    ]
                    ;

                    if ( DocumentTagDBModel::fetch()->insert( [ $record ] ) === false )
                    {// (Unable to insert the record)
                        // Returning the value
                        return
                            Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to register the document tag relationship' ] ] ) )
                        ;
                    }
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], [ 'id' => $document_id ] ) )
                ;
            break;

            case 'unregister':
                if ( EditorMiddleware::run() === false ) return;



                if ( DocumentDBModel::fetch()->condition_start()->where_field( null, 'id' )->in( RPC::$input->list )->condition_end()->delete() === false )
                {// (Unable to delete the records)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to remove the documents' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200) ) )
                ;
            break;



            case 'change':
                if ( EditorMiddleware::run() === false ) return;



                // (Getting the values)
                $document_id = RPC::$input->id;
                $path        = '/' . preg_replace( '/^\//', '', RPC::$input->path );



                // (Getting the value)
                $document = DocumentDBModel::fetch()->filter( [ [ 'path' => $path ] ] )->find();

                if ( $document !== false && $document->id !== $document_id )
                {// Match failed
                    // Returning the value
                    return
                        Server::send( new Response( new Status(409), [], [ 'error' => [ 'message' => 'Document already exists :: Duplicate entry `path` belongs to another document', 'subject' => 'path' ] ] ) )
                    ;
                }



                // (Getting the value)
                $content = base64_decode( RPC::$input->content );



                // (Getting the values)
                $html_document = HTMLDocument::read( $content );

                $title = $html_document->execute( '/html/head/title' );
                $title = $title ? trim( $title[0]->nodeValue ) : '';

                $description = $html_document->execute( '/html/head/meta[@name="description"]/@content' );
                $description = $description ? trim( $description[0]->value ) : '';

                $keywords = $html_document->execute( '/html/head/meta[@name="keywords"]/@content' );
                $keywords = $keywords ? $keywords[0]->value : false;
                $keywords = $keywords ? explode( ', ', $keywords ) : [];



                // (Getting the value)
                $record =
                [
                    'path'        => $path,

                    'title'       => $title,
                    'description' => $description,

                    'content'     => $content
                ]
                ;

                if ( DocumentDBModel::fetch()->filter( [ [ 'id' => $document_id ] ] )->update($record) === false )
                {// (Unable to update the record)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to change the document' ] ] ) )
                    ;
                }



                if ( DocumentTagDBModel::fetch()->filter( [ [ 'document' => $document_id ] ] )->delete() === false )
                {// (Unable to delete the record)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to unregister the document tag relationships' ] ] ) )
                    ;
                }



                foreach ( $keywords as $keyword )
                {// Processing each entry
                    // (Getting the value)
                    $tag = TagDBModel::fetch()->filter( [ [ 'value' => $keyword ] ] )->find();
                    
                    if ( $tag )
                    {// (Tag found)
                        // (Getting the value)
                        $tag_id = $tag->id;
                    }
                    else
                    {// (Tag not found)
                        // (Getting the value)
                        $record =
                        [
                            'value' => $keyword
                        ]
                        ;

                        if ( TagDBModel::fetch()->insert( [ $record ] ) === false )
                        {// (Unable to insert the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to register the tag' ] ] ) )
                            ;
                        }



                        // (Getting the value)
                        $tag_id = TagDBModel::fetch()->fetch_ids()[0];
                    }



                    // (Getting the value)
                    $record =
                    [
                        'document' => $document_id,
                        'tag'      => $tag_id
                    ]
                    ;

                    if ( DocumentTagDBModel::fetch()->insert( [ $record ] ) === false )
                    {// (Unable to insert the record)
                        // Returning the value
                        return
                            Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to register the document tag relationship' ] ] ) )
                        ;
                    }
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200) ) )
                ;
            break;



            case 'set_option':
                if ( EditorMiddleware::run() === false ) return;



                // (Setting the value)
                $valid_options = [ 'sitemap', 'active' ];



                // (Getting the value)
                $option = RPC::$input->option;

                if ( !in_array( $option, $valid_options ) )
                {// Match failed
                    // Returning the value
                    return
                        Server::send( new Response( new Status(400), [], [ 'error' => [ 'message' => 'Bad request' ] ] ) )
                    ;
                }



                // (Getting the value)
                $record =
                [
                    'datetime.option.' . $option => RPC::$input->value ? DateTime::fetch() : null
                ]
                ;

                if ( DocumentDBModel::fetch()->filter( [ [ 'id' => RPC::$input->id ] ] )->update($record) === false )
                {// (Unable to update the record)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to change the document' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200) ) )
                ;
            break;



            case 'find':
                if ( EditorMiddleware::run() === false ) return;



                // (Setting the value)
                $valid_keys = [ 'id', 'path' ];

                // (Getting the value)
                $key = RPC::$input->key;

                if ( !in_array( $key, $valid_keys ) )
                {// Match failed
                    // Returning the value
                    return
                        Server::send( new Response( new Status(400), [], [ 'error' => [ 'message' => 'Bad request' ] ] ) )
                    ;
                }



                // (Getting the value)
                $document = DocumentDBModel::fetch()->filter( [ [ $key => RPC::$input->value ] ] )->find();

                if ( $document === false )
                {// (Document not found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Document not found' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], $document ) )
                ;
            break;
        }
    }
}



?>