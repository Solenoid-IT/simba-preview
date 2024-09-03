<script>

    import { browser } from '$app/environment';

    import { createEventDispatcher } from 'svelte';



    const dispatch = createEventDispatcher();



    export let title   = '';
    export let records = [];



    $:
        if ( browser )
        {// Value found
            // (Listening for the event)
            jQuery('body').delegate('.jtable table tbody tr .controls .btn', 'click', function (event) {
                // (Getting the value)
                const rowElement = this.closest('tr');

                // (Triggering the event)
                dispatch
                (
                    'record.action',
                    {
                        'element': rowElement,
                        'id':      rowElement.getAttribute('data-id'),
                        'action':  this.getAttribute('value')
                    }
                )
                ;
            });
        }



    let element;



    let sortColumn  = null;
    let sortReverse = false;



    export let api;

    $:
        if ( element )
        {// Value found
            // (Setting the value)
            api = {};



            // (Setting the value)
            api.ready = false;



            // (Setting the value)
            api.search =
            {
                'global':
                {
                    'value': '',
                    'fn':    'SEARCH_GLOBAL'
                }
            }
            ;



            // Returns [number|false]
            api.getColumnIndex = function (column)
            {
                // (Setting the value)
                let index = false;

                for ( const i in records[0].values )
                {// Iterating each index
                    if ( records[0].values[i].column === column )
                    {// Match OK
                        // (Getting the value)
                        index = i;

                        // Breaking the iteration
                        break;
                    }
                }



                // Returning the value
                return index;
            }

            // Returns [void]
            api.sort = function (column, reverse)
            {
                if ( typeof reverse === 'undefined' ) reverse = sortReverse;



                // (Getting the value)
                const index = api.getColumnIndex(column);



                // (Sorting the array)
                records.sort
                (
                    function (a, b)
                    {
                        if ( a.values[index].value < b.values[index].value )
                        {// (A is lesser than B)
                            // Returning the value
                            return reverse ? 1 : -1;
                        }
                        else
                        if ( a.values[index].value > b.values[index].value )
                        {// (A is greater than B)
                            // Returning the value
                            return reverse ? -1 : 1;
                        }



                        // Returning the value
                        return 0;
                    }
                )
                ;



                // (Getting the value)
                records = records;



                // (Setting the value)
                sortColumn = column;

                // (Getting the value)
                sortReverse = !sortReverse;
            }

            // Returns [void]
            api.filter = function (fn)
            {
                switch ( fn )
                {
                    case 'SEARCH_GLOBAL':
                        for ( const i in records )
                        {// Iterating each index
                            // (Setting the value)
                            let resultFound = false;

                            for ( const column of records[i].values )
                            {// Processing each entry
                                if ( column.value.toString().toLowerCase().indexOf( api.search.global.value.toLowerCase() ) !== -1 )
                                {// Match OK
                                    // (Setting the value)
                                    resultFound = true;

                                    // Breaking the iteration
                                    break;
                                }
                            }



                            // (Getting the value)
                            records[i].hidden = !resultFound;
                        }
                    break;

                    default:
                        // (Calling the function)
                        fn();
                }
            }



            // (Setting the value)
            api.ready = true;
        }
    
    
    
    // Returns [void]
    function onGlobalSearch (event)
    {
        // (Getting the value)
        api.search.global.value = event.target.value;



        // (Filtering the records)
        api.filter('SEARCH_GLOBAL');
    }

</script>

<div class="card shadow mb-4 jtable" bind:this={ element }>
    <div class="card-header py-3 d-flex align-items-center" style="justify-content: space-between;">
        <h6 class="m-0 font-weight-bold text-primary">{ title }</h6>

        <slot name="controls"/>
    </div>

    <div class="card-body">
        { #if records.length > 0 && api.ready }
            <div class="table-responsive">
                <div class="dataTables_wrapper dt-bootstrap4">
                    <div class="row">
                        <div class="col d-flex justify-content-end">
                            <div class="search-box">
                                { #if api.search.global.value.length > 0 }
                                    <button class="btn btn-danger" title="remove filter">
                                        <i class="fa-solid fa-filter-circle-xmark"></i>
                                    </button>
                                { :else }
                                    <button class="btn btn-secondary" title="apply filter">
                                        <i class="fa-solid fa-filter"></i>
                                    </button>
                                { /if }

                                <input type="text" class="form-control input ml-3" placeholder="Search ..." style="width: 250px;" on:input={ onGlobalSearch }>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <table class="table table-bordered dataTable" width="100%" cellspacing="0" role="grid" style="width: 100%;">
                                <thead>
                                    <tr>
                                        { #each records[0].values.map( function (record) { return record.column; } ) as column }
                                            <th>
                                                <!-- svelte-ignore a11y-click-events-have-key-events -->
                                                <div class="column-header" on:click={ () => { api.sort(column); } }>
                                                    <div class="column-name">{ column }</div>
                                                    <div class="column-controls d-flex" style="flex-direction: column; font-size: 12px;">
                                                        { #if sortColumn === column }
                                                            { #if sortReverse }
                                                                <i class="fa-solid fa-caret-up"></i>
                                                            { :else }
                                                                <i class="fa-solid fa-caret-down"></i>
                                                            { /if }
                                                        { /if }
                                                    </div>
                                                </div>
                                            </th>
                                        { /each }

                                        <th class="controls"></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    { #each records as record }
                                        { #if !record.hidden }
                                            <tr data-id={ record.id }>
                                                { #each record.values as value }
                                                    <td>{ value.value }</td>
                                                { /each }

                                                <td class="controls text-center">
                                                    { @html record.controls }
                                                </td>
                                            </tr>
                                        { /if }
                                    { /each }
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        { :else }
            <div class="row">
                <div class="col d-flex justify-content-center align-items-center" style="flex-direction: column;">
                    <span class="mb-3" style="font-size: 18px; font-weight: 800;">NO DATA</span>

                    <i class="fa-solid fa-box" style="font-size: 32px;"></i>
                </div>
            </div>
        { /if }
    </div>
</div>

<style>

    .table tbody tr:nth-child(even)
    {
        background-color: #e1e1e1;
    }

    .table tbody tr:hover
    {
        background-color: #d4d4d4;
    }

    .table .column-header
    {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .search-box
    {
        width: 350px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .search-box .input
    {
        flex-grow: 1;
    }

</style>