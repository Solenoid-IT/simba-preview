<svelte:head>
    <title>{ title }</title>
</svelte:head>

<script>

    import App from '../../../views/App.svelte';
    import Base from '../../../views/components/Base.svelte';
    import Table from '../../../views/components/Table.svelte';
    import Modal from '../../../views/components/Modal.svelte';
    import Form from '../../../views/components/Form.svelte';

    import { envs } from '../../../envs.js';
    import { appReady } from '../../../stores/appReady.js';
    import { user } from '../../../stores/user.js';



    let resourceType = 'User';
    let resourceName = 'Users';



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
        $user = response.body['user'];



        // (Getting the value)
        title = `${ resourceName } ( ${ response.body['records'].length } )`;



        // (Getting the value)
        hierarchies = response.body['hierarchies'];



        // (Setting the value)
        const records = [];

        for ( const record of response.body['records'] )
        {// Processing each entry
            // (Getting the value)
            const r =
            {
                'id':             record['email'],

                'values':
                [
                    {
                        'column':  'hierarchy',
                        'value':   hierarchies[ record['hierarchy'] ]['type'],

                        'content':
                            `
                                <span class="tag-label" style="background-color: ${ hierarchies[ record['hierarchy'] ]['color'] };">${ hierarchies[ record['hierarchy'] ]['type'] }</span>
                            `
                    },

                    {
                        'column': 'name',
                        'value':  record['name']
                    },

                    {
                        'column': 'email',
                        'value':  record['email']
                    },

                    {
                        'column': 'birth.name',
                        'value':  record['birth']['name']
                    },

                    {
                        'column': 'birth.surname',
                        'value':  record['birth']['surname']
                    },

                    {
                        'column': 'datetime.insert',
                        'value':  record['datetime']['insert']
                    },
                ],

                'controls':
                    `
                        <button class="btn btn-sm btn-danger" value="user::remove" title="remove">
                            <i class="fa-solid fa-trash"></i>
                        </button>
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



    let userModal;
    


    let userForm;

    let userFormMsg = '';

    // Returns [Promise:bool]
    async function onUserFormSubmit ()
    {
        // (Validating the form)
        const result = userForm.validate();

        if ( !result.valid ) return false;



        // (Setting the value)
        userFormMsg = '';



        // (Sending the request)
        const response = await Solenoid.HTTP.sendRequest
        (
            envs.APP_URL + '/rpc',
            'RPC',
            [
                'Action: user::add',
                'Content-Type: application/json'
            ],
            JSON.stringify( result.fetch() ),
            'json',
            true
        )
        ;

        if ( response.status.code !== 200 )
        {// (Request failed)
            // (Alerting the value)
            alert( response.body['error']['message'] );



            // Returning the value
            return false;
        }



        // (Setting the value)
        userForm.disabled = true;



        // (Setting the value)
        userFormMsg = `Confirm operation by email '${ result.entries['email'].value }' ...`;



        // Returning the value
        return true;
    }

</script>

<App>
    <Base>
        <Table title={ title } bind:records={ tableRecords } on:record.action={ onTableRecordAction }>
            <div slot="controls">
                { #if $user.user.hierarchy === 1 }
                    <button class="btn btn-primary" title="add" on:click={ userModal.show }>
                        <i class="fa-solid fa-plus"></i>
                    </button>
                { /if }
            </div>
        </Table>

        <Modal title="{ resourceType }" bind:api={ userModal }>
            <Form bind:api={ userForm } on:submit={ onUserFormSubmit }>
                <div class="row">
                    <div class="col">
                        <label class="d-block m-0">
                            Name
                            <input type="text" class="form-control input" name="name" data-required>
                        </label>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col">
                        <label class="d-block m-0">
                            Email
                            <input type="text" class="form-control input" name="email" data-required>
                        </label>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col">
                        <label class="d-block m-0">
                            Hierarchy
                            <select class="form-control input" name="hierarchy" data-required>
                                <option value=""></option>
                                <option disabled></option>

                                { #each Object.values( hierarchies ) as hierarchy }
                                    <option value="{ hierarchy.id }">{ hierarchy.type }</option>
                                { /each }
                            </select>
                        </label>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col text-center">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col">
                        <span class="color-warning">{ userFormMsg }</span>
                    </div>
                </div>
            </Form>
        </Modal>
    </Base>
</App>