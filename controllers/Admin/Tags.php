<?php



namespace App\Controllers\Admin;



use \Solenoid\Core\MVC\Controller;

use \Solenoid\HTTP\Server;
use \Solenoid\HTTP\Response;
use \Solenoid\HTTP\Status;
use \Solenoid\HTML\Document as HTMLDocument;

use \App\Stores\Session\User as UserSessionStore;
use \App\Models\DB\local\simba_db\User as UserDBModel;
use \App\Models\DB\local\simba_db\Tag as TagDBModel;
use \App\Models\DB\local\simba_db\DocumentTag as DocumentTagDBModel;
use \App\Models\DB\local\simba_db\Document as DocumentDBModel;
use \App\Middlewares\RPC\Parser as RPC;
use \App\Middlewares\Editor as EditorMiddleware;






class Tags extends Controller
{
    # Returns [void]
    public function rpc ()
    {
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

                    'records'          => TagDBModel::fetch()->list( [], false, [ 'id' => 'DESC' ] ),
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
                $record =
                [
                    'value' => RPC::$input->value,
                    'color' => RPC::$input->color
                ]
                ;

                // (Registering the tag)
                $tag_model = TagDBModel::fetch()->insert( [ $record ] );

                if ( $tag_model === false )
                {// (Unable to insert the record)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to register the tag' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], [ 'id' => $tag_model->fetch_ids()[0] ] ) )
                ;
            break;

            case 'unregister':
                if ( EditorMiddleware::run() === false ) return;



                foreach ( RPC::$input->list as $id )
                {// Processing each entry
                    // (Getting the values)
                    $tag_id = $id;
                    $tag    = TagDBModel::fetch()->filter( [ [ 'id' => $tag_id ] ] )->find();



                    // (Getting the value)
                    $documents = DocumentTagDBModel::fetch()->filter( [ [ 'tag' => $tag_id ] ] )->list();

                    foreach ( $documents as $document_id )
                    {// Processing each entry
                        // (Getting the value)
                        $document = DocumentDBModel::fetch()->filter( [ [ 'id' => $document_id ] ] )->find();



                        // (Getting the value)
                        $html_document = HTMLDocument::read( $document->content );



                        // (Getting the value)
                        $node = $html_document->execute( '/html/head/meta[@name="keywords"]' );
                        $node = $node ? $node->item( 0 )->attributes->getNamedItem('content') : false;

                        if ( $node )
                        {// Value found
                            // (Getting the value)
                            $keywords = explode( ', ', $node->nodeValue );



                            // (Getting the value)
                            $key = array_search( $tag->value, $keywords );

                            if ( $key )
                            {// Value found
                                // (Removing the element)
                                unset( $keywords[ $key ] );

                                // (Getting the value)
                                $keywords = array_values( $keywords );
                            }



                            // (Getting the value)
                            $keywords = implode( ', ', $keywords );



                            // (Setting the attribute)
                            $node->nodeValue = $keywords;
                        }



                        // (Getting the value)
                        $record =
                        [
                            'content' => (string) $html_document
                        ]
                        ;

                        if ( DocumentDBModel::fetch()->filter( [ [ 'id' => $document_id ] ] )->update($record) === false )
                        {// (Unable to update the record)
                            // Returning the value
                            return
                                Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to change the document' ] ] ) )
                            ;
                        }
                    }



                    if ( TagDBModel::fetch()->filter( [ [ 'id' => $tag_id ] ] )->delete() === false )
                    {// (Unable to delete the record)
                        // Returning the value
                        return
                            Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to remove the tag' ] ] ) )
                        ;
                    }
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200) ) )
                ;
            break;



            case 'change':
                if ( EditorMiddleware::run() === false ) return;



                // (Getting the values)
                $tag_id    = RPC::$input->id;
                $tag       = TagDBModel::fetch()->filter( [ [ 'id' => $tag_id ] ] )->find();

                $old_value = $tag->value;
                $new_value = RPC::$input->value;



                // (Getting the value)
                $documents = DocumentTagDBModel::fetch()->filter( [ [ 'tag' => $tag_id ] ] )->list();

                foreach ( $documents as $document_tag )
                {// Processing each entry
                    // (Getting the value)
                    $document_id = $document_tag->document;



                    // (Getting the value)
                    $document = DocumentDBModel::fetch()->filter( [ [ 'id' => $document_id ] ] )->find();



                    // (Getting the value)
                    $html_document = HTMLDocument::read( $document->content );



                    // (Getting the value)
                    $node = $html_document->execute( '/html/head/meta[@name="keywords"]' );
                    $node = $node ? $node->item( 0 )->attributes->getNamedItem('content') : false;

                    if ( $node )
                    {// Value found
                        // (Getting the value)
                        $keywords = explode( ', ', $node->nodeValue );



                        // (Getting the value)
                        $key = array_search( $old_value, $keywords );

                        if ( $key )
                        {// Value found
                            // (Getting the value)
                            $keywords[ $key ] = $new_value;
                        }



                        // (Getting the value)
                        $keywords = implode( ', ', $keywords );



                        // (Setting the attribute)
                        $node->nodeValue = $keywords;
                    }



                    // (Getting the value)
                    $record =
                    [
                        'content' => (string) $html_document
                    ]
                    ;

                    if ( DocumentDBModel::fetch()->filter( [ [ 'id' => $document_id ] ] )->update($record) === false )
                    {// (Unable to update the record)
                        // Returning the value
                        return
                            Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to change the document' ] ] ) )
                        ;
                    }
                }



                // (Getting the value)
                $record =
                [
                    'value' => $new_value,
                    'color' => RPC::$input->color
                ]
                ;

                if ( TagDBModel::fetch()->filter( [ [ 'id' => $tag_id ] ] )->update($record) === false )
                {// (Unable to uodate the record)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(500), [], [ 'error' => [ 'message' => 'Unable to change the tag' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200) ) )
                ;
            break;



            case 'find':
                // (Setting the value)
                $valid_keys =
                [
                    'id',
                    'name'
                ]
                ;



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
                $tag = TagDBModel::fetch()->filter( [ [ $key => RPC::$input->value ] ] )->find();

                if ( $tag === false )
                {// (Tag not found)
                    // Returning the value
                    return
                        Server::send( new Response( new Status(404), [], [ 'error' => [ 'message' => 'Tag not found' ] ] ) )
                    ;
                }



                // Returning the value
                return
                    Server::send( new Response( new Status(200), [], $tag ) )
                ;
            break;
        }
    }
}



?>