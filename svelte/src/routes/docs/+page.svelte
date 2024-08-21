<script>

	import { Core } from '../../lib/solenoid/solenoid.core.js';

	import { browser } from '$app/environment';

	import App from '../../views/App.svelte';
	import Table from '../../views/components/Table.svelte';



	let resourceType     = 'resource';
	let resourcePageName = 'Docs';
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
			Core.URL.build('/docs'),
			'RPC',
			[
				'Action: list',
				'Content-Type: application/json'
			],
			'',
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
				return false;
			}



			// (Alerting the value)
			alert( response.body['error']['message'] );

			// Returning the value
			return false;
		}



		// (Getting the values)
		resourceRecords = response.body['records'];



		// (Getting the values)
		resourceTableColumns =
		[
			{
				'id':    'path',
				'label': 'Path',

				'hidden': false,

				'fixed': true
			},

			{
				'id':    'title',
				'label': 'Title',

				'hidden': false,

				'fixed': false
			},

			{
				'id':    'description',
				'label': 'Description',

				'hidden': false,

				'fixed': false
			},
			
			{
				'id':     'tag_list',
				'label':  'Tag List',

				'hidden': false,

				'fixed': false
			},

			{
				'id':    'datetime.last_update',
				'label': 'Last Update',

				'hidden': false,

				'fixed': true
			}
		]
		;



		// (Setting the value)
		let records = [];

		for (const resource of resourceRecords)
		{// Processing each entry
			// (Setting the value)
			let tagValues = [];



			for (const tag of resource['tag_list'])
			{// Processing each entry
				// (Getting the values)
				const [ value, color ] = tag.split('=');

				// (Appending the value)
				tagValues.push( `<span class=\"tag-value ms-2 ml-2\" style=\"border-bottom-color: ${ color };\"><b>${ value }</b></span>` );
			}



			// (Getting the value)
			tagValues = tagValues.join('');






			// (Getting the value)
			let record =
			{
				'hidden':         false,

				'selectable':     false,
				'selected':       false,

				'key':            resource['path'],

				'values':
				{
					'path':
					{
						'text':
						`
							<i class="fa-solid fa-file-lines me-3"></i>
							${ resource['path'] }
						`.trim(),
						'value': resource['path']
					},

					'title':
					{
						'text': resource['title'],
						'value': null
					},

					'description':
					{
						'text': resource['description'],
						'value': null
					},

					'tag_list':
					{
						'text':  tagValues,
						'value': resource['tag_list']
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
					`
						<a class="btn btn-primary input ms-2" href="https://${ Core.envs.BE_HOST }${ resource['path'] }" target="_blank" title="open" rel="external">
							<i class="fa-solid fa-arrow-up-right-from-square"></i>
							<span class="btn-label ms-1">
								Open
							</span>
						</a>
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
		<a class="admin-controls" href="/admin" title="go to admin">
			<i class="fa-solid fa-user"></i>
		</a>

		<div class="card-box">
			<div class="card mb-4">
				<div class="card-header">
					<div class="row">
						<div class="col d-flex align-items-center" style="flex-basis: 100%;">
							<i class="fas fa-table me-3"></i>
							{ resourcePageName } ( { resourceRecords.length } )
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

<style>

	.box
	{
		height: 100vh;
		padding: 50px;
		/*
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		*/
		background-color: #c6c6c6;
	}

	.card
	{
		width: 100%;
		height: 100%;
	}

	:global( #resource_table )
	{
		height: 100%;
	}



	@media screen and (max-width: 800px)
	{
		:global( .compressible )
		{
			display: none !important;
		}
	}

</style>