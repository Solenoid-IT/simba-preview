<svelte:head>
    <title>{ title }</title>
</svelte:head>

<script>

    import App from '../../../views/App.svelte';
    import Base from '../../../views/components/Base.svelte';
    import Table from '../../../views/components/Table.svelte';
    import SelectionTable from '../../../views/components/SelectionTable.svelte';
    import Modal from '../../../views/components/Modal.svelte';
    import Form from '../../../views/components/Form.svelte';

    import { envs } from '../../../envs.js';

    import { appReady } from '../../../stores/appReady.js';
    import { appData } from '../../../stores/appData.js';



    let resourceType       = 'document';

    let resourceName       = 'Document';
    let resourceNamePlural = 'Documents';



    let title = '';



    let hierarchies = {};



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
        $appData = response.body;



        // (Getting the value)
        title = `${ resourceNamePlural } ( ${ $appData.records.length } )`;



        // (Setting the value)
        const records = [];

        for ( const record of $appData.records )
        {// Processing each entry
            // (Getting the value)
            const r =
            {
                'id':             record['id'],

                'values':
                [
                    {
                        'column': 'path',
                        'value':  record['path']
                    },

                    {
                        'column': 'title',
                        'value':  record['title']
                    },

                    {
                        'column': 'description',
                        'value':  record['description']
                    },

                    {
                        'column': 'datetime.insert',
                        'value':  record['datetime']['insert']
                    },

                    {
                        'column': 'datetime.update',
                        'value':  record['datetime']['update']
                    },

                    {
                        'column':  'datetime.option.active',
                        'value':   record['datetime']['option']['active'],

                        'content':
                            `
                                <input type="checkbox" ${ record['datetime']['option']['active'] ? 'checked' : '' } title="active">
                            `
                    },

                    {
                        'column':  'datetime.option.sitemap',
                        'value':   record['datetime']['option']['sitemap'],

                        'content':
                            `
                                <input type="checkbox" ${ record['datetime']['option']['sitemap'] ? 'checked' : '' } title="sitemap">
                            `
                    },
                ],

                'controls':
                    `
                        <div class="d-flex justify-content-center align-items-center">
                            <button class="btn btn-sm btn-warning" value="update" title="edit">
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger ml-2" value="delete" title="remove">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `
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



    // (Setting the value)
    const Resource =
    {
        'find': async function (id)
        {
            // (Sending a request)
            const response = await Solenoid.HTTP.sendRequest
            (
                envs.APP_URL + '/rpc',
                'RPC',
                [
                    'Action: ' + resourceType + '::find',
                    'Content-Type: application/json',

                    'Route: ' + window.location.pathname
                ],
                JSON.stringify
                (
                    {
                        'id': id
                    }
                ),
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



            // Returning the value
            return response.body;
        },

        'remove': async function (ids)
        {
            if ( !confirm('Are you sure to remove the selected entries ?') ) return;



            // (Sending a request)
            const response = await Solenoid.HTTP.sendRequest
            (
                envs.APP_URL + '/rpc',
                'RPC',
                [
                    'Action: ' + resourceType + '::delete',
                    'Content-Type: application/json'
                ],
                JSON.stringify( ids ),
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



            // (Fetching the data)
            await fetchData();



            // Returning the value
            return true;
        }
    }
    ;



    let table;

    // Returns [Promise:void]
    async function onTableRecordAction (event)
    {
        // (Setting the value)
        let result = false;



        // (Getting the value)
        const entry = event.detail;

        switch ( entry.action )
        {
            case 'update':
                // (Getting the value)
                const record = await Resource.find( entry.id );

                // (Getting the values)
                resourceForm.setValues(record);

                // (Showing the modal)
                resourceModal.show();
            break;

            case 'delete':
                // (Removing the resource)
                result = await Resource.remove( [ entry.id ] );
            break;
        }



        // Returning the value
        return result;
    }

    // Returns [void]
    function onTableSelectionChange (event)
    {
        // (Getting the value)
        const ids = table.fetchSelectedRecords();

        // (Consoling the value)
        console.debug(ids);// ahcid
    }

    // Returns [Promise:bool]
    async function onBulkRemove ()
    {
        // (Setting the value)
        const ids = [];

        for ( const id of table.fetchSelectedRecords() )
        {// Processing each entry
            // (Appending the value)
            ids.push( tableRecords[id].id );
        }



        // (Removing the users)
        const result = await Resource.delete( ids );



        // Returning the value
        return result;
    }



    let resourceModal;
    


    let resourceForm;

    let resourceFormMsg = '';

    // Returns [Promise:bool]
    async function onResourceFormSubmit ()
    {
        // (Validating the form)
        const result = resourceForm.validate();

        if ( !result.valid ) return false;



        // (Setting the value)
        resourceFormMsg = '';



        // (Sending the request)
        const response = await Solenoid.HTTP.sendRequest
        (
            envs.APP_URL + '/rpc',
            'RPC',
            [
                'Action: ' + resourceType + '::' + ( result.entries['id'].value ? 'update' : 'insert' ),
                'Content-Type: application/json'
            ],
            JSON.stringify( result.fetch() ),
            'json',
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



        // (Fetching the data)
        await fetchData();



        // Returning the value
        return true;
    }



    let availableTagsRecords = [];



    // debug
    availableTagsRecords =
    [
        {
            'id': 1,

            'values':
            [
                {
                    'column': 'name',
                    'value':  'iOS'
                }
            ],

            'controls': ''
        },
        {
            'id': 2,

            'values':
            [
                {
                    'column': 'name',
                    'value':  'Android'
                }
            ],

            'controls': ''
        },
    ]
    ;

</script>

<App>
    <Base>
        <Table title={ title } bind:api={ table } bind:records={ tableRecords } on:record.action={ onTableRecordAction } selectable on:selection.change={ onTableSelectionChange }>
            <div slot="fixed-controls">
                { #if $appData.user.user.hierarchy === 1 }
                    <button class="btn btn-primary btn-sm" title="add" on:click={ resourceModal.show }>
                        <i class="fa-solid fa-plus"></i>
                    </button>
                { /if }
            </div>
            <div slot="selection-controls">
                { #if $appData.user.user.hierarchy === 1 }
                    <button class="btn btn-danger btn-sm" on:click={ onBulkRemove } title="remove">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                { /if }
            </div>
        </Table>

        <Modal title="{ resourceName }" bind:api={ resourceModal } width="1300px">
            <Form bind:api={ resourceForm } on:submit={ onResourceFormSubmit }>
                <input type="hidden" class="form-input" name="id" value="">

                <div class="row">
                    <div class="col">
                        <label class="d-block m-0">
                            Path
                            <input type="text" class="form-control input form-input" name="path" data-required>
                        </label>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col">
                        <label class="d-block m-0">
                            Title
                            <input type="text" class="form-control input form-input" name="title" data-required>
                        </label>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col">
                        <label class="d-block m-0">
                            Description
                            <textarea  class="form-control input form-input" name="description"></textarea>
                        </label>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col">
                        <label class="d-block m-0">
                            Content
                            <textarea  class="form-control input form-input" name="content"></textarea>
                        </label>
                    </div>
                </div>

                <fieldset class="fieldset mt-2">
                    <legend>Tags</legend>
                    <SelectionTable input='tags' bind:availableRecords={ availableTagsRecords }/>
                </fieldset>

                <div class="row mt-4">
                    <div class="col text-center">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col text-center">
                        <span class="color-warning">{ resourceFormMsg }</span>
                    </div>
                </div>
            </Form>
        </Modal>
    </Base>
</App>