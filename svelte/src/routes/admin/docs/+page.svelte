<script>

	import { Core } from '../../../lib/solenoid/solenoid.core.js';

	import { browser } from '$app/environment';

	import App from '../../../views/App.svelte';
	import Base from '../../../views/components/Base.svelte';

	import Table from '../../../views/components/Table.svelte';
	import Modal from '../../../views/components/Modal.svelte';
	import Form from '../../../views/components/Form.svelte';
	import TextEditor from '../../../views/components/TextEditor.svelte';

	import { user } from '../../../stores/user.js';
    import { requiredActions } from '../../../stores/requiredActions.js';

	import { activityStack } from '../../../stores/activityStack.js';



	let resourceType     = 'document';
	let resourceName     = 'Document';
	let resourcePageName = 'Documents';
	let resource         = null;
	let resourceRecords  = [];



	let resourceTableColumns = [];
	let resourceTableRecords = [];



	let resourceTable = null;

	let template      = null;



	// Returns [Promise:bool]
	async function fetchData ()
	{
		// (Getting the value)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build( '/admin/docs' ),
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
		template         = atob( response.body['template'] );



		// (Getting the values)
		resourceTableColumns =
		[
			{
				'id':    'id',
				'label': 'ID',

				'hidden': true,

				'fixed': true
			},

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

				'fixed': true
			},

			{
				'id':     'description',
				'label':  'Description',

				'hidden': false,

				'fixed': true
			},
			
			{
				'id':     'tag_list',
				'label':  'Tag List',

				'hidden': false,

				'fixed': true
			},

			{
				'id':     'datetime.insert',
				'label':  'Insert DateTime',

				'hidden': false,

				'fixed': true
			},

			{
				'id':     'datetime.update',
				'label':  'Update DateTime',

				'hidden': false,

				'fixed': true
			},

			{
				'id':     'owner',
				'label':  'Owner',

				'hidden': false,

				'fixed': true
			},

			{
				'id':     'active',
				'label':  'Active',

				'hidden': false,

				'fixed': true
			},

			{
				'id':     'sitemap',
				'label':  'Sitemap',

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
			const rootControls =
				`
					<button class="btn btn-warning input ms-2" value="edit" title="edit">
						<i class="fa-solid fa-pen"></i>
						<span class="btn-label ms-1">
							Edit
						</span>
					</button>
					<button class="btn btn-danger input ms-2" value="delete" title="delete">
						<i class="fa-solid fa-trash"></i>
						<span class="btn-label ms-1">
							Delete
						</span>
					</button>
				`
			;



			// (Getting the value)
			let record =
			{
				'hidden':        false,

				'selectable':    true,
				'selected':      false,

				'key':           resource['id'],

				'values':
				{
					'id':
					{
						'text':  resource['id'],
						'value': null
					},

					'path':
					{
						'text':  resource['path'],
						'value': null
					},

					'title':
					{
						'text':  resource['title'],
						'value': null
					},

					'description':
					{
						'text':  resource['description'],
						'value': null
					},

					'tag_list':
					{
						'text':  tagValues,
						'value': resource['tag_list']
					},

					'datetime.insert':
					{
						'text':
						`
							<span title="${ resource['datetime']['insert'] }">${ Solenoid.DateTime.localize( resource['datetime']['insert'] ) }</span>
						`.trim(),
						'value': Solenoid.DateTime.localize( resource['datetime']['insert'] )
					},

					'datetime.update':
					{
						'text':
						`
							<span title="${ resource['datetime']['update'] }">${ Solenoid.DateTime.localize( resource['datetime']['update'] ) }</span>
						`.trim(),
						'value': Solenoid.DateTime.localize( resource['datetime']['update'] )
					},

					'owner':
					{
						'text':
						`
							<span title="${ resource['owner']['name'] } ${ resource['owner']['surname'] } <${ resource['owner']['email'] }>">${ resource['owner']['username'] }</span>
						`.trim(),
						'value': `${ resource['owner']['username'] } -> ${ resource['owner']['name'] } ${ resource['owner']['surname'] } <${ resource['owner']['email'] }>`
					},

					'active':
					{
						'text':
						`
							<input type="checkbox" class="m-auto d-table document-option input" value="active" data-option ${ resource['datetime']['option']['active'] ? 'checked' : '' }>
						`.trim(),
						'value': resource['datetime']['option']['active'] ? 'ON' : 'OFF'
					},

					'sitemap':
					{
						'text':
						`
							<input type="checkbox" class="m-auto d-table document-option input" value="sitemap" data-option ${ resource['datetime']['option']['sitemap'] ? 'checked' : '' }>
						`.trim(),
						'value': resource['datetime']['option']['sitemap'] ? 'ON' : 'OFF'
					},
				},

				'controls':
					`
						<a class="btn btn-success" href="https://${ Core.envs.BE_HOST }/docs${ resource['path'] }" target="_blank" value="view" title="view">
							<i class="fa-solid fa-arrow-up-right-from-square"></i>
							<span class="btn-label ms-1">
								View
							</span>
						</a>

						${ [ 1, 2 ].includes( $user['hierarchy'] ) ? rootControls : '' }
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



	const Resource =
	{
		// Returns [Promise:object|false]
		'find': async function (id)
		{
			// (Getting the value)
			const response = await Solenoid.HTTP.sendRequest
			(
				Core.URL.build( '/admin/docs' ),
				'RPC',
				[
					'Action: find',
					'Content-Type: application/json'
				],
				JSON.stringify
				(
					{
						'key':   'id',
						'value': id
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



			// Returning the value
			return response.body;
		},

		// Returns [Promise:bool]
		'remove': async function (idList)
		{
			// (Getting the value)
			const response = await Solenoid.HTTP.sendRequest
			(
				Core.URL.build( '/admin/docs' ),
				'RPC',
				[
					'Action: unregister',
					'Content-Type: application/json'
				],
				JSON.stringify
				(
					{
						'list': idList
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
							${ resourcePageName } have been removed
							<ul>
								${ idList.map( function (id) { return '<li><b>' + resourceTable.getRecordValue( id, 'path' ) + '</b></li>'; } ).join(' ') }
							</ul>
						`
				}
			)
			;

			// (Getting the value)
			$activityStack = $activityStack;



			// Returning the value
			return true;
		},

		// Returns [Promise:bool]
		'setOption': async function (id, option, value)
		{
			// (Getting the value)
			const response = await Solenoid.HTTP.sendRequest
			(
				Core.URL.build( '/admin/docs' ),
				'RPC',
				[
					'Action: set_option',
					'Content-Type: application/json'
				],
				JSON.stringify
				(
					{
						'id':     id,
						'option': option,
						'value':  value
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



			// Returning the value
			return true;
		},

		// Returns [Promise:bool]
		'set': async function (input)
		{
			// (Sending an http request)
			const response = await Solenoid.HTTP.sendRequest
			(
				Core.URL.build( '/admin/docs' ),
				'RPC',
				[
					`Action: ${ input['id'] ? 'change' : 'register' }`,
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
					'message': `${ resourceName } <b>${ input['path'] }</b> has been ${ input['id'] ? 'changed' : 'created' }`
				}
			)
			;

			// (Getting the value)
			$activityStack = $activityStack;



			// Returning the value
			return true;
		}
	}
	;



	let resourceModal = null;
	
	
	
	let resourceForm = null;

	// Returns [void]
	async function onSubmit ()
	{
		if ( Object.keys( resourceForm.validate(true) ).length > 0 ) return;



		// (Getting the value)
		const input      = await resourceForm.getInput();
		input['content'] = btoa( textEditor.getValue() );



		if ( ! ( await Resource.set( input ) ) ) return;



		// (Fetching the data)
		await fetchData();



		// (Closing the modal)
		resourceModal.close();
	}



	let textEditor = null;

	let documentEditorValue = '';



	// Returns [Promise]
	async function onTableEntryAction ( { detail: event } )
	{
		switch ( event.value )
		{
			case 'edit':
				// (Getting the value)
				resource = await Resource.find( event.id );



				// (Opening the modal)
				resourceModal.open();



				// (Setting the input)
				resourceForm.setInput( resource );



				// (Getting the value)
				documentEditorValue = resource['content'];

				// (Setting the value)
				textEditor.setValue( documentEditorValue );
			break;

			case 'delete':
				if ( !confirm('Are you sure to remove this entry ?') ) return;



				// (Removing the resource)
				await Resource.remove( [ event.id ] );

				// (Removing the record)
				//resourceTable.removeRecord( event.index );



				// (Fetching the data)
				await fetchData();



				// (Closing the modal)
				resourceModal.close();
			break;



			case 'sitemap':
			case 'active':
				// (Setting the option)
				await Resource.setOption( event.id, event.value, event.checked );
			break;
		}
	}

	// Returns [Promise]
	async function onTableSelectionAction ( { detail: event } )
	{
		switch ( event.value )
		{
			case 'delete':
				if ( !confirm('Are you sure to remove the selected entries ?') ) return;



				// (Removing the resources)
				await Resource.remove( event.entries.map( function (entry) { return entry.key; } ) );



				// (Fetching the data)
				await fetchData();



				// (Closing the modal)
				resourceModal.close();
			break;
		}
	}



	// Returns [void]
	function onTextEditorReady ()
	{
		// (Setting the value)
		textEditor.setValue( template );
	}



	// Returns [void]
	function createResource ()
	{
		// (Resetting the form)
		resourceForm.reset();



		// (Opening the modal)
		resourceModal.open();



		// (Getting the value)
		documentEditorValue = template;

		// (Setting the value)
		textEditor.setValue( template );
	}



	$:
		if ( browser )
		{// Value is true
			// (Fetching the data)
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

					<div class="card">
						<div class="card-header">
							<div class="row">
								<div class="col d-flex align-items-center">
									<i class="fas fa-table me-3"></i>
									{ resourcePageName } ( { resourceRecords.length } )
								</div>
								<div class="col d-flex justify-content-end">
									{ #if $user['hierarchy'] <= 2 }
										<button class="btn btn-primary ms-2" id="register_resource_button" title="create" on:click={ createResource }>
											<i class="fa-solid fa-plus"></i>
											<span class="btn-label ms-1">
												Register
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
									<button class="btn btn-danger input ms-2" value="delete" title="delete">
										<i class="fa-solid fa-trash"></i>
										<span class="btn-label ms-1">
											Delete
										</span>
									</button>
								</div>
							</Table>
						</div>
					</div>



					<Modal class="theme-dark" id="resource_modal" bind:api={ resourceModal } width="98%">
						<div slot="title">
							{ resourceName }
						</div>
						<div slot="body">
							<Form id="resource_form" bind:api={ resourceForm } on:submit={ onSubmit }>
								<div slot="body" class="p-2">
									<input type="hidden" class="input" name="id" value="{ resource ? resource['id'] : '' }" data-type="int">

									<div class="row">
										<div class="col">
											<div class="form-floating mb-3">
												<input type="text" class="form-control input" name="path" placeholder="path" data-required>
												<!-- svelte-ignore a11y-label-has-associated-control -->
												<label>Path</label>
											</div>
										</div>
									</div>
			
									<div class="row resp mb-3">
										<div class="col">
											<TextEditor class="document-editor" id="text_editor"
												bind:api={ textEditor }

												settings={ { 'language': 'html' } }

												on:ready={ onTextEditorReady }
											/>
										</div>
										<div class="col">
											<!-- svelte-ignore a11y-missing-attribute -->
											<iframe class="document-preview" srcdoc="{ documentEditorValue }"></iframe>
										</div>
									</div>
					
									<div class="row mt-5">
										<div class="col">
											<button type="submit" class="btn btn-primary m-auto d-table">
												{ resource ? 'Save' : 'Register' }
											</button>
										</div>
									</div>
								</div>
							</Form>
						</div>
					</Modal>
				</div>
			</Base>
		{ /if }
	</div>
</App>

<style>

		:global( #text_editor )
		{
			height: 76vh;
		}



		/*
		
		.document-editor
        {
            width: 100%;
            height: 76vh;
            min-height: 700px;
            border-radius: 4px;
        }

		*/

        .document-preview
        {
            width: 100%;
            height: 100%;
        }

</style>