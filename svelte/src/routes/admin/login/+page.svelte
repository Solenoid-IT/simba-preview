<script>

	import { Core } from '../../../lib/solenoid/solenoid.core.js';

	import { browser } from '$app/environment';

	import { pendingAction } from '../../../stores/pendingAction.js';
	import { idk } from '../../../stores/idk.js';

	import App from '../../../views/App.svelte';

	import Form from '../../../views/components/Form.svelte';
	import PasswordField from '../../../views/components/PasswordField.svelte';

	import Modal from '../../../views/components/Modal.svelte';



	// (Setting the value)
	let loginForm = null;

	// Returns [Promise:void]
	async function onLoginFormSubmit ()
	{
		if ( Object.keys( loginForm.validate(true) ).length > 0 ) return;



		// (Getting the value)
		const input = await loginForm.getInput();



		// (Sending an http request)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build( '/admin/user' ),
			'RPC',
			[
				'Action: user::login',
				'Content-Type: application/json'
			],
			JSON.stringify( input ),
			'json',
			true
		)
		;

		if ( response.status.code !== 200 )
		{// Match failed
			if ( response.status.code === 401 )
			{// (User is not authorized)
				// (Alerting the value)
				alert('Login failed');

				// Returning the value
				return false;
			}



			// (Alerting the value)
			alert( response.body['error']['message'] );

			// Returning the value
			return false;
		}



		if ( response.body && response.body['mfa'] )
		{// Value found
			// (Creating a connection)
			const connection = new Solenoid.SSE.Connection( Core.URL.build( '/admin/authorization' ), null, true );

			// (Listening for the event)
			connection.addEventListener('close', function (event) {
				// (Closing the connection)
				connection.close();



				// (Setting the value)
				$pendingAction = null;



				// (Moving to the URL)
				Core.navigate( JSON.parse(event.data)['location'] );
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
		else
		{// Value not found
			// (Moving to the URL)
			Core.navigate( response.body['location'] );
		}



		// Returning the value
		return true;
	}
	
	

	// Returns [Promise:bool]
	async function loginWithIDK ()
	{
		// (Getting the value)
		const input =
		{
			'idk': $idk['content']
		}
		;



		// (Sending an http request)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build( '/admin/user' ),
			'RPC',
			[
				'Action: user::login',
				'Content-Type: application/json'
			],
			JSON.stringify( input ),
			'json',
			true
		)
		;

		if ( response.status.code !== 200 )
		{// Match failed
			if ( response.status.code === 401 )
			{// (User is not authorized)
				// (Alerting the value)
				alert('Login failed');

				// Returning the value
				return false;
			}



			// (Alerting the value)
			alert( response.body['error']['message'] );

			// Returning the value
			return false;
		}



		// (Moving to the URL)
		Core.navigate( response.body['location'] );



		// Returning the value
		return true;
	}

	// Returns [void]
	function ejectIDK ()
	{
		if ( !confirm('Are you sure to eject the IDK ?') ) return;



		// (Setting the value)
		$idk = null;

		// (Clearing the local storage)
		Solenoid.LocalStorage.clear( 'idk' );
	}

	// Returns [void]
	function importIDK (event)
	{
		// (Triggering the event)
		event.target.closest('div').querySelector('.input[name="file"]').click();
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



	// (Setting the value)
	let recoverAccountForm = null;

	// Returns [Promise:bool]
	async function onRecoverAccountFormSubmit ()
	{
		if ( Object.keys( recoverAccountForm.validate(true) ).length > 0 ) return;



		// (Getting the value)
		const input = await recoverAccountForm.getInput();



		// (Sending an http request)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build( '/admin/user' ),
			'RPC',
			[
				'Action: account::recover',
				'Content-Type: application/json'
			],
			JSON.stringify( input ),
			'json',
			true
		)
		;

		if ( response.status.code !== 200 )
		{// Match failed
			// (Alerting the value)
			alert( response.body['error']['message'] );

			// Returning the value
			return false;
		}



		// (Alerting the value)
		alert('Confirm operation via email');



		// Returning the value
		return true;
	}



	// (Setting the value)
	let recoverAccountModal = null;



	$:
		if ( browser )
		{// Value is true
			// (Getting the value)
			$idk = Solenoid.LocalStorage.read( 'idk' );
		}

</script>

<svelte:head>
	<title>Login</title>
</svelte:head>

<App>
	<div slot="body">
		<div class="card-box">
			<div class="card shadow-lg border-0 rounded-lg h-auto">
				<div class="card-header">
					<h3 class="text-center font-weight-light my-4">Login</h3>
				</div>
				<div class="card-body">
					<Form id="login_form" bind:api={ loginForm } on:submit={ onLoginFormSubmit }>
						<div slot="body">
							<div class="std-login-box">
								<div class="row mt-3">
									<div class="col">
										<input type="text" class="form-control input" name="username" placeholder="username" data-required>
									</div>
								</div>

								<div class="row mt-3">
									<div class="col">
										<PasswordField name="password" placeholder="password" required=true/>
									</div>
								</div>

								<div class="form-check mb-3">
									<label class="form-check-label">
										<input type="checkbox" class="form-check-input input" name="remember">
										Remember
									</label>
								</div>
							</div>

							<div class="d-flex align-items-center justify-content-center mt-4 mb-0">
								<!--<a class="small" href="password.html">Forgot Password?</a>-->
								<button type="submit" class="btn btn-primary" title="login" style="width: 100%;">
									<i class="fa-solid fa-right-to-bracket"></i>
								</button>
							</div>
						</div>
					</Form>
				</div>
				<div class="card-footer text-center py-3">
					<div class="d-flex justify-content-center">
						<div class="small me-5">
							{ #if $idk }
								<!-- svelte-ignore a11y-click-events-have-key-events -->
								<a class="btn btn-primary me-2" id="login_with_idk_button" style="width: 40px;" title="login with IDK" on:click={ loginWithIDK }>
									<i class="fa-solid fa-right-to-bracket"></i>
								</a>

								<!-- svelte-ignore a11y-click-events-have-key-events -->
								<a class="btn btn-danger" id="eject_idk_button" style="width: 40px;" title="eject IDK" on:click={ ejectIDK }>
									<i class="fa-solid fa-eject"></i>
								</a>
							{ :else }
								<!-- svelte-ignore a11y-click-events-have-key-events -->
								<a class="btn btn-primary" id="import_idk_button" style="width: 40px;" title="import IDK" on:click={ (event) => { importIDK( event ); } }>
									<i class="fa-solid fa-upload"></i>
								</a>

								<input type="file" class="input" name="file" accept=".idk" hidden on:change={ (event) => { storeIDK( event ); } }>
							{ /if }
						</div>

						<!-- svelte-ignore a11y-missing-attribute -->
						<!-- svelte-ignore a11y-click-events-have-key-events -->
						<a class="btn btn-danger" title="recover account" style="width: 40px;" on:click={ () => { recoverAccountModal.open(); } }>
							<i class="fa-solid fa-lock-open"></i>
						</a>
					</div>
				</div>
			</div>
		</div>

		<Modal id="recover_account_modal" bind:api={ recoverAccountModal }>
			<div slot="title">
				Recover Account
			</div>
			<div slot="body">
				<Form id="recover_account_form" bind:api={ recoverAccountForm } on:submit={ onRecoverAccountFormSubmit }>
					<div slot="body">
						<div class="std-login-box">
							<div class="row mt-3">
								<div class="col">
									<input type="text" class="form-control input" name="email" placeholder="email" data-required>
								</div>
							</div>
						</div>

						<div class="d-flex align-items-center justify-content-center mt-4 mb-0">
							<button type="submit" class="btn btn-primary">
								Recover
							</button>
						</div>
					</div>
				</Form>
			</div>
		</Modal>
	</div>
</App>

<style>

	.card-footer .btn
	{
		font-size: 12px;
	}



	.card
	{
		width: 460px;
		margin: 0 auto;
	}

	@media screen and (max-width: 600px)
	{
		.card
		{
			width: 90%;
		}
	}



	.card-box
	{
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
	}

</style>