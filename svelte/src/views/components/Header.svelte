<script context="module">

    import { Core } from '$lib/solenoid/solenoid.core.js';

    import { onMount } from 'svelte';

    import { user } from '../../stores/user.js';
    import { requiredActions } from '../../stores/requiredActions.js';

    import { pendingAction } from '../../stores/pendingAction.js';

    import { idk } from '../../stores/idk.js';

</script>

<script>

    import Form from '../components/Form.svelte';
    import Button from '../components/Button.svelte';
    import Modal from '../components/Modal.svelte';
    import PasswordField from './PasswordField.svelte';
    import Switch from './Switch.svelte';
    import PTable from './PTable.svelte';
    import AppHistory from './AppHistory.svelte';



    let viewAccessForm = null;



    let fullscreenButton = null;



    let sidebarToggleElement = null;



    let appInfoButton = null;



    // Returns [void]
    function onKeyUp (event)
    {
        if ( document.activeElement !== document.body ) return;

        switch ( event.key )
        {// Match OK
            case 'f':
                // (Triggering the event)
                fullscreenButton.element.click();
            break;

            case 's':
                // (Triggering the event)
                sidebarToggleElement.click();
            break;

            case 'i':
                // (Triggering the event)
                appInfoButton.element.click();
            break;

            case 'o':
                // (Triggering the event)
                logoutButton.click();
            break;
        }
    }



    const defaultProfileImageSrc = Core.asset( '/assets/images/user.png' );
    let userProfileImageSrc      = null;
    let currentProfileImageSrc   = null;

    $:
        if ( $user )
        {// Value found
            // (Getting the values)
            userProfileImageSrc    = $user['profile']['photo'] ? `data:${ $user['profile']['photo']['type'] };base64,${ $user['profile']['photo']['content'] }` : defaultProfileImageSrc;
            currentProfileImageSrc = userProfileImageSrc;
        }



    let changeProfileModal = null;
    let changeSecurityModal = null;



    let changeProfileForm = null;

    // Returns [void]
    async function onChangeProfileFormSubmit ()
    {
        if ( Object.keys( changeProfileForm.validate(true) ).length > 0 ) return;



        // (Sending an http request)
        const response = await Solenoid.HTTP.sendRequest
        (
            Core.URL.build( '/admin/user' ),
            'RPC',
            [
                'Action: profile::change',
                'Content-Type: application/json'
            ],
            JSON.stringify( await changeProfileForm.getInput() ),
            '',
            true
        )
        ;

        if ( response.status.code !== 200 )
        {// (Request failed)
            // (Alerting the value)
            alert( response.body['error']['message'] );

            // Returning the value
            return;
        }



        // (Alerting the value)
        alert( 'Profile has been changed' );

        // (Moving to the URL)
        Solenoid.URL.move('');
    }

    $:
        if ( changeProfileForm )
        {// Value found
            // (Change-Event on the element)
            jq('#change_profile_form .input[name="photo_action"]').on('change', function () {
                // (Getting the value)
                const value = jq(this).val();

                switch ( value )
                {
                    case '':
                        // (Setting the value)
                        jq('#change_profile_form .input[name="photo"]').val('');

                        // (Getting the value)
                        currentProfileImageSrc = userProfileImageSrc;
                    break;

                    case 'change':
                        // (Triggering the event)
                        jq('#change_profile_form .input[name="photo"]').trigger('click');

                        // (Getting the value)
                        currentProfileImageSrc = '';
                    break;

                    case 'remove':
                        // (Setting the value)
                        jq('#change_profile_form .input[name="photo"]').val('');

                        // (Getting the value)
                        currentProfileImageSrc = defaultProfileImageSrc;
                    break;
                }
            });

            // (Change-Event on the element)
            jq('#change_profile_form .input[name="photo"]').on('change', async function () {
                // (Getting the value)
                const input = await changeProfileForm.getInput();

                // (Getting the value)
                currentProfileImageSrc = `data:${ input['photo']['type'] };base64,${ input['photo']['content'] }`;
            });
        }



    let changeUsernameForm = null;

    // Returns [void]
    async function onChangeUsernameFormSubmit ()
    {
        if ( Object.keys( changeUsernameForm.validate(true) ).length > 0 ) return;

        if( !confirm( 'Are you sure to change the username ?' ) ) return;



        // (Sending an http request)
        const response = await Solenoid.HTTP.sendRequest
        (
            Core.URL.build( '/admin/user' ),
            'RPC',
            [
                'Action: username::change',
                'Content-Type: application/json'
            ],
            JSON.stringify( await changeUsernameForm.getInput() ),
            '',
            true
        )
        ;

        if ( response.status.code !== 200 )
        {// (Request failed)
            // (Alerting the value)
            alert( response.body['error']['message'] );



            // (Setting the input)
            changeUsernameForm.setInput( { 'username': $user['username'] } );



            // Returning the value
            return;
        }



        // (Alerting the value)
        alert( 'Username has been changed' );

        // (Moving to the URL)
        Solenoid.URL.move('');
    }



    let changeEmailForm = null;

    // Returns [void]
    async function onChangeEmailFormSubmit ()
    {
        if ( Object.keys( changeEmailForm.validate( true ) ).length > 0 ) return;

        if ( !confirm('Are you sure to change the email ?') ) return;



        // (Sending an http request)
        const response = await Solenoid.HTTP.sendRequest
        (
            Core.URL.build( '/admin/user' ),
            'RPC',
            [
                'Action: email::change',
                'Content-Type: application/json'
            ],
            JSON.stringify( await changeEmailForm.getInput() ),
            '',
            true
        )
        ;

        if ( response.status.code !== 200 )
        {// (Request failed)
            if ( response.status.code === 401 )
            {// (Authentication failed)
                // (Moving to the URL)
                Core.URL.open( '/admin/login' );



                // Returning the value
                return;
            }



            // (Alerting the value)
            alert( response.body['error']['message'] );



            // Returning the value
            return;
        }



        // (Creating a connection)
        const connection = new Solenoid.SSE.Connection( Core.URL.build( '/admin/authorization' ), null, true );
	
        // (Listening for the event)
        connection.addEventListener('close', async function (event) {
            // (Closing the connection)
            connection.close();



            // (Setting the value)
            $pendingAction = null;



            // (Setting the input)
            //changeEmailForm.setInput( { 'email': $user['email'] } );



            // (Alerting the value)
            alert( 'Email has been changed' );

            // (Moving to the URL)
            Core.URL.open('');
        });



        // (Opening the connection)
        connection.open();



        // (Getting the values)
        $pendingAction =
        {
            'message': 'Confirm Authorization via EMAIL',
            'duration': response.body['exp_time'] - Solenoid.DateTime.fetchCurrentTimestamp()
        }
        ;
    }


    
    let changePasswordForm = null;

    // Returns [void]
    async function onChangePasswordFormSubmit ()
    {
        if ( Object.keys( changePasswordForm.validate( true ) ).length > 0 ) return;



        if ( !confirm('Are you sure to change the password ?') ) return;



        // (Sending an http request)
        const response = await Solenoid.HTTP.sendRequest
        (
            Core.URL.build( '/admin/user' ),
            'RPC',
            [
                'Action: security::change',
                'Content-Type: application/json'
            ],
            JSON.stringify( await changePasswordForm.getInput() ),
            '',
            true
        )
        ;

        if ( response.status.code !== 200 )
        {// (Request failed)
            // (Alerting the value)
            alert( response.body['error']['message'] );

            // Returning the value
            return;
        }



        // (Alerting the value)
        alert( 'Password has been changed' );

        // (Moving to the URL)
        Solenoid.URL.move('');
    }



    let mfaSwitch = null;

    $:
        if ( mfaSwitch )
        {// Value found
            // (Listening for the event)
            mfaSwitch.addEventListener('change', async function (event) {
                // (Sending an http request)
                const response = await Solenoid.HTTP.sendRequest
                (
                    Core.URL.build( '/admin/user' ),
                    'RPC',
                    [
                        'Action: security::change',
                        'Content-Type: application/json'
                    ],
                    JSON.stringify
                    (
                        {
                            'mfa': event.originalEvent.target.checked
                        }
                    ),
                    '',
                    true
                )
                ;

                if ( response.status.code !== 200 )
                {// (Request failed)
                    // (Alerting the value)
                    alert( response.body['error']['message'] );

                    // Returning the value
                    return;
                }
            });
        }
    


    let idkAuthSwitch = null;

    $:
        if ( idkAuthSwitch )
        {// Value found
            // (Listening for the event)
            idkAuthSwitch.addEventListener('change', async function (event) {
                // (Getting the value)
                const target = event.originalEvent.target;

                if ( target.checked )
                {// Value is true
                    if ( !confirm("Are you sure to enable the IDK authentication ?\n\nThis login method allow you authenticating only by importing the IDK file.") )
                    {// (Confirmation failed)
                        // (Setting the value)
                        target.checked = false;

                        // Returning the value
                        return;
                    }
                }



                // (Sending an http request)
                const response = await Solenoid.HTTP.sendRequest
                (
                    Core.URL.build( '/admin/user' ),
                    'RPC',
                    [
                        'Action: security::change',
                        'Content-Type: application/json'
                    ],
                    JSON.stringify
                    (
                        {
                            'idk_authentication': target.checked
                        }
                    ),
                    '',
                    true
                )
                ;

                if ( response.status.code !== 200 )
                {// (Request failed)
                    // (Alerting the value)
                    alert( response.body['error']['message'] );

                    // Returning the value
                    return;
                }
            });
        }



    let idkDownloadModal = null;



    let logoutButton = null;

    $:
        if ( logoutButton )
        {// Value found
            // (Listening for the event)
            logoutButton.addEventListener('click', async function () {
                if ( !confirm('Are you sure to leave this session ?') ) return;



                // (Sending an http request)
                const response = await Solenoid.HTTP.sendRequest
                (
                    Core.URL.build( '/admin/user' ),
                    'RPC',
                    [
                        'Action: user::logout',
                        'Content-Type: application/json'
                    ],
                    '',
                    '',
                    true
                )
                ;

                if ( response.status.code !== 200 )
                {// (Request failed)
                    // (Alerting the value)
                    alert( response.body['error']['message'] );

                    // Returning the value
                    return;
                }



                // (Moving to the URL)
                Solenoid.URL.move('/admin/login');
            });
        }



    let appInfoModal = null;

    let appInfoProps =
    [
        {
            'label':
            {
                'id':    'name',
                'value': 'Name' 
            },

            'content':   Core.envs.APP_NAME
        },

        {
            'label':
            {
                'id':    'version',
                'value': 'Version' 
            },

            'content':   Core.envs.APP_VERSION
        },

        {
            'label':
            {
                'id':    'build_time',
                'value': 'Build Time' 
            },

            'content':   new Date( Core.envs.APP_BUILD_TIME * 1000 ).toISOString()
        }
    ]
    ;



    let appHistory = null;

    // Returns [Promise:object]
    async function fetchAppHistory ()
    {
        // (Sending a request)
        const response = await Solenoid.HTTP.sendRequest
        (
            Core.URL.build('/history.json'),
            'GET',
            [],
            '',
            '',
            true
        )
        ;



        // Returning the value
        return response.body;
    }



    // Returns [void]
    async function generateIDK ()
    {
        if( !confirm( 'Are you sure to generate a new IDK ?' ) ) return;



        // (Sending an http request)
        const response = await Solenoid.HTTP.sendRequest
        (
            Core.URL.build( '/admin/user' ),
            'RPC',
            [
                'Action: idk::generate',
                'Content-Type: application/json'
            ],
            '',
            '',
            true
        )
        ;

        if ( response.status.code !== 200 )
        {// (Request failed)
            // (Alerting the value)
            alert( response.body['error']['message'] );

            // Returning the value
            return;
        }



        // (Getting the value)
        $idk =
        {
            'content':    response.body['idk'],
            'importTime': Solenoid.DateTime.fetchCurrentTimestamp()
        }
        ;

        // (Writing to the local storage)
        Solenoid.LocalStorage.write( 'idk', $idk );



        // (Opening the modal)
        idkDownloadModal.open();
    }

    // Returns [void]
    function downloadIDK ()
    {
        // (Downloading the file)
        Solenoid.URL.download( Solenoid.URL.buildDataURL( $idk['content'], 'text/plain' ), `${ window.location.host }.idk` );



        // (Closing the modal)
        idkDownloadModal.close();
    }

    // Returns [void]
    function importIDK ()
    {
        // (Triggering the event)
        this.querySelector('.input[name="file"]').click();
    }

    // Returns [void]
    async function storeIDK (event)
    {
        // (Getting the value)
        $idk =
        {
            'content':    await Solenoid.File.read( event.target.files[0] ),
            'importTime': Solenoid.DateTime.fetchCurrentTimestamp()
        }
        ;

        // (Writing to the local storage)
        Solenoid.LocalStorage.write( 'idk', $idk );
    }

    // Returns [void]
    function ejectIDK ()
    {
        if ( !confirm('Are you sure to remove the IDK from local memory ?') ) return;



        // (Setting the value)
        $idk = null;

        // (Clearing the local storage)
        Solenoid.LocalStorage.clear( 'idk' );
    }



    // (Listening for the event)
    onMount
    (
        function ()
        {
            // (Getting the value)
            $idk = Solenoid.LocalStorage.read( 'idk' );
        }
    )
    ;



    let requiredActionModal = null;

    $:
        if ( $requiredActions.length > 0 )
        {// Value found
            if ( requiredActionModal )
            {// Value found
                // (Opening the modal)
                requiredActionModal.open();
            }
        }
    
    
    
    // Returns [Promise:void]
    async function openAppNewVersionModal()
    {
        // (Getting the value)
        appHistory = await fetchAppHistory();

        // (Getting the value)
        appNewVersionModal.open();
    }



    // Returns [Promise:bool]
    async function markChangelogAsRead ()
    {
        // (Sending a request)
        const response = await Solenoid.HTTP.sendRequest
        (
            Core.URL.build('/admin/user'),
            'RPC',
            [
                'Action: user::mark_changelog_as_read',
                'Content-Type: application/json'
            ],
            '',
            '',
            true
        )
        ;

		if ( response.status.code !== 200 )
		{// Match failed
			if ( response.status.code === 401 )
			{
				// (Moving to the URL)
				Solenoid.URL.move( '/admin/login' );

				// Returning the value
				return false;
			}



			// (Alerting the value)
			alert( response.body['error']['message'] );

			// Returning the value
			return false;
		}



        // Returning the value
        return true;
    }



    let appNewVersionModal = null;

    $:
        if ( appNewVersionModal )
        {// Value found
            if ( $user )
            {// Value found
                if ( !$user['datetime']['changelog_mark_as_read'] )
                {// Match failed
                    // (Opening the app new version modal)
                    openAppNewVersionModal();
                }
            }
        }

