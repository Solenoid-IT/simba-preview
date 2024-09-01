<svelte:head>
    <title>Login</title>
</svelte:head>

<script>

    import App from '../../../views/App.svelte';
    import Form from '../../../views/components/Form.svelte';
    import PasswordField from '../../../views/components/PasswordField.svelte';

    import { envs } from '../../../envs.js';
    
    import { idk } from '../../../stores/idk.js';

    import { goto } from '$app/navigation';



    let loginErrMsg = '';
    let loginWrnMsg = '';



    let loginForm;



    // Returns [Promise:bool]
    async function onLoginFormSubmit ()
    {
        // (Validating the form)
        let result = loginForm.validate();

        if ( !result.valid ) return false;



        // (Sending the request)
        const response = await Solenoid.HTTP.sendRequest
        (
            envs.APP_URL + '/rpc',
            'RPC',
            [
                'Action: user::login',
                'Content-Type: application/json'
            ],
            JSON.stringify( result.fetch() ),
            'json',
            true
        )
        ;

        if ( response.status.code !== 200 )
        {// (Request failed)
            // (Getting the value)
            loginErrMsg = response.body['error']['message'];
            loginWrnMsg = '';



            // Returning the value
            return false;
        }



        // (Setting the value)
        loginErrMsg = '';
        loginWrnMsg = 'Confirm operation by email ...';



        // (Sending the request)
        const res = await Solenoid.HTTP.sendRequest
        (
            envs.APP_URL + '/rpc',
            'RPC',
            [
                'Action: user::login_wait',
                'Content-Type: application/json'
            ],
            '',
            'json',
            true
        )
        ;

        if ( res.status.code !== 200 )
        {// (Request failed)
            // (Getting the value)
            loginErrMsg = res.body['error']['message'];



            // Returning the value
            return false;
        }



        // (Setting the value)
        loginWrnMsg = '';



        // (Moving to the URL)
        goto( res.body['location'] );



        // Returning the value
        return true;
    }



    // Returns [Promise:void]
    async function importIDK ()
    {
        // (Selecting the files)
        const file = ( await Solenoid.File.select( '.idk' ) )[0];



        // (Getting the value)
        $idk = await Solenoid.File.read( file );

        // (Setting the item)
        localStorage.setItem( 'idk', $idk );
    }

    // Returns [void]
    function ejectIDK ()
    {
        if ( !confirm('Are you sure to remove the IDK from the local memory ?') ) return;



        // (Removing the item)
        localStorage.removeItem( 'idk' );

        // (Setting the value)
        $idk = null;
    }



    // Returns [Promise:bool]
    async function loginWithIDK ()
    {
        // (Sending the request)
        const response = await Solenoid.HTTP.sendRequest
        (
            envs.APP_URL + '/rpc',
            'RPC',
            [
                'Action: user::login_with_idk',
                'Content-Type: text/plain'
            ],
            $idk,
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



        // (Moving to the URL)
        goto( '/admin/dashboard' );



        // Returning the value
        return true;
    }



    // Returns [Promise:bool]
    async function recoverUser ()
    {

    }

</script>

<App>
    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col">
                                <div class="p-5">
                                    <img class="mb-3" id="login_logo" src="{ envs.APP_URL }/assets/images/simba.jpg" alt="">

                                    <Form id="login_form" on:submit={ onLoginFormSubmit } bind:api={ loginForm }>
                                        <div class="form-group">
                                            <input type="text" class="form-control input" name="login" placeholder="Login" data-required>
                                        </div>
                                        <div class="form-group">
                                            <PasswordField name="password" placeholder="Password" required/>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox small">
                                                <input type="checkbox" class="custom-control-input" id="customCheck">
                                                <label class="custom-control-label" for="customCheck">Remember
                                                    Me</label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-block">
                                            Login
                                        </button>

                                        <div class="row mt-3">
                                            <div class="col text-center">
                                                <span class="color-danger">{ loginErrMsg }</span>
                                                <span class="color-warning">{ loginWrnMsg }</span>
                                            </div>
                                        </div>
                                    </Form>
                                    <hr>
                                    <div class="row mt-2">
                                        <div class="col text-center">
                                            { #if $idk }
                                                <button class="btn btn-danger" on:click={ ejectIDK } title="eject IDK">
                                                    <i class="fa-solid fa-eject"></i>
                                                </button>

                                                <button class="btn btn-primary ml-2" on:click={ loginWithIDK } title="login with IDK">
                                                    <i class="fa-solid fa-right-to-bracket"></i>
                                                </button>
                                            { :else }
                                                <button class="btn btn-secondary" on:click={ importIDK } title="import IDK">
                                                    <i class="fa-solid fa-upload"></i>
                                                </button>
                                            { /if }
                                        </div>
                                        <div class="col text-center">
                                            <button class="btn btn-danger" on:click={ recoverUser } title="recover user">
                                                <i class="fa-solid fa-lock-open"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</App>

<style>

    :global( body )
    {
        background-color: #c2c2c2;
    }

    .container
    {
        width: 768px;
        height: 100vh;
        display: flex;
        align-items: center;
    }

    .container > .row
    {
        width: 100%;
    }

    #login_logo
    {
        margin: 0 auto;
        padding: 20px;
        height: 140px;
        display: table;
        object-fit: cover;
        border-radius: 32px;
    }

</style>