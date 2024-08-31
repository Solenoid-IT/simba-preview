<svelte:head>
    <title>Login</title>
</svelte:head>

<script>

    import App from '../../../views/App.svelte';
    import Form from '../../../views/components/Form.svelte';
    import PasswordField from '../../../views/components/PasswordField.svelte';

    import { envs } from '../../../envs.js';

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
                                    <div class="text-center">
                                        <a class="small" href="forgot-password.html">Forgot Password?</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="register.html">Create an Account!</a>
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