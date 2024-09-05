<svelte:head>
    <title>{ title }</title>
</svelte:head>

<script>

    import App from '../../../views/App.svelte';
    import Base from '../../../views/components/Base.svelte';
    import Table from '../../../views/components/Table.svelte';

    import { envs } from '../../../envs.js';
    import { appReady } from '../../../stores/appReady.js';
    import { user } from '../../../stores/user.js';



    let title = '';



    let tableRecords = [];



    // Returns [Promise:bool]
    async function fetchData ()
    {
        // (Sending a request)
        const response = await Solenoid.HTTP.sendRequest
        (
            envs.APP_URL + '/rpc',
            'RPC',
            [
                'Action: user::fetch_data',
                'Content-Type: application/json',

                'Route: ' + window.location.pathname
            ],
            '',
            '',
            true
        )
        ;

        if ( response.status.code !== 200 )
        {// (Request failed)
            if ( response.status.code === 401 )
            {// (Client is not authorized)
                // (Setting the location)
                window.location.href = '/admin/login';



                // Returning the value
                return false;
            }



            // (Alerting the value)
            alert( response.body['error']['message'] );



            // Returning the value
            return false;
        }



        // (Getting the value)
        $user = response.body['user'];



        // (Getting the value)
        title = `Users ( ${ response.body['records'].length } )`;



        // (Setting the value)
        const records = [];

        for ( const record of response.body['records'] )
        {// Processing each entry
            // (Getting the value)
            const r =
            {
                'id':             record['id'],

                'values':
                [
                    {
                        'column': 'id',
                        'value':  record['id']
                    },

                    {
                        'column': 'action',
                        'value':  record['action']
                    },

                    {
                        'column': 'ip',
                        'value':  [ record['ip'], record['ip_info']['country']['code'], record['ip_info']['isp'] ].join(' - ')
                    },

                    {
                        'column': 'browser',
                        'value':  record['ua_info']['browser']
                    },

                    {
                        'column': 'os',
                        'value':  record['ua_info']['os']
                    },

                    {
                        'column': 'hw',
                        'value':  record['ua_info']['hw']
                    },

                    {
                        'column': 'datetime.insert',
                        'value':  record['datetime']['insert']
                    },
                ],

                'controls':
                    record['session'] && !record['current_session']
                        ?
                    `
                        <button class="btn btn-sm btn-danger" value="user::terminate_session" title="terminate session">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    `
                        :
                    ''
                ,

                'hidden': false
            }
            ;



            // (Appending the value)
            records.push( r );
        }



        // (Getting the value)
        tableRecords = records;



        // Returning the value
        return true;
    }



    $:
        if ( $appReady )
        {// Value is true
            // (Fetching the data)
            fetchData();
        }



    // Returns [Promise:void]
    async function onTableRecordAction (event)
    {
        // (Setting the value)
        let result = false;



        // (Getting the value)
        const entry = event.detail;

        switch ( entry.action )
        {
            case 'user::terminate_session':
                // (Terminating the session)
                result = await terminateSession( entry.id );

                // (Removing the element)
                jQuery(entry.element).find('.controls .btn[value="' + entry.action + '"]').remove();
            break;
        }



        // Returning the value
        return result;
    }

</script>

<App>
    <Base>
        <Table title={ title } bind:records={ tableRecords } on:record.action={ onTableRecordAction }/>
    </Base>
</App>