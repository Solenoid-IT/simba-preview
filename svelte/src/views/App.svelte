<script context="module">

    let appLoaded = false;

    let interval = null;

</script>

<script>

    import { Core } from '../lib/solenoid/solenoid.core.js';

    import { onMount, onDestroy } from 'svelte';

    import { browser } from '$app/environment';

    import { page } from '$app/stores';

    import { pendingRequest } from '../stores/pendingRequest.js';
    import { pendingAction } from '../stores/pendingAction.js';

    import { idk } from '../stores/idk.js';

    import ActivityBar from './components/ActivityBar.svelte';
    import Spinner from './components/Spinner.svelte';

    import '../assets/styles/custom.css';



    // (Getting the value)
    let loadApp = !appLoaded;

    // (Setting the value)
    appLoaded = true;



    // (Setting the value)
    let spinner = null;

    $:
        if ( spinner )
        {// Value found
            if ( $pendingAction )
            {// Value found
                // (Starting the spinner)
                spinner.start( $pendingAction['message'], $pendingAction['duration'] );
            }
            else
            {// Value not found
                // (Stopping the spinner)
                spinner.stop();
            }
        }
    
    

    // (Setting the value)
    let layoutMounted = false;

    // (Listening for the event)
    onMount
    (
        function ()
        {
            // (Setting the value)
            layoutMounted = true;
        }
    )
    ;

    // (Listening for the event)
    onDestroy
    (
        function ()
        {
            // (Setting the value)
            layoutMounted = false;
        }
    )
    ;



    // Returns [Promise:bool]
    async function verifySession ()
	{
		// (Getting the value)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build( '/admin/user' ),
			'RPC',
			[
				'Action: session::validate',
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
            {// Match OK
                // (Moving to the URL)
                Solenoid.URL.move('/admin/login');

                // Returning the value
                return true;
            }



			// (Alerting the value)
			alert( response.body['error']['message'] );

			// Returning the value
			return false;
		}



		// Returning the value
		return true;
	}



    export let ready = false;



    let drop = false;



    $:
        if ( loadApp )
        {// Value is true
            // (Setting the value)
            loadApp = false;

            if ( typeof window !== 'undefined' )
            {// Value found
                // (Getting the value)
                window.jq = jQuery;



                // (Setting the value)
                let currentActiveElement = null;

                // (Listening for the events)
                Solenoid.HTTP.addEventListener('start', function () {
                    // (Getting the value)
                    currentActiveElement = document.activeElement;

                    // (Getting the value)
                    currentActiveElement.blur();



                    // (Setting the value)
                    $pendingRequest = true;
                });

                Solenoid.HTTP.addEventListener('end', function () {
                    if ( currentActiveElement !== null )
                    {// Value found
                        // (Getting the value)
                        currentActiveElement.focus();
                    }



                    // (Setting the value)
                    $pendingRequest = false;
                });

                Solenoid.HTTP.addEventListener('enqueued-request', function (unprocessableRequest) {
                    console.debug(unprocessableRequest);
                });



                // (Getting the value)
                Solenoid.HTTP.debug = Core.envs.BE_TYPE === 'dev' ? 'RPC' : false;



                if ( Core.envs.ENV_TYPE === 'dev' )
                {// Match OK
                    // (Setting the value)
                    Solenoid.HTTP.defaultHeaders['Dev-Sid'] = Core.envs.DEV_SID;
                }



                // (Listening for the events)
                jq(window).on('dragover', function (event) {
                    // (Preventing the default)
                    event.preventDefault();

                    // (Setting the value)
                    drop = true;
                });

                jq(window).on('drop', async function (event) {
                    // (Preventing the default)
                    event.preventDefault();

                    // (Stopping the propagation)
                    event.stopImmediatePropagation();



                    // (Setting the value)
                    drop = false;



                    if ( event.originalEvent.dataTransfer.files.length === 0 ) return;



                    // (Getting the value)
                    const file = event.originalEvent.dataTransfer.files[0];

                    if ( file.name.split('.').at(-1) !== 'idk' ) return;



                    if ( !confirm('Are you sure to import the IDK ?') ) return;



                    // (Getting the value)
                    $idk =
                    {
                        'content':    await Solenoid.File.read( file ),
                        'importTime': Solenoid.DateTime.fetchCurrentTimestamp()
                    }
                    ;

                    // (Writing to the local storage)
                    Solenoid.LocalStorage.write( 'idk', $idk );



                    // (Alerting the value)
                    alert('IDK has been imported');
                });



                // (Getting the value)
                const cookieDomain = `.${ Core.envs.APP_ID }`;

                // (Setting the cookies)
                document.cookie = `route=${ encodeURI( window.location.pathname + window.location.search ) };Path=/;Domain=${ cookieDomain }`;
                document.cookie = `timezone=${ encodeURI( Solenoid.DateTime.getCurrentTimeZone() ) };Path=/;Domain=${ cookieDomain }`;
                document.cookie = `language=${ encodeURI( navigator.language ) }; Path=/;Domain=${ cookieDomain }`;



                // (Logging the value)
                console.log( `APP ${ Core.envs.APP_ID } :: ${ Core.envs.APP_NAME }/${ Core.envs.APP_VERSION } -> READY` );



                // (Setting the value)
                ready = true;



                // (Calling the function)
                onAppReady();
            }
        }
    
    

    // Returns [Promise]
    async function onAppReady ()
    {
        // (Doing nothing)
    }



    // Returns [void]
    function onRouteChange (route)
    {
        if ( /^\/admin/.test( route ) )
        {// (Path starts with '/admin')
            if ( route !== '/admin/login' )
            {// Match OK
                // (Clearing the interval)
                clearInterval( interval );

                // (Setting the interval)
                interval = setInterval
                (
                    function ()
                    {
                        // (Verifying the session)
                        verifySession();
                    },
                    5 * 60 * 1000// '5 min'
                )
                ;
            }
        }
    }

    $:
        if ( browser )
        {// Value found
            if ( $page )
            {// Value is true
                // (Calling the function)
                onRouteChange( $page.route.id );
            }
        }
    ;

</script>

<main>
    <Spinner bind:api={ spinner }/>

    { #if $pendingRequest }
        <ActivityBar/>
    { /if }

    <slot name="body"/>



    { #if drop }
        <div class="dropzone"></div>
    { /if }
</main>

<style>

    .dropzone
    {
        margin: 10px;
        position: fixed;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        outline: 10px solid #0d6efd66;
    }

</style>