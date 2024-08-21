<script>

	import { Core } from '../lib/solenoid/solenoid.core.js';

	import { browser } from '$app/environment';

	import App from '../views/App.svelte';
	import Table from '../views/components/Table.svelte';



	let resourceType    = 'resource';
	let resourceRecords = [];



	let resourceTableColumns = [];
	let resourceTableRecords = [];



	let resourceTable = null;



	let dir = new URL( document.location ).searchParams.get('d') ?? '/';



	// Returns [Promise:bool]
	async function fetchData ()
	{
		// (Getting the value)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build('/docs'),
			'RPC',
			[
				'Action: list_by_dir',
				'Content-Type: application/json'
			],
			JSON.stringify
			(
				{
					'dir': dir
				}
			),
			'json',
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



		// (Getting the value)
		resourceRecords = response.body['records'];



		// (Getting the values)
		resourceTableColumns =
		[
			{
				'id':    'basename',
				'label': 'Name',

				'hidden': false
			},

			{
				'id':    'datetime.last_update',
				'label': 'Last Update',

				'hidden': false
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

				'selectable':     false,
				'selected':       false,

				'key':            resource['path'],

				'values':
				{
					'basename':
					{
						'text':
						resource['type'] === 'file'
							?
						`
							<i class="fa-solid fa-file-lines me-3"></i>
							${ resource['basename'] }
						`.trim()
							:
						`
							<i class="fa-solid fa-folder me-3"></i>
							${ resource['basename'] }
						`.trim(),
						'value': resource['basename']
					},

					'datetime.last_update':
					{
						'text':
						`
							<span title="${ resource['datetime']['last_update'] }">${ Solenoid.DateTime.localize( resource['datetime']['last_update'] ) }</span>
						`.trim(),
						'value': Solenoid.DateTime.localize( resource['datetime']['last_update'] )
					}
				},

				'data': {},

				'controls':
					resource['type'] === 'file'
						?
					`
						<a class="btn btn-primary input ms-2" href="https://${ Core.envs.BE_HOST }${ resource['path'] }" target="_blank" title="open" rel="external">
							<i class="fa-solid fa-arrow-up-right-from-square"></i>
							<span class="btn-label ms-1">
								Open
							</span>
						</a>
					`
						:
					`
						<button class="btn btn-primary input ms-2" value="dir::open" title="open">
							<i class="fa-solid fa-arrow-up-right-from-square"></i>
							<span class="btn-label ms-1">
								Open
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



	// Returns [Promise]
	async function onTableEntryAction ( { detail: event } )
	{
		switch ( event.value )
		{
			case 'dir::open':
				// (Setting the value)
				dir = event.id.replace( /^\/docs/, '' );
			break;
		}
	}



	// Returns [string]
	function getParentDir (value)
	{
		// (Getting the value)
		let d = value.split('/');

		// (Popping the array)
		d.pop();

		// (Getting the value)
		d = d.join('/');



		if ( d === '' ) d = '/';



		// Returning the value
		return d;
	}



	// Returns [void]
	async function onDirChange (value)
	{
		if ( dir === '' )
		{// Value found
			// (Setting the value)
			dir = '/';
		}



		// (Fetching the data)
		await fetchData();



		// (Setting the value)
		//window.location.search = `?d=${ dir }`;
	}

	$:
		onDirChange(dir);

</script>

<svelte:head>
	<title>Home</title>
</svelte:head>

<App>
	<div slot="body">
		<a class="admin-controls" href="/admin" title="go to admin">
			<i class="fa-solid fa-user"></i>
		</a>

		<div class="card-box">
			<div class="card mb-4">
				<div class="card-header">
					<div class="row">
						<div class="col d-flex align-items-center" style="flex-basis: 100%;">
							<i class="fas fa-table me-3"></i>
							Docs

							<div class="input-group">
								<input type="text" class="form-control ms-5 input" name="dir" bind:value={ dir }>
								<div class="input-group-append d-flex">
									<button class="btn btn-primary" style="width: 50px;" title="go to parent" disabled={ dir === '/' ? 'true' : null }
										on:click={ () => { dir = getParentDir(dir); } }
									>
										<i class="fa-solid fa-up-long"></i>
									</button>
								</div>
							</div>

							<a class="btn btn-primary ms-3" href="/docs" title="compact view">
								<i class="fa-solid fa-list"></i>
							</a>
						</div>
						<div class="col d-flex justify-content-end">
							
						</div>
					</div>
				</div>
				<div class="card-body">
					<Table id="resource_table"
						bind:api={ resourceTable }

						columns={ resourceTableColumns }
						records={ resourceTableRecords }

						selectable={ false }

						on:entry.action={ onTableEntryAction }
					/>
				</div>
			</div>
		</div>
	</div>
</App>