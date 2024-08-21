<script>

	import { Core } from '../../../lib/solenoid/solenoid.core.js';

	import { browser } from '$app/environment';

	import App from '../../../views/App.svelte';
	import Base from '../../../views/components/Base.svelte';

	import Table from '../../../views/components/Table.svelte';

	import { user } from '../../../stores/user.js';
	import { requiredActions } from '../../../stores/requiredActions.js';

	import { activityStack } from '../../../stores/activityStack.js';



	let resourceType     = 'resource';
	let resourceName     = 'Access Log';
	let resourcePageName = 'Access Log';
	let resourceRecords  = [];



	let resourceTableColumns = [];
	let resourceTableRecords = [];



	let resourceTable = null;



	// Returns [Promise:bool]
	async function fetchData ()
	{
		// (Getting the value)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build( '/admin/access_log' ),
			'RPC',
			[
				'Action: fetch_data',
				'Content-Type: application/json'
			],
			'',
			'json',
			true
		)
		;

		if ( response.status.code !== 200 )
		{// Match failed
			if ( response.status.code === 401 )
			{// (User is not authorized)
				// (Opening the URL)
				Core.URL.open( '/admin/login' );

				// Returning the value
				return false;
			}



			// (Alerting the value)
			alert( response.body['error']['message'] );

			// Returning the value
			return false;
		}



		// (Getting the values)
		$user            = response.body['user'];
		$requiredActions = response.body['required_actions'];

		resourceRecords  = response.body['records'];



		// (Getting the values)
		resourceTableColumns =
		[
			{
				'id':    'datetime.insert',
				'label': 'DateTime',

				'hidden': false,

				'fixed': true
			},

			{
				'id':    'ip',
				'label': 'IP',

				'hidden': false,

				'fixed': true
			},

			{
				'id':    'browser',
				'label': 'Browser',

				'hidden': false,

				'fixed': true
			},

			{
				'id':     'os',
				'label':  'OS',

				'hidden': false,

				'fixed': true
			},
			
			{
				'id':     'hw',
				'label':  'HW',

				'hidden': false,

				'fixed': true
			},

			{
				'id':     'login_method',
				'label':  'Login Method',

				'hidden': false,

				'fixed': true
			},

			{
				'id':     'session',
				'label':  'Session',

				'hidden': false,

				'fixed': true
			}
		]
		;



		// (Setting the value)
		let records = [];

		for (const resource of resourceRecords)
		{// Processing each entry
			// (Getting the value)
			let record =
			{
				'hidden':         false,

				'selectable':     resource['session'] && !resource['current_session'] ? true : false,
				'selected':       false,

				'key':            resource['session'],

				'values':
				{
					'datetime.insert':
					{
						'text':
						`
							<span title="${ resource['datetime']['insert'] }">${ Solenoid.DateTime.localize( resource['datetime']['insert'] ) }</span>
						`.trim(),
						'value': Solenoid.DateTime.localize( resource['datetime']['insert'] )
					},

					'ip':
					{
						'text':
						`
							<div class="d-flex align-items-center">
								<span class="ms-2 me-2">
									${ resource['ip']['address'] }
								</span>
									•
								<span class="ms-2 me-2">
									${ resource['ip']['country']['code'] }
								</span>
									•
								<span class="ms-2 me-2">
									${ resource['ip']['country']['name'] }
								</span>
									•
								<span class="ms-2 me-2">
									<img class="country-flag" src="https://flagsapi.com/${ resource['ip']['country']['code'] }/flat/64.png" alt="">
								</span>
									•
								<span class="ms-2 me-2">
									${ resource['ip']['isp'] }
								</span>
							</div>
						`.trim(),
						'value': `${ resource['ip']['address'] } • ${ resource['ip']['country']['code'] } • ${ resource['ip']['country']['name'] } • ${ resource['ip']['country']['name'] } • ${ resource['ip']['isp'] }`
					},

					'browser':
					{
						'text':  resource['browser'],
						'value': null
					},

					'os':
					{
						'text':  resource['os'],
						'value': null
					},

					'hw':
					{
						'text':  resource['hw'],
						'value': null
					},

					'login_method':
					{
						'text':  resource['login_method'],
						'value': null
					},

					'session':
					{
						'text':
							resource['session']
								?
								(
									resource['current_session']
										?
										`
											<i class="fa-solid fa-circle" style="color: #b57f00;"></i>
											<span class="ms-2" style="margin-bottom: 2px;">
												current
											</span>
										`.trim()
										:
										
										`
											<i class="fa-solid fa-circle" style="color: #49b500;"></i>
											<span class="ms-2" style="margin-bottom: 2px;">
												active
											</span>
										`.trim()
								)
								:
							`
								<i class="fa-solid fa-circle" style="color: #a0a0a0;"></i>
								<span class="ms-2" style="margin-bottom: 2px;">
									disconnected
								</span>
							`.trim()
						,
						'value': resource['session'] ? ( resource['current_session'] ? 'current' : 'active' ) : 'disconnected'
					}
				},

				'data':
				{
					'session_id': resource['session'],
					'ip_address': resource['ip']['address']
				},

				'controls':
					`
						<button class="btn btn-danger input ms-2" value="session::remove" title="disconnect client" ${ resource['session'] && !resource['current_session'] ? '' : 'disabled' }>
							<i class="fa-solid fa-right-from-bracket"></i>
							<span class="btn-label ms-1">
								Disconnect Client
							</span>
						</button>
					`
			}
			;



			// (Appending the value)
			records.push( record );
		}



		// (Getting the value)
		resourceTableRecords = records;



		// Returning the value
		return true;
	}



	// Returns [Promise:bool]
	async function emptyLog ()
	{
		if ( !confirm('Are you sure to empty the log ?') ) return;



		// (Getting the value)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build( '/admin/access_log' ),
			'RPC',
			[
				'Action: empty',
				'Content-Type: application/json'
			],
			'',
			'json',
			true
		)
		;

		if ( response.status.code !== 200 )
		{// Match failed
			if ( response.status.code === 401 )
			{// (User is not authorized)
				// (Opening the URL)
				Core.URL.open( '/admin/login' );

				// Returning the value
				return false;
			}



			// (Alerting the value)
			alert( response.body['error']['message'] );

			// Returning the value
			return false;
		}



		// (Opening the URL)
		Core.URL.open('');



		// Returning the value
		return true;
	}



	const Session =
	{
		// Returns [Promise:bool]
		'remove': async function (list)
		{
			// (Getting the value)
			const response = await Solenoid.HTTP.sendRequest
			(
				Core.URL.build( '/admin/user' ),
				'RPC',
				[
					'Action: session::remove',
					'Content-Type: application/json'
				],
				JSON.stringify
				(
					{
						'list': list
					}
				),
				'json',
				true
			)
			;

			if ( response.status.code !== 200 )
			{// Match failed
				if ( response.status.code === 401 )
				{// (User is not authorized)
					// (Opening the URL)
					Core.URL.open( '/admin/login' );

					// Returning the value
					return false;
				}



				// (Alerting the value)
				alert( response.body['error']['message'] );

				// Returning the value
				return false;
			}



			// (Pushing the activity)
			activityStack.push
			(
				{
					'title':   `<a href="${ window.location.pathname }" title="${ Solenoid.DateTime.format() }">${ resourcePageName }</a>`,
					'message':
						`
							Clients have been disconnected
							<ul>
								${ list.map( function (id) { return '<li><b>' + resourceTable.getRecordById(id).data['ip_address'] + '</b> ( ' + resourceTable.getRecordValue( id, 'datetime.insert' ) + ' )' + '</li>'; } ).join(' ') }
							</ul>
						`
				}
			)
			;

			// (Getting the value)
			$activityStack = $activityStack;



			// (Fetching the data)
			await fetchData();



			// Returning the value
			return true;
		}
	}
	;



	// Returns [Promise]
	async function onTableEntryAction ( { detail: event } )
	{
		switch ( event.value )
		{
			case 'session::remove':
				if ( !confirm('Are you sure to disconnect this client ?') ) return;



				// (Removing the session)
				await Session.remove( [ resourceTable.getRecord( event.index ).data['session_id'] ] );
			break;
		}
	}

	// Returns [Promise]
	async function onTableSelectionAction ( { detail: event } )
	{
		switch ( event.value )
		{
			case 'session::remove':
				if ( !confirm('Are you sure to disconnect these clients ?') ) return;



				// (Removing the session)
				await Session.remove( event.entries.map( function (entry) { return entry.data['session_id']; } ) );
			break;
		}
	}



	$:
		if ( browser )
		{// Value is true
			// (Fething the data)
			fetchData();
		}

