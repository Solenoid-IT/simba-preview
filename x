#!/usr/bin/php
<?php



// (Setting the value)
error_reporting( E_ERROR );



// (Setting the values)
$helper =
    <<<EOD


    AVAILABLE COMMANDS


    x install

    x init

    x make:lib <id>

    x make:store <id>
    x make:service <id>

    x make:middleware <id>

    x make:task <id>

    x make:controller <id>

    x mysql build <root-username>
    x mysql import-models

    x daemon register ?<name>
    x daemon start
    x daemon stop
    x daemon restart

    x scheduler start
    x scheduler stop
    x scheduler restart
    x scheduler enable
    x scheduler disable
    x scheduler task <id> <action=enable|disable>

    x dev
    x build
    x release



    EOD
;



switch ( $argv[1] )
{
    case 'install':
        // (Setting the cwd)
        chdir( __DIR__ );



        // (Executing the cmds)
        system('composer update');
        system('composer install');
        system('composer dump-autoload');
    break;

    case 'init':
        // (Setting the cwd)
        chdir( __DIR__ );

        // (Executing the cmd)
        system('php x install');



        // (Executing the command)
        #echo shell_exec("sudo php x perms set");



        // (Getting the value)
        $app_config = json_decode( file_get_contents( __DIR__ . '/app.json' ) );



        if ( !file_exists( $app_config->credentials ) )
        {// (File not found)
            // (Setting the value)
            $credentials_content =
                <<<EOD
                {
                    "mysql":
                    {
                        "rpu_username": "",
                        "profiles":
                        {
                            "local":
                            {
                                "simba_db":
                                {
                                    "host":     "localhost",
                                    "port":     null,
                                    "username": "",
                                    "password": ""
                                }
                            }
                        }
                    },

                    "smtp":
                    {
                        "profiles":
                        {
                            "service":
                            {
                                "host":            "",
                                "port":            null,
                                "username":        "",
                                "password":        "",
                                "encryption_type": ""
                            }
                        }
                    },

                    "idk":
                    {
                        "passphrase": ""
                    },

                    "rpc":
                    {
                        "token": ""
                    }
                }
                EOD
            ;

            if ( file_put_contents( $app_config->credentials, $credentials_content ) === false )
            {// (Unable to write to the file)
                // (Setting the value)
                $message = "Unable to write to the file '$app_config->credentials'";

                // Throwing an exception
                throw new \Exception($message);

                // Closing the process
                exit;
            }
        }



        // (Getting the value)
        $storage_folder_path = $app_config->storage->folder_path;

        if ( !is_dir( $storage_folder_path ) )
        {// (Directory not found)
            // (Executing the cmds)
            system("mkdir -p \"$storage_folder_path\"");

            #system("chown -R $cli_user:$http_user \"$storage_folder_path\"");
            #system("chmod -R 775 \"$dir\"");
        }



        foreach ( $app_config->blade as $dir )
        {// Processing each entry
            if ( !is_dir( $dir ) )
            {// (Directory not found)
                // (Executing the cmds)
                system("mkdir -p \"$dir\"");

                #system("chown -R $cli_user:$http_user \"$dir\"");
                #system("chmod -R 775 \"$dir\"");
            }
        }



        foreach ( $app_config->logs as $context => $v )
        {// Processing each entry
            foreach ( $v as $type => $file_path )
            {// Processing each entry
                // (Getting the values)
                $dir  = dirname($file_path);
                $base = basename($file_path);



                if ( !is_dir( $dir ) )
                {// (Directory not found)
                    // (Executing the cmds)
                    system("mkdir -p \"$dir\" && touch \"$dir/$base\"");

                    #system("chown -R $cli_user:$http_user \"$file_path\"");
                    #system("chmod -R 664 \"$file_path\"");
                }
            }
        }



        // (Getting the value)
        $core_folder_path = realpath( __DIR__ . '/..' );



        // (Getting the values)
        $core_user  = posix_getpwuid( fileowner( $core_folder_path ) )['name'];
        $core_group = posix_getpwuid( filegroup( $core_folder_path ) )['name'];



        # Returns [string]
        function explicit_path (string $base, string $path)
        {
            // Returning the value
            return preg_replace( '/^\./', $base, $path );
        }



        // (Getting the value)
        $file_paths =
        [
            explicit_path( __DIR__, $app_config->history ),
            explicit_path( __DIR__, $app_config->credentials ),
        ]
        ;

        foreach ( $app_config->logs as $context => $v )
        {// Processing each entry
            foreach ( $v as $type => $file_path )
            {// Processing each entry
                // (Appending the value)
                $file_paths[] = explicit_path( __DIR__, $file_path );
            }
        }

        foreach ( $file_paths as $file_path )
        {// Processing each entry
            // (Executing the cmd)
            system("sudo chmod 664 \"$file_path\"");
        }



        // (Getting the value)
        $folder_paths =
        [
            explicit_path( __DIR__, $app_config->storage->folder_path ),
            explicit_path( __DIR__, $app_config->blade->views ),
            explicit_path( __DIR__, $app_config->blade->cache )
        ]
        ;

        foreach ( $folder_paths as $folder_path )
        {// Processing each entry
            // (Executing the cmd)
            system("sudo chmod 2775 \"$folder_path\"");
        }



        // (Executing the cmd)
        system("sudo chown -R $core_user:$core_group \"$core_folder_path\"");



        /*

        // (Executing the cmd)
        system("sudo rm -rf ./svelte/node_modules");

        // (Setting the cwd)
        chdir( "$core_folder_path/svelte" );

        // (Executing the cmd)
        system("npm install");

        */



        // Printing the value
        echo "\n\nApp has been initialized. Visit 'https://kb.solenoid.it/simba@1.0.0' for configuring the app\n\n\n";
    break;



    case 'make:lib':
        // (Getting the value)
        $id = $argv[2];



        // (Getting the value)
        $folder_path = __DIR__ . "/lib/src/$id";

        if ( is_dir( $folder_path ) )
        {// (Directory found)
            // Printing the value
            echo "\n\nCannot create the library :: Library '$id' already exists !\n\n\n";
        }
        else
        {// (Directory not found)
            // (Making the directories)
            mkdir( $folder_path, 0777, true );
            mkdir( "$folder_path/src" );



            // (Getting the values)
            $sample_file_path = "$folder_path/src/Sample.php";
            $sample_ns        = str_replace( '/', '\\', $id );
            $sample_content   =
                <<<EOD
                <?php



                namespace App\Libraries\\$sample_ns;
                
                
                
                class Sample
                {
                    # Returns [string]
                    public static function build (string \$key, array \$value = [])
                    {
                        // Returning the value
                        return \$key . ' -> ' . json_encode( \$value );
                    }
                }
                
                
                
                ?>
                EOD
            ;



            if ( file_put_contents( $sample_file_path, $sample_content ) === false )
            {// (Unable to write to the file)
                // (Setting the value)
                $message = "Unable to write to the file '$sample_file_path'";

                // Throwing an exception
                throw new \Exception($message);

                // Closing the process
                exit;
            }



            // Printing the value
            echo "\n\nFile `$sample_file_path` has been created !\n\n\n";
        }
    break;

    case 'make:store':
        // (Getting the value)
        $id = $argv[2];



        // (Getting the value)
        $file_path = __DIR__ . "/stores/$id.php";

        if ( file_exists( $file_path ) )
        {// (File found)
            // Printing the value
            echo "\n\nCannot create the store :: File '$file_path' already exists !\n\n\n";

            // Closing the process
            exit;
        }



        // (Getting the values)
        $parts        = explode( '/', $id );
        $class_name   = $parts[ count( $parts ) - 1 ];
        $class_path   = count( $parts ) === 1 ? '' : '\\' . implode( '\\', array_slice( $parts, 0, count( $parts ) - 1 ) );
        $file_content =
            <<<EOD
            <?php



            namespace App\Stores$class_path;



            use \Solenoid\Core\Store;



            class $class_name extends Store
            {
                private static self \$instance;



                # Returns [self]
                private function __construct ()
                {
                    
                }



                # Returns [self]
                public static function fetch ()
                {
                    if ( !isset( self::\$instance ) )
                    {// Value not found
                        // (Getting the value)
                        self::\$instance = new self();
                    }



                    // Returning the value
                    return self::\$instance;
                }



                # Returns [int]
                public function fetch_time ()
                {
                    // Returning the value
                    return time();
                }
            }



            ?>
            EOD
        ;



        // (Getting the value)
        $dir = dirname( $file_path );

        if ( !is_dir( $dir ) )
        {// (Directory not found)
            if ( !mkdir( $dir, 0777, true ) )
            {// (Unable to make the directory)
                // (Setting the value)
                $message = "Unable to make the directory '$dir'";

                // Throwing an exception
                throw new \Exception($message);

                // Closing the process
                exit;
            }
        }



        if ( file_put_contents( $file_path, $file_content ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$file_path'";

            // Throwing an exception
            throw new \Exception($message);

            // Closing the process
            exit;
        }



        // Printing the value
        echo "\n\nFile `$file_path` has been created !\n\n\n";
    break;

    case 'make:service':
        // (Getting the value)
        $id = $argv[2];



        // (Getting the value)
        $file_path = __DIR__ . "/services/$id.php";

        if ( file_exists( $file_path ) )
        {// (File found)
            // Printing the value
            echo "\n\nCannot create the service :: File '$file_path' already exists !\n\n\n";

            // Closing the process
            exit;
        }



        // (Getting the values)
        $parts        = explode( '/', $id );
        $class_name   = $parts[ count( $parts ) - 1 ];
        $class_path   = count( $parts ) === 1 ? '' : '\\' . implode( '\\', array_slice( $parts, 0, count( $parts ) - 1 ) );
        $file_content =
            <<<EOD
            <?php



            namespace App\Services$class_path;



            use \Solenoid\Core\Service;

            use \Solenoid\Core\App\WebApp;



            class $class_name extends Service
            {
                # Returns [assoc] | Throws [Exception]
                public static function fetch_url ()
                {
                    // (Getting the value)
                    \$app = WebApp::fetch();



                    // Returning the value
                    return \$app->request->url;
                }
            }



            ?>
            EOD
        ;



        // (Getting the value)
        $dir = dirname( $file_path );

        if ( !is_dir( $dir ) )
        {// (Directory not found)
            if ( !mkdir( $dir, 0777, true ) )
            {// (Unable to make the directory)
                // (Setting the value)
                $message = "Unable to make the directory '$dir'";

                // Throwing an exception
                throw new \Exception($message);

                // Closing the process
                exit;
            }
        }



        if ( file_put_contents( $file_path, $file_content ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$file_path'";

            // Throwing an exception
            throw new \Exception($message);

            // Closing the process
            exit;
        }



        // Printing the value
        echo "\n\nFile `$file_path` has been created !\n\n\n";
    break;

    case 'make:middleware':
        // (Getting the value)
        $id = $argv[2];



        // (Getting the value)
        $file_path = __DIR__ . "/middlewares/src/$id.php";

        if ( file_exists( $file_path ) )
        {// (File found)
            // Printing the value
            echo "\n\nCannot create the middleware :: File '$file_path' already exists !\n\n\n";

            // Closing the process
            exit;
        }



        // (Getting the values)
        $parts        = explode( '/', $id );
        $class_name   = $parts[ count( $parts ) - 1 ];
        $class_path   = count( $parts ) === 1 ? '' : '\\' . implode( '\\', array_slice( $parts, 0, count( $parts ) - 1 ) );
        $file_content =
            <<<EOD
            <?php



            namespace App\Middlewares$class_path;



            use \Solenoid\Core\Middleware;

            use \Solenoid\Core\App\WebApp;



            class $class_name extends Middleware
            {
                
                # Returns [bool] | Throws [Exception]
                public static function run ()
                {
                    // (Getting the value)
                    \$app = WebApp::fetch();



                    // Returning the value
                    return \$app->request->headers['Auth-Token'] === 'test';
                }
            }



            ?>
            EOD
        ;



        // (Getting the value)
        $dir = dirname( $file_path );

        if ( !is_dir( $dir ) )
        {// (Directory not found)
            if ( !mkdir( $dir, 0777, true ) )
            {// (Unable to make the directory)
                // (Setting the value)
                $message = "Unable to make the directory '$dir'";

                // Throwing an exception
                throw new \Exception($message);

                // Closing the process
                exit;
            }
        }



        if ( file_put_contents( $file_path, $file_content ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$file_path'";

            // Throwing an exception
            throw new \Exception($message);

            // Closing the process
            exit;
        }



        // Printing the value
        echo "\n\nFile `$file_path` has been created !\n\n\n";
    break;

    case 'make:task':
        // (Getting the value)
        $id = $argv[2];



        // (Getting the value)
        $file_path = __DIR__ . "/tasks/src/$id.php";

        if ( file_exists( $file_path ) )
        {// (File found)
            // Printing the value
            echo "\n\nCannot create the task :: File '$file_path' already exists !\n\n\n";

            // Closing the process
            exit;
        }



        // (Getting the values)
        $parts        = explode( '/', $id );
        $class_name   = $parts[ count( $parts ) - 1 ];
        $class_path   = count( $parts ) === 1 ? '' : '\\' . implode( '\\', array_slice( $parts, 0, count( $parts ) - 1 ) );
        $file_content =
            <<<EOD
            <?php



            namespace App\Tasks$class_path;



            use \Solenoid\Core\Task\Task;

            use \Solenoid\Core\App\App;



            class $class_name extends Task
            {
                public static array \$tags = [ 'test' ];



                # Returns [void]
                public function run ()
                {
                    // (Getting the value)
                    \$app = App::get();



                    // (Writing to the storage)
                    \$app->storage->write( '/a/b/c/d/e/file.txt', date('c') );

                    // (Writing to the storage)
                    \$app->storage->write( '/../a/b/c/d/e/file-ext.txt', 'Hello World !!!' );
                }
            }



            ?>
            EOD
        ;



        // (Getting the value)
        $dir = dirname( $file_path );

        if ( !is_dir( $dir ) )
        {// (Directory not found)
            if ( !mkdir( $dir, 0777, true ) )
            {// (Unable to make the directory)
                // (Setting the value)
                $message = "Unable to make the directory '$dir'";

                // Throwing an exception
                throw new \Exception($message);

                // Closing the process
                exit;
            }
        }



        if ( file_put_contents( $file_path, $file_content ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$file_path'";

            // Throwing an exception
            throw new \Exception($message);

            // Closing the process
            exit;
        }



        // Printing the value
        echo "\n\nFile `$file_path` has been created !\n\n\n";
    break;

    case 'make:controller':
        // (Getting the value)
        $id = $argv[2];



        // (Getting the value)
        $file_path = __DIR__ . "/controllers/$id.php";

        if ( file_exists( $file_path ) )
        {// (File found)
            // Printing the value
            echo "\n\nCannot create the controller :: File '$file_path' already exists !\n\n\n";

            // Closing the process
            exit;
        }



        // (Getting the values)
        $parts        = explode( '/', $id );
        $class_name   = $parts[ count( $parts ) - 1 ];
        $class_path   = count( $parts ) === 1 ? '' : '\\' . implode( '\\', array_slice( $parts, 0, count( $parts ) - 1 ) );
        $file_content =
            <<<EOD
            <?php



            namespace App\Controllers$class_path;



            use \Solenoid\Core\MVC\Controller;

            use \Solenoid\Core\App\WebApp;

            use \Solenoid\HTTP\Server;
            use \Solenoid\HTTP\Status;
            use \Solenoid\HTTP\Response;

            use \App\Middlewares\RPC\Parser as RPC;



            class $class_name extends Controller
            {
                # Returns [void]
                public function get ()
                {
                    // Returning the value
                    return 'test';
                }

                # Returns [void]
                public function rpc ()
                {
                    // (Getting the value)
                    \$app = WebApp::fetch();



                    switch ( RPC::\$subject )
                    {
                        case 'user':
                            switch ( RPC::\$verb )
                            {
                                case 'change':
                                    // Returning the value
                                    return
                                        Server::send( new Response( new Status(200), [], \$app->request->url ) )
                                    ;
                                break;
                            }
                        break;
                    }
                }
            }



            ?>
            EOD
        ;



        // (Getting the value)
        $dir = dirname( $file_path );

        if ( !is_dir( $dir ) )
        {// (Directory not found)
            if ( !mkdir( $dir, 0777, true ) )
            {// (Unable to make the directory)
                // (Setting the value)
                $message = "Unable to make the directory '$dir'";

                // Throwing an exception
                throw new \Exception($message);

                // Closing the process
                exit;
            }
        }



        if ( file_put_contents( $file_path, $file_content ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$file_path'";

            // Throwing an exception
            throw new \Exception($message);

            // Closing the process
            exit;
        }



        // Printing the value
        echo "\n\nFile `$file_path` has been created !\n\n\n";
    break;



    case 'mysql':
        switch ( $argv[2] )
        {
            case 'build':
                # Returns [string]
                function replace_values (string $content, array $values)
                {
                    foreach ( $values as $k => $v )
                    {// Processing each entry
                        // (Getting the value)
                        $content = str_replace( $k, $v, $content );
                    }



                    // Returning the value
                    return $content;
                }



                // (Getting the value)
                $app_config = json_decode( file_get_contents( __DIR__ . '/app.json' ) );



                // (Getting the value)
                $credentials = json_decode( file_get_contents( $app_config->credentials ) )->mysql;



                // (Setting the value)
                $dbs = [];

                foreach ( glob( __DIR__ . '/databases/*/*' ) as $folder_path )
                {// Processing each entry
                    // (Getting the values)
                    $profile = basename( dirname( $folder_path ) );
                    $db_name = basename( $folder_path );



                    // (Getting the value)
                    $constants =
                    [
                        '%( DB_NAME )%' => $db_name,
                        '%( DB_USER )%' => $credentials->profiles->{ $profile }->{ $db_name }->username,
                        '%( DB_PASS )%' => $credentials->profiles->{ $profile }->{ $db_name }->password
                    ]
                    ;



                    // (Setting the value)
                    $sql_context_pad_length = 86;



                    // (Getting the value)
                    $merge_sql_file = "$folder_path/merge.sql";

                    foreach ( [ 'schema.sql', 'contents.sql', 'user.sql' ] as $file_name )
                    {// Processing each entry
                        // (Getting the value)
                        $sql_context = str_pad( '-- ' . strtoupper( pathinfo( $file_name, PATHINFO_FILENAME ) ) . ' ', $sql_context_pad_length, '-', STR_PAD_RIGHT );



                        // (Writing to the file)
                        file_put_contents( $merge_sql_file, "$sql_context\n\n\n" . replace_values( file_get_contents( "$folder_path/$file_name" ), $constants ) . "\n\n\n", FILE_APPEND );
                    }



                    // (Getting the value)
                    $sql_context = str_pad( '-- EVENTS ', $sql_context_pad_length, '-', STR_PAD_RIGHT );

                    // (Writing to the file)
                    file_put_contents( $merge_sql_file, "$sql_context\n\n\n", FILE_APPEND );

                    foreach ( glob( "$folder_path/events/*" ) as $file_path )
                    {// Processing each entry
                        // (Writing to the file)
                        file_put_contents( $merge_sql_file, replace_values( file_get_contents( "$file_path" ), $constants ) . "\n\n\n", FILE_APPEND );
                    }



                    // (Getting the value)
                    $sql_context = str_pad( '-- VIEWS ', $sql_context_pad_length, '-', STR_PAD_RIGHT );

                    // (Writing to the file)
                    file_put_contents( $merge_sql_file, "$sql_context\n\n\n", FILE_APPEND );

                    foreach ( glob( "$folder_path/views/*" ) as $file_path )
                    {// Processing each entry
                        // (Writing to the file)
                        file_put_contents( $merge_sql_file, replace_values( file_get_contents( "$file_path" ), $constants ) . "\n\n\n", FILE_APPEND );
                    }



                    // (Executing the cmd)
                    #echo shell_exec("sudo mysql -u $username -p$password $db_name < $merge_sql_file");
                    system("sudo mysql -u $credentials->rpu_username -p < $merge_sql_file");

                    // (Removing the file)
                    unlink( $merge_sql_file );



                    // (Appending the value)
                    $dbs[] = "$profile/$db_name";
                }



                if ( $dbs )
                {// Value is not empty
                    // (Getting the value)
                    $dbs_csv = '"' . implode( '";"', $dbs ) . '"';

                    // Printing the value
                    echo "\n\nDatabases $dbs_csv have been imported\n\n\n";
                }
            break;

            case 'import-models':
                // (Including the file)
                include_once( __DIR__ . '/autoload.php' );



                # Returns [string]
                function snake_to_camel (string $value)
                {
                    // Returning the value
                    return lcfirst( str_replace( '_', '', ucwords( str_replace( '.', '_', $value ), '_' ) ) );
                }

                # Returns [string]
                function replace_values (string $content, array $values)
                {
                    foreach ( $values as $k => $v )
                    {// Processing each entry
                        // (Getting the value)
                        $content = str_replace( $k, $v, $content );
                    }



                    // Returning the value
                    return $content;
                }



                // (Setting the value)
                $dbs = [];

                foreach ( glob( __DIR__ . '/databases/*/*/schema.sql' ) as $file_path )
                {// Processing each entry
                    // (Getting the values)
                    $profile = basename( dirname( $file_path, 2 ) );
                    $db_name = basename( dirname( $file_path ) );



                    // (Getting the value)
                    $constants =
                    [
                        '%( DB_NAME )%' => $db_name
                    ]
                    ;



                    // (Getting the value)
                    $model_db_path = __DIR__ . "/models/DB/$profile/$db_name";

                    if ( !is_dir( $model_db_path ) )
                    {// (Directory not found)
                        if ( !mkdir( $model_db_path, 0777, true ) )
                        {// (Unable to make the directory)
                            // (Setting the value)
                            $message = "Unable to make the directory '$model_db_path'";

                            // Throwing an exception
                            throw new \Exception($message);

                            // Closing the process
                            exit;
                        }
                    }



                    // (Getting the value)
                    $database = \Solenoid\MySQL\DDL\Database::parse( replace_values( file_get_contents( $file_path ), $constants ) );

                    foreach ( $database->entities as $entity )
                    {// Processing each entry
                        // (Getting the values)
                        $model_name      = ucfirst( snake_to_camel( $entity->name ) );
                        $model_file_path = "$model_db_path/$model_name.php";

                        if ( !file_exists( $model_file_path ) )
                        {// (File not found)
                            // (Setting the value)
                            $ai_field = false;

                            foreach ( $entity->fields as $name => $descriptor )
                            {// Processing each entry
                                if ( stripos( $descriptor, 'AUTO_INCREMENT' ) !== false )
                                {// Value found
                                    // (Getting the value)
                                    $ai_field = $name;

                                    // Breaking the iteration
                                    break;
                                }
                            }



                            // (Setting the value)
                            $model_file_content =
                                <<<EOD
                                <?php



                                namespace App\Models\DB\\$profile\\$db_name;



                                use \Solenoid\MySQL\Model;

                                use \Solenoid\MySQL\Query;

                                use \App\Stores\Connection\MySQL as MySQLConnectionStore;



                                class $model_name extends Model
                                {
                                    private static self \$instance;



                                    # Returns [self]
                                    private function __construct ()
                                    {
                                        // (Calling the function)
                                        parent::__construct( MySQLConnectionStore::fetch()->connections['$profile/$db_name'], '$database->name', '$entity->name' );
                                    }



                                    # Returns [self]
                                    public static function fetch ()
                                    {
                                        if ( !isset( self::\$instance ) )
                                        {// Value not found
                                            // (Getting the value)
                                            self::\$instance = new self();
                                        }



                                        // Returning the value
                                        return self::\$instance;
                                    }



                                    # Returns [Cursor|false]
                                    public function view ()
                                    {
                                        // Returning the value
                                        return ( new Query( \$this->connection ) )->from( \$this->database, "view::\$this->table::all" )->select_all()->run();
                                    }
                                }



                                ?>
                                EOD
                            ;

                            if ( file_put_contents( $model_file_path, $model_file_content ) === false )
                            {// (Unable to write to the file)
                                // (Setting the value)
                                $message = "Unable to write to the file '$model_file_path'";

                                // Throwing an exception
                                throw new \Exception($message);

                                // Closing the process
                                exit;
                            }
                        }
                    }



                    // (Appending the value)
                    $dbs[] = "$profile/$db_name";
                }



                if ( $dbs )
                {// Value is not empty
                    // (Getting the value)
                    $models_csv = '"' . implode( '";"', $dbs ) . '"';



                    // Printing the value
                    echo "\n\nModels $models_csv have been imported\n\n\n";
                }
            break;
        }
    break;



    case 'daemon':
        switch ( $argv[2] )
        {
            case 'start':
                // (Setting the cwd)
                chdir( __DIR__ );



                // (Executing the cmd)
                system('php ./system/daemon.php start');
            break;

            case 'stop':
                // (Setting the cwd)
                chdir( __DIR__ );



                // (Executing the cmd)
                system('php ./system/daemon.php stop');
            break;

            case 'restart':
                // (Setting the cwd)
                chdir( __DIR__ );



                // (Executing the cmd)
                system('php ./system/daemon.php restart');
            break;



            case 'register':
                if ( isset( $argv[3] ) )
                {// Value found
                    // (Getting the value)
                    $name = $argv[3];
                }
                else
                {// Value not found
                    // (Getting the value)
                    $app_config = json_decode( file_get_contents( __DIR__ . '/app.json' ) );

                    // (Getting the value)
                    $name = "$app_config->id.simba";
                }



                // (Getting the values)
                $description     = '';
                $executable_path = "php " . __DIR__ . '/system/daemon.php';



                // (Executing the cmd)
                system("simba service create \"$name\" \"$description\" \"$executable_path\"");
            break;
        }
    break;

    case 'scheduler':
        switch ( $argv[2] )
        {
            case 'start':
                // (Setting the cwd)
                chdir( __DIR__ );



                // (Executing the cmd)
                system('php ./system/scheduler.php start');
            break;

            case 'stop':
                // (Setting the cwd)
                chdir( __DIR__ );



                // (Executing the cmd)
                system('php ./system/scheduler.php stop');
            break;

            case 'restart':
                // (Setting the cwd)
                chdir( __DIR__ );



                // (Executing the cmd)
                system('php ./system/scheduler.php restart');
            break;



            case 'enable':
                // (Getting the value)
                $file_path = __DIR__ . '/tasks/scheduler.json';



                // (Getting the value)
                $scheduler = json_decode( file_get_contents( $file_path ), true );

                // (Setting the value)
                $scheduler['enabled'] = true;

                // (Writing to the file)
                file_put_contents( $file_path, json_encode( $scheduler, JSON_PRETTY_PRINT ) );
            break;

            case 'disable':
                // (Getting the value)
                $file_path = __DIR__ . '/tasks/scheduler.json';



                // (Getting the value)
                $scheduler = json_decode( file_get_contents( $file_path ), true );

                // (Setting the value)
                $scheduler['enabled'] = false;

                // (Writing to the file)
                file_put_contents( $file_path, json_encode( $scheduler, JSON_PRETTY_PRINT ) );
            break;



            case 'task':
                // (Including the file)
                include_once( __DIR__ . '/autoload.php' );



                // (Getting the value)
                $task_id = $argv[3];

                switch ( $argv[4] )
                {
                    case 'enable':
                    case 'disable':
                        // (Getting the value)
                        $task_action = $argv[4];
                    break;

                    default:
                        // Printing the value
                        echo $helper;

                        // Closing the process
                        exit;
                }



                // (Getting the value)
                $file_path = __DIR__ . '/tasks/scheduler.json';



                // (Getting the value)
                $scheduler = json_decode( file_get_contents( $file_path ), true );

                foreach ( $scheduler['tasks'] as &$task )
                {// Processing each entry
                    if ( $task['id'] === $task_id )
                    {// Match OK
                        // (Setting the value)
                        $task['enabled'] = $task_action === 'enable';
                    }
                }

                // (Writing to the file)
                file_put_contents( $file_path, json_encode( $scheduler, JSON_PRETTY_PRINT ) );
            break;
        }
    break;



    case 'dev':
        // (Including the file)
        include_once( __DIR__ . '/autoload.php' );



        // (Getting the value)
        $app_config = json_decode( file_get_contents( __DIR__ . '/app.json' ) );



        // (Getting the values)
        $app_id          = $app_config->id;
        $app_name        = $app_config->name;
        $app_version     = array_keys( json_decode( file_get_contents( $app_config->history ), true ) )[0];
        $app_build_time  = array_values( json_decode( file_get_contents( $app_config->history ), true ) )[0]['buildTime'];
        $dev_sid         = bin2hex( random_bytes( 64 / 2 ) );
        $be_host         = $app_id;
        #$dev_server_fqdn = $app_id;
        $dev_server_fqdn = "front-dev.{$app_id}";



        // (Getting the values)
        $hosts_file_path    = '/etc/hosts';
        $hosts_file_content = file_get_contents( $hosts_file_path );
        $localhost_entry    = "127.0.0.1 $dev_server_fqdn";

        if ( strpos( $hosts_file_content, $localhost_entry ) === false )
        {// Value not found
            if ( file_put_contents( $hosts_file_path, "\n\n# Simba [$app_id]\n$localhost_entry", FILE_APPEND ) === false )
            {// (Unable to write to the file)
                // (Setting the value)
                $message = "Unable to write to the file '$hosts_file_path'";

                // Throwing an exception
                throw new \Exception($message);

                // Closing the process
                exit;
            }
        }



        if ( readline( "Have you added the localhost-entry '$localhost_entry' to your hosts file (ex. /etc/hosts) to access the web-app from your browser ? [Y/n]\n" ) === 'n' )
        {// (Confirmation is failed)
            // Printing the value
            echo "\n\nDev-Server has not been started\n\n\n";

            // Closing the process
            exit;
        }



        // (Setting the cwd)
        chdir( __DIR__ . '/svelte' );



        // (Getting the value)
        $envs =
        [
            'P_APP_ID'         => $app_id,
            'P_APP_NAME'       => $app_name,
            'P_APP_VERSION'    => $app_version,
            'P_APP_BUILD_TIME' => $app_build_time,
            'P_DEV_SID'        => $dev_sid,
            'P_BE_HOST'        => $be_host,
            'P_BE_TYPE'        => 'dev'
        ]
        ;
        


        // (Setting the value)
        $envs_s = [];

        foreach ( $envs as $k => $v )
        {// Processing each entry
            // (Getting the value)
            $envs_s[] = "$k=\"$v\"";
        }



        // (Getting the value)
        $envs_s = implode( "\n", $envs_s );



        // (Getting the values)
        $env_file_path    = __DIR__ . '/svelte/.env';
        $env_file_content =
            <<<EOD
            # Core
            $envs_s
            EOD
        ;

        if ( file_put_contents( $env_file_path, $env_file_content ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$env_file_path'";

            // Throwing an exception
            throw new \Exception($message);

            // Closing the process
            exit;
        }



        // (Creating a Blade)
        $blade = new \eftec\bladeone\BladeOne( __DIR__ . '/svelte/src', __DIR__ . '/svelte/src/_cache' );



        // (Setting the value)
        $envs_n = [];

        foreach ( $envs as $k => $v )
        {// Processing each entry
            // (Getting the value)
            $envs_n[ preg_replace( '/^P\_/', '', $k ) ] = $v;
        }



        // (Getting the value)
        $html_file_content = $blade->run( '/app.blade.php', [ 'envs' => $envs_n ] );



        // (Getting the value)
        $app_file_path = __DIR__ . '/svelte/src/app.html';

        if ( file_put_contents( $app_file_path, $html_file_content ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$app_file_path'";

            // Throwing an exception
            throw new \Exception($message);

            // Closing the process
            exit;
        }



        // (Getting the values)
        $cert_file_path = __DIR__ . '/svelte/cert/cert.pem';
        $key_file_path  = __DIR__ . '/svelte/cert/key.pem';

        // (Executing the command)
        echo shell_exec( "openssl req -x509 -newkey rsa:4096 -keyout $key_file_path -out $cert_file_path -sha256 -days 3650 -nodes -subj \"/C=IT/ST=Italy/L=Turin/O=Solenoid-IT/OU=Solenoid-IT/CN=Solenoid-IT\"" );



        // (Getting the value)
        $core_file_path    = __DIR__ . '/svelte/.core';
        $core_file_content =
        [
            'dev_server'   =>
            [
                'host'     => $dev_server_fqdn,
                'https'    =>
                [
                    'key'  => $key_file_path,
                    'cert' => $cert_file_path
                ]
            ],

            'build_path'   => __DIR__ . '/web/build'
        ]
        ;

        if ( file_put_contents( $core_file_path, json_encode( $core_file_content, JSON_PRETTY_PRINT ) ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$core_file_path'";

            // Throwing an exception
            throw new \Exception($message);

            // Closing the process
            exit;
        }



        // (Executing the cmd)
        #system("npm run dev -- --open --port 3000");
        #system("npm run dev -- --open");
        system("npm run dev");
    break;

    case 'build':
        // (Including the files)
        include_once( __DIR__ . '/autoload.php' );
        include_once( __DIR__ . '/envs.php' );



        // (Getting the value)
        $app_config = json_decode( file_get_contents( __DIR__ . '/app.json' ) );



        // (Getting the values)
        $app_id         = $app_config->id;
        $app_name       = $app_config->name;
        $app_version    = array_keys( json_decode( file_get_contents( $app_config->history ), true ) )[0];
        $app_build_time = array_values( json_decode( file_get_contents( $app_config->history ), true ) )[0]['buildTime'];
        $be_host        = $app_id;
        $env            = array_values( array_filter( $envs['http'], function ($env) use ($app_config) { return in_array( $app_config->id, $env->hosts ); } ) )[0];
        $be_type        = $env->type;



        // (Setting the cwd)
        chdir( __DIR__ . '/svelte' );



        // (Getting the value)
        $envs =
        [
            'P_APP_ID'         => $app_id,
            'P_APP_NAME'       => $app_name,
            'P_APP_VERSION'    => $app_version,
            'P_APP_BUILD_TIME' => $app_build_time,
            'P_DEV_SID'        => null,
            'P_BE_HOST'        => $be_host,
            'P_BE_TYPE'        => $be_type
        ]
        ;
        


        // (Setting the value)
        $envs_s = [];

        foreach ( $envs as $k => $v )
        {// Processing each entry
            // (Getting the value)
            $envs_s[] = "$k=\"$v\"";
        }



        // (Getting the value)
        $envs_s = implode( "\n", $envs_s );



        // (Getting the values)
        $env_file_path    = __DIR__ . '/svelte/.env';
        $env_file_content =
            <<<EOD
            # Core
            $envs_s
            EOD
        ;

        if ( file_put_contents( $env_file_path, $env_file_content ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$env_file_path'";

            // Throwing an exception
            throw new \Exception($message);
        }



        // (Creating a Blade)
        $blade = new \eftec\bladeone\BladeOne( __DIR__ . '/svelte/src', __DIR__ . '/svelte/src/_cache' );



        // (Setting the value)
        $envs_n = [];

        foreach ( $envs as $k => $v )
        {// Processing each entry
            // (Getting the value)
            $envs_n[ preg_replace( '/^P\_/', '', $k ) ] = $v;
        }



        // (Getting the value)
        $html_file_content = $blade->run( '/app.blade.php', [ 'envs' => $envs_n ] );



        // (Getting the value)
        $app_file_path = __DIR__ . '/svelte/src/app.html';

        if ( file_put_contents( $app_file_path, $html_file_content ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$app_file_path'";

            // Throwing an exception
            throw new \Exception($message);
        }



        // (Getting the value)
        $build_folder_path = __DIR__ . '/web/build';



        // (Getting the value)
        $core_file_path    = __DIR__ . '/svelte/.core';
        $core_file_content =
        [
            'dev_server'   =>
            [
                'host'     => "front-dev.{$app_id}",
                'https'    =>
                [
                    'key'  => __DIR__ . '/svelte/cert/key.pem',
                    'cert' => __DIR__ . '/svelte/cert/cert.pem'
                ]
            ],

            'build_path'   => $build_folder_path
        ]
        ;

        if ( file_put_contents( $core_file_path, json_encode( $core_file_content, JSON_PRETTY_PRINT ) ) === false )
        {// (Unable to write to the file)
            // (Setting the value)
            $message = "Unable to write to the file '$core_file_path'";

            // Throwing an exception
            throw new \Exception($message);
        }



        // (Executing the cmd)
        system("npm run build");



        if ( !is_dir( $core_file_content['build_path'] ) )
        {// (Directory not found)
            // Printing the value
            echo "\n\nUnable to build the app :: Vite build is failed\n\n\n";

            // Closing the process
            exit;
        }



        // (Getting the value)
        $cap_dir = __DIR__ . '/capacitor';

        // (Setting the cwd)
        chdir( $cap_dir );



        // (Executing the command)
        echo shell_exec("rm -rf $cap_dir/android");
        echo shell_exec("rm -rf $cap_dir/ios");

        echo shell_exec('npx cap add android');
        echo shell_exec('npx cap add ios');



        // (Getting the value)
        $link = __DIR__ . '/capacitor/build';
        
        if ( is_link($link) ) unlink($link);

        // (Creating a symlink)
        symlink( $build_folder_path, $link );



        // (Executing the command)
        echo shell_exec('npx cap sync');



        // (Getting the value)
        $file_path_list = \Solenoid\System\Directory::select( $build_folder_path )->list( 0, '/\.html$/' );

        foreach ( $file_path_list as $file_path )
        {// Processing each entry
            // (Getting the value)
            $file_content = file_get_contents( $file_path );



            // (Replacing the text)
            $file_content = preg_replace( '/\.?\/_svelte/', "https://$be_host/build/_svelte", $file_content );



            /*
            
            // (Creating an HTMLBuilder)
            $html_builder = \Solenoid\HTML\Builder::create( $file_content );

            // (Appending the contents)
            $html_builder->append( 'head', "@include('assets/head.blade.php')" );
            $html_builder->append( 'body', "@include('assets/body.blade.php')" );

            // (Getting the value)
            $file_content = $html_builder->fetch_content();

            */



            // (Getting the value)
            $blade_file_path = preg_replace( '/\.html$/', '.blade.php', $file_path );



            // (Writing the content to the file)
            \Solenoid\System\File::select( $blade_file_path )->write( $file_content );
        }



        // (Printing the value)
        echo "\n\nApp -> https://$be_host\n\n\n";
    break;

    case 'release':
        // (Including the file)
        include_once( __DIR__ . '/release.php' );
    break;



    default:
        // Printing the value
        echo $helper;
}



?>