</script>

<svelte:window on:keyup={ onKeyUp }/>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark fixed-top">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3" href="/">
        <img class="brand-logo" src="{ Core.asset( '/assets/images/simba.png' ) }" alt="Core">
    </a>

    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebar_toggle_button" title="Sidebar ON/OFF (S)" style="color: #ffffff;"
        on:click={ () => { document.body.classList.toggle('sb-sidenav-toggled'); } }
        bind:this={ sidebarToggleElement }
    >
        <i class="fas fa-bars"></i>
    </button>



    <!-- Navbar Search-->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <!--
        <div class="input-group">
            <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
            <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
        </div>
        -->
    </form>

    <!-- Fullscreen Button -->
    <Button class="me-2" id="fullscreen_button" title="Fullscreen ON/OFF (F)"
        bind:api={ fullscreenButton }
        on:click={ () => { window.fullScreen ? document.exitFullscreen() : document.body.requestFullscreen(); } }
    >
        <div slot="body" style="display: flex;">
            <i class="fa-solid fa-expand" style="height: 20px; color: #9a9c9e;"></i>
        </div>
    </Button>

    <!-- App-Info Button -->
    <Button class="me-2" id="app_info_button" title="App Info (I)"
        bind:api={ appInfoButton }
        on:click={ async () => { appHistory = await fetchAppHistory(); appInfoModal.open(); } }
    >
        <div slot="body" style="display: flex;">
            <i class="fa-solid fa-microchip" style="height: 20px; color: #9a9c9e;"></i>
        </div>
    </Button>

    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <!-- svelte-ignore a11y-invalid-attribute -->
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="User Menu">
                <i class="fas fa-user fa-fw"></i>
            </a>

            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li>
                    <!-- svelte-ignore a11y-missing-attribute -->
                    <!-- svelte-ignore a11y-click-events-have-key-events -->
                    <a class="dropdown-item" on:click={ () => { changeProfileModal.open(); } }>
                        Profile
                    </a>
                </li>
                <li>
                    <!-- svelte-ignore a11y-missing-attribute -->
                    <!-- svelte-ignore a11y-click-events-have-key-events -->
                    <a class="dropdown-item" on:click={ () => { changeSecurityModal.open(); } }>
                        Security
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>

                { #if $user['hierarchy'] === 1 }
                    <li>
                        <a class="dropdown-item" href="/admin/users">
                            Users
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                { /if }

                <li>
                    <!-- svelte-ignore a11y-missing-attribute -->
                    <a class="dropdown-item" bind:this={ logoutButton } title="Logout (o)">
                        Logout
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>



<Modal id="change_profile_modal" bind:api={ changeProfileModal } width="900px">
    <div slot="title">
        Profile
    </div>
    <div slot="body">
        <Form id="change_profile_form" bind:api={ changeProfileForm } on:submit={ onChangeProfileFormSubmit }>
            <div slot="body" class="p-2">
                <div class="row resp mt-2">
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control input" name="name" value="{ $user['profile']['name'] }" placeholder="name">
                            <!-- svelte-ignore a11y-label-has-associated-control -->
                            <label>Name</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control input" name="surname" value="{ $user['profile']['surname'] }" placeholder="surname">
                            <!-- svelte-ignore a11y-label-has-associated-control -->
                            <label>Surname</label>
                        </div>
                    </div>
                    <div class="col" style="position: relative;">
                        <div class="form-floating mb-3">
                            <select class="form-control input" name="photo_action">
                                <option value=""></option>
                                <option disabled></option>

                                <option value="change">
                                    Change
                                </option>
                                <option value="remove">
                                    Remove
                                </option>
                            </select>

                            <!-- svelte-ignore a11y-label-has-associated-control -->
                            <label>Photo</label>

                            <input type="file" class="form-control input" name="photo" placeholder="photo" hidden>
                        </div>

                        <img class="profile-image" src="{ currentProfileImageSrc }" alt="" style="margin: 10px; margin-right: 20px; position: absolute; top: 0; right: 0;">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col">
                        <button type="submit" class="btn btn-primary m-auto d-table">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </Form>

        <Form class="mt-3" id="change_username_form" bind:api={ changeUsernameForm } on:submit={ onChangeUsernameFormSubmit }>
            <div slot="body">
                <div class="row">
                    <div class="col">Username</div>
                </div>
    
                <div class="row p-2 mt-2">
                    <div class="col">
                        <input type="text" class="form-control input" name="username" value="{ $user['username'] }" placeholder="username" data-required>
                    </div>
                    <div class="col flex-grow-0">
                        <button type="submit" class="btn btn-primary m-auto d-table">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </Form>

        <Form class="mt-3" id="change_email_form" bind:api={ changeEmailForm } on:submit={ onChangeEmailFormSubmit }>
            <div slot="body">
                <div class="row">
                    <div class="col">Email</div>
                </div>
    
                <div class="row p-2 mt-2">
                    <div class="col">
                        <input type="text" class="form-control input" name="email" value="{ $user['email'] }" placeholder="email" data-required>
                    </div>
                    <div class="col flex-grow-0">
                        <button type="submit" class="btn btn-primary m-auto d-table">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </Form>
    </div>
</Modal>

<Modal id="change_security_modal" bind:api={ changeSecurityModal } width="700px">
    <div slot="title">
        Security
    </div>
    <div slot="body">
        <Form class="form-not-idk" id="change_password_form" bind:api={ changePasswordForm } on:submit={ onChangePasswordFormSubmit }>
            <div slot="body">
                <div class="row">
                    <div class="col">Password</div>
                </div>

                <div class="row resp p-2 mt-2">
                    <div class="col">
                        <PasswordField name="password" placeholder="password" required=true generateButton=true strengthMeter=true/>
                    </div>
                    <div class="col flex-grow-0">
                        <button type="submit" class="btn btn-primary m-auto d-table">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </Form>

        <Form class="form-not-idk mt-3" id="change_mfa_form">
            <div slot="body">
                <div class="row">
                    <div class="col">Multi-Factor Authentication ( <b>MFA</b> )</div>
                </div>

                <div class="row p-2 mt-2">
                    <div class="col d-flex justify-content-start align-items-center">
                        <Switch name="mfa" checked={ $user['security']['mfa'] } bind:api={ mfaSwitch }>
                            <div slot="body">
                                Enabled
                            </div>
                        </Switch>
                    </div>
                </div>
            </div>
        </Form>

        <Form class="mt-3" id="idk_form">
            <div slot="body">
                <div class="row">
                    <div class="col">Identity-Key ( <b>IDK</b> )</div>
                </div>

                <div class="row p-2 mt-2">
                    <div class="col">
                        <button type="button" class="btn btn-primary" value="generate" on:click={ generateIDK }>
                            <i class="fa-solid fa-dice"></i>
                            <span class="ms-2">
                                Generate
                            </span>
                        </button>

                        <button type="button" class="btn btn-secondary ms-2" value="import" on:click={ importIDK }>
                            <i class="fa-solid fa-upload"></i>
                            <span class="ms-2">
                                Import
                            </span>

                            <input type="file" class="input" name="file" accept=".idk" hidden on:change={ (event) => { storeIDK(event); } }>
                        </button>
                    </div>
                    <div class="col d-flex justify-content-end align-items-center">
                        { #if $idk }
                            <div class="eject-idk-box">
                                <button type="button" class="btn btn-danger" value="eject" on:click={ ejectIDK }>
                                    <i class="fa-solid fa-eject"></i>
                                    <span class="ms-2">
                                        Eject
                                    </span>
                                </button>

                                <span class="import-datetime-box ms-3">
                                    Imported at <span class="import-datetime-value ms-2">{ $idk ? Solenoid.DateTime.format( $idk['importTime'] ) : '-' }</span>
                                </span>
                            </div>
                        { /if }
                    </div>
                </div>

                <div class="row p-2 mt-2">
                    <div class="col d-flex justify-content-start align-items-center">
                        <Switch name="idk_authentication" checked={ $user['security']['idk']['authentication'] } bind:api={ idkAuthSwitch }>
                            <div slot="body">
                                IDK Authentication
                            </div>
                        </Switch>
                    </div>
                </div>
            </div>
        </Form>

        <Form class="mt-3" id="view_access_form" bind:api={ viewAccessForm }>
            <div slot="body">
                <div class="row">
                    <div class="col">Access Log</div>
                </div>

                <div class="row p-2 mt-2">
                    <div class="col">
                        <a class="btn btn-primary" href="/admin/access_log" on:click={ () => { changeSecurityModal.close(); } }>
                            <i class="fa-solid fa-list"></i>
                            <span class="ms-2">
                                View
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </Form>
    </div>
</Modal>

<Modal id="idk_download_modal" bind:api={ idkDownloadModal } width="540px">
    <div slot="title">
        IDK
    </div>
    <div slot="body">
        <b>IDK</b> has been generated :: Download it and keep it in a secure place !
        <br>
        <br>

        <button type="button" class="btn btn-primary m-auto d-table" id="download_idk_button" on:click={ downloadIDK }>
            <i class="fa-solid fa-download"></i>
            <span class="ms-2">
                Download
            </span>
        </button>
    </div>
</Modal>

<Modal id="required_action_modal" bind:api={ requiredActionModal } width="640px">
    <div slot="title">
        Required Action
    </div>
    <div slot="body">
        <div class="row">
            <div class="col text-center">
                <b>Password</b> must be set !
            </div>
        </div>

        <div class="row mt-4">
            <div class="col">
                <button type="submit" class="btn btn-primary m-auto d-table" id="set_password_button" on:click={ () => { changeSecurityModal.open(); requiredActionModal.close(); } }>
                    <span>
                        Resolve
                    </span>
                </button>
            </div>
        </div>
    </div>
</Modal>

<Modal id="app_info_modal" bind:api={ appInfoModal } width="640px">
    <div slot="title">
        App Info

        <!-- GitHub Link -->
        <a class="btn btn-link btn-sm ms-2" href="https://github.com/Solenoid-IT/simba" target="_blank" title="GitHub Link">
            <i class="fa-brands fa-github" style="height: 20px; color: #9a9c9e;"></i>
        </a>
    </div>
    <div slot="body">
        <div class="d-flex align-items-center">
            <img class="app-info-logo" src="https://{ Core.envs.BE_HOST }/assets/images/simba.png" alt="">
            <div class="flex-grow-1 m-4">
                <PTable props={ appInfoProps }/>
            </div>
        </div>

        <br>

        <AppHistory history={ appHistory }/>
    </div>
</Modal>

<Modal id="app_new_version_modal" bind:api={ appNewVersionModal } width="640px"
    on:close={ () => { markChangelogAsRead(); } }
>
    <div slot="title">
        NEW VERSION IS AVAILABLE !
    </div>
    <div slot="body">
        <AppHistory history={ appHistory } display="last"/>
    </div>
</Modal>



<!--<div class="modal fade" id="change_profile_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 900px;">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title">
                  Profile
              </h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <div class="modal-body">
              <form class="swg swg-form" id="change_profile_form">
                  <div class="p-2">
                      <div class="row mt-2">
                          <div class="col">
                              <div class="form-floating mb-3">
                                  <input type="text" class="form-control input" name="name" value="{ $user['profile']['name'] }" placeholder="name">
                                  <label>Name</label>
                              </div>
                          </div>
                          <div class="col">
                              <div class="form-floating mb-3">
                                  <input type="text" class="form-control input" name="surname" value="{ $user['profile']['surname'] }" placeholder="surname">
                                  <label>Surname</label>
                              </div>
                          </div>
                          <div class="col" style="position: relative;">
                              <div class="form-floating mb-3">
                                  <select class="form-control input" name="photo_action">
                                      <option value=""></option>
                                      <option disabled></option>
  
                                      <option value="change">
                                          Change
                                      </option>
                                      <option value="remove">
                                          Remove
                                      </option>
                                  </select>
  
                                  <label>Photo</label>
  
                                  <input type="file" class="form-control input" name="photo" placeholder="photo" hidden>
                              </div>
  
                              <img class="profile-image" src="{ $user['profile']['photo'] ? `data:${ $user['profile']['photo']['type'] };base64,${ $user['profile']['photo']['content'] }` : '/assets/images/user.png' }" alt="" style="margin: 10px; margin-right: 20px; position: absolute; top: 0; right: 0;">
                          </div>
                      </div>
  
                      <div class="row mt-3">
                          <div class="col">
                              <button type="submit" class="btn btn-primary m-auto d-table">
                                  Save
                              </button>
                          </div>
                      </div>
                  </div>
              </form>
  
              <form class="swg swg-form mt-3" id="change_username_form">
                  <div class="row">
                      <div class="col">Username</div>
                  </div>
  
                  <div class="row p-2 mt-2">
                      <div class="col">
                          <input type="text" class="form-control input" name="username" value="{ $user['username'] }" placeholder="username" data-required>
                      </div>
                      <div class="col flex-grow-0">
                          <button type="submit" class="btn btn-primary m-auto d-table">
                              Save
                          </button>
                      </div>
                  </div>
              </form>
  
              <form class="swg swg-form mt-3" id="change_email_form">
                  <div class="row">
                      <div class="col">Email</div>
                  </div>
  
                  <div class="row p-2 mt-2">
                      <div class="col">
                          <input type="text" class="form-control input" name="email" value="{ $user['email'] }" placeholder="email" data-required>
                      </div>
                      <div class="col flex-grow-0">
                          <button type="submit" class="btn btn-primary m-auto d-table">
                              Save
                          </button>
                      </div>
                  </div>
              </form>
          </div>
      </div>
    </div>
</div>-->




<div class="modal fade" id="set_password_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 540px;">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">
                Required Action
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col text-center">
                    <b>Password</b> must be set !
                </div>
            </div>

            <div class="row mt-4">
                <div class="col">
                    <button type="submit" class="btn btn-primary m-auto d-table" id="set_password_button">
                        <span>
                            Set
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>



<style>

    .brand-logo
    {
        height: 30px;
    }



    :global( .import-datetime-box )
    {
        white-space: nowrap;
    }



    @media screen and (max-width: 600px)
    {
        :global( #idk_form .btn[value="import"] )
        {
            margin: 10px 0;
        }

        :global( .import-datetime-box )
        {
            margin: 0 !important;
        }
    }



    .app-info-logo
    {
        width: 100px;
        height: 100px;
        border-radius: 4px;
    }

</style>