</script>

<svelte:head>
	<title>{ resourcePageName } ( { resourceRecords.length } )</title>
</svelte:head>

<App>
	<div slot="body">
		{ #if $user }
			<Base>
				<div slot="body">
    				<!-- svelte-ignore a11y-missing-content -->
    				<h1 class="mt-4 mb-4"></h1>

					<div class="card mb-4">
						<div class="card-header">
							<div class="row">
								<div class="col d-flex align-items-center">
									<i class="fas fa-table me-3"></i>
									{ resourcePageName } ( { resourceRecords.length } )
								</div>
								<div class="col d-flex justify-content-end">
									{ #if $user['hierarchy'] === 1 }
										<button class="btn btn-danger ms-2" id="empty_log_button" title="empty log" on:click={ emptyLog }>
											<i class="fa-solid fa-trash"></i>
											<span class="btn-label ms-1">
												Empty
											</span>
										</button>
									{ /if }
								</div>
							</div>
						</div>
						<div class="card-body">
							<Table id="resource_table"
								bind:api={ resourceTable }

								columns={ resourceTableColumns }
								records={ resourceTableRecords }

								selectable={ true }

								on:entry.action={ onTableEntryAction }
								on:selection.action={ onTableSelectionAction }
							>
								<div slot="selection-controls-box">
									<button class="btn btn-danger input ms-2" value="session::remove" title="disconnect client">
										<i class="fa-solid fa-right-from-bracket"></i>
										<span class="btn-label ms-1">
											Disconnect Client
										</span>
									</button>
								</div>
							</Table>
						</div>
					</div>
				</div>
			</Base>
		{ /if }
	</div>
</App>