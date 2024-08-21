<script>

	import { Core } from '../../lib/solenoid/solenoid.core.js';

	import { browser } from '$app/environment';

	import App from '../../views/App.svelte';
	import Base from '../../views/components/Base.svelte';

	import Table from '../../views/components/Table.svelte';

	import { user } from '../../stores/user.js';
	import { requiredActions } from '../../stores/requiredActions.js';



	let resourceName = 'Dashboard';



	let charts   = null;
	let visitors = [];



	// Returns [Promise:bool]
	async function fetchData ()
	{
		// (Getting the value)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build( '/admin/dashboard' ),
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



		// (Getting the values)
		$user            = response.body['user'];
		$requiredActions = response.body['required_actions'];

		charts           = response.body['charts'];
		visitors         = response.body['visitors'];



		// (Getting the values)
		visitorTableColumns =
		[
			{
				'id':    'document',
				'label': 'Document',

				'fixed':  true
			},

			{
				'id':    'ip',
				'label': 'IP',

				'fixed':  false
			},

			{
				'id':    'browser',
				'label': 'Browser',

				'fixed':  false
			},

			{
				'id':     'os',
				'label':  'OS',

				'fixed':  false
			},
			
			{
				'id':     'hw',
				'label':  'HW',

				'fixed':  false
			},

			{
				'id':     'datetime.insert',
				'label':  'Access DateTime',

				'fixed':  true
			}
		]
		;



		// (Setting the value)
		let records = [];

		for (const visitor of visitors)
		{// Processing each entry
			// (Getting the value)
			let record =
			{
				'hidden':   false,

				'selected': false,

				'key':      visitor['datetime']['insert'] + ';' + visitor['document'],

				'values':
				{
					'document':
					{
						'text':  visitor['route'],
						'value': null
					},

					'ip':
					{
						'text':
						`
							<div class="d-flex align-items-center">
								<span class="ms-2 me-2">
									${ visitor['ip']['address'] }
								</span>
									•
								<span class="ms-2 me-2">
									${ visitor['ip']['country']['code'] }
								</span>
									•
								<span class="ms-2 me-2">
									${ visitor['ip']['country']['name'] }
								</span>
									•
								<span class="ms-2 me-2">
									<img class="country-flag" src="https://flagsapi.com/${ visitor['ip']['country']['code'] }/flat/64.png" alt="">
								</span>
									•
								<span class="ms-2 me-2">
									${ visitor['ip']['isp'] }
								</span>
							</div>
						`.trim(),
						'value': `${ visitor['ip']['address'] } • ${ visitor['ip']['country']['code'] } • ${ visitor['ip']['country']['name'] } • ${ visitor['ip']['country']['name'] } • ${ visitor['ip']['isp'] }`
					},

					'browser':
					{
						'text':  visitor['browser'],
						'value': null
					},

					'os':
					{
						'text':  visitor['os'],
						'value': null
					},

					'hw':
					{
						'text':  visitor['hw'],
						'value': null
					},

					'datetime.insert':
					{
						'text':
						`
							<span title="${ visitor['datetime']['insert'] }">${ Solenoid.DateTime.localize( visitor['datetime']['insert'] ) }</span>
						`.trim(),
						'value': Solenoid.DateTime.localize( visitor['datetime']['insert'] )
					}
				}
			}
			;



			// (Appending the value)
			records.push( record );
		}



		// (Getting the value)
		visitorTableRecords = records;



		// Returning the value
		return true;
	}



	let currentMonthVisitorsChartElement = null;
	let currentYearVisitorsChartElement = null;

	$:
		if ( charts )
		{// Value found
			if ( currentYearVisitorsChartElement )
			{// Value found
				// (Creating a Chart)
				const currentYearVisitorsChart = new Chart
				(
					currentYearVisitorsChartElement,
					{
						'type': 'bar',
						'data':
						{
							'labels': charts['current_year_visitors']['labels'],
							'datasets':
							[
								{
									'label': 'Visitors',
									'backgroundColor': "rgba(2,117,216,1)",
									'borderColor': "rgba(2,117,216,1)",
									'data': charts['current_year_visitors']['data'],
								}
							],
						},

						'options':
						{
							'scales':
							{
								'xAxes':
								[
									{
										'time':
										{
											'unit': 'month'
										},

										'gridLines':
										{
											'display': false
										},

										'ticks':
										{
											//'maxTicksLimit': 6
										}
									}
								],

								/*

								'yAxes':
								[
									{
										'ticks':
										{
											'min': 0,
											'max': 15000,
											'maxTicksLimit': 5
										},

										'gridLines':
										{
											'display': true
										}
									}
								]

								*/
							},

							'legend':
							{
								'display': false
							}
						}
					}
				)
				;
			}

			if ( currentMonthVisitorsChartElement )
			{// Value found
				// (Creating a Chart)
				const currentMonthVisitorsChart = new Chart
				(
					currentMonthVisitorsChartElement,
					{
						'type': 'line',

						'data':
						{
							'labels': charts['current_month_visitors']['labels'],
							'datasets':
							[
								{
									'label': "Visitors",
									'lineTension': 0.3,
									'backgroundColor': "rgba(2,117,216,0.2)",
									'borderColor': "rgba(2,117,216,1)",
									'pointRadius': 5,
									'pointBackgroundColor': "rgba(2,117,216,1)",
									'pointBorderColor': "rgba(255,255,255,0.8)",
									'pointHoverRadius': 5,
									'pointHoverBackgroundColor': "rgba(2,117,216,1)",
									'pointHitRadius': 50,
									'pointBorderWidth': 2,
									'data': charts['current_month_visitors']['data']
								}
							],
						},

						'options':
						{
							'scales':
							{
								'xAxes':
								[
									{
										'time':
										{
											'unit': 'date'
										},

										'gridLines':
										{
											'display': false
										},

										'ticks':
										{
											//'maxTicksLimit': 7
										}
									}
								],

								/*

								'yAxes':
								[
									{
										'ticks':
										{
											'min': 0,
											'max': 40000,
											'maxTicksLimit': 5
										},
										'gridLines':
										{
											'color': "rgba(0, 0, 0, .125)",
										}
									}
								]

								*/
							},

							'legend':
							{
								'display': false
							}
						}
					}
				)
				;
			}
		}
	
	

	let visitorTableColumns = [];
	let visitorTableRecords = [];
	let visitorTableKey     = [];



	let visitorTable = null;

	

	// Returns [Promise:bool]
	async function emptyLog ()
	{
		if ( !confirm('Are you sure to empty the log ?') ) return;



		// (Getting the value)
		const response = await Solenoid.HTTP.sendRequest
		(
			Core.URL.build( '/admin/visitor' ),
			'RPC',
			[
				'Action: empty_log',
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



		// (Moving to the URL)
		//Core.URL.open('');



		// (Setting the value)
		visitorTableRecords = [];



		// Returning the value
		return true;
	}



	$:
		if ( browser )
		{// Value is true
			// (Fetching the data)
			fetchData();



			// (Setting the values)
			Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
			Chart.defaults.global.defaultFontColor = '#292b2c';
		}

</script>

<svelte:head>
	<title>{ resourceName }</title>
</svelte:head>

<App>
	<div slot="body">
		{ #if $user }
			<Base>
				<div slot="body">
    				<h1 class="mt-4 mb-4">{ resourceName }</h1>

					<div class="row">
						<div class="col-xl-6">
							<div class="card mb-4">
								<div class="card-header">
									<i class="fas fa-chart-area me-1"></i>
									Visitors • Current Month
								</div>
								<div class="card-body">
									<canvas id="current_month_visitors_chart" width="100%" height="40" bind:this={ currentMonthVisitorsChartElement }></canvas>
								</div>
							</div>
						</div>
						<div class="col-xl-6">
							<div class="card mb-4">
								<div class="card-header">
									<i class="fas fa-chart-bar me-1"></i>
									Visitors • Current Year
								</div>
								<div class="card-body"><canvas id="current_year_visitors_chart" width="100%" height="40" bind:this={ currentYearVisitorsChartElement }></canvas></div>
							</div>
						</div>
					</div>

					<div class="card">
						<div class="card-header">
							<div class="row">
								<div class="col d-flex align-items-center">
									<i class="fas fa-table me-3"></i>
									Visitors • List ( { visitorTableRecords.length } )
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
							<Table id="visitor_table"
								bind:api={ visitorTable }

								columns={ visitorTableColumns }
								records={ visitorTableRecords }
							/>
						</div>
					</div>
				</div>
			</Base>
		{ /if }
	</div>
</App>

<style>

	@media screen and (max-width: 800px)
	{
		:global( .compressible[data-column="ip"] > div )
		{
			flex-direction: column;
		}

		:global( .compressible:not([data-column="ip"]) )
		{
			display: none !important;
		}
	}

</style>