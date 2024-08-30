<svelte:head>
    <title>Login</title>
</svelte:head>

<script>

    import App from '../../../views/App.svelte';
    import Form from '../../../views/components/Form.svelte';

    import { envs } from '../../../envs.js';



    let loginErrMsg = 'error';
    let loginWrnMsg = 'warning';



    let loginForm;



    // Returns [Promise:bool]
    async function onLoginFormSubmit ()
    {
        // (Validating the form)
        const result = loginForm.validate();

        console.debug(result);
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
                                            <input type="password" class="form-control input" name="password" placeholder="Password" data-required>
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