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



    export let api = null;

    $:
        if ( element )
        {// Value found
            if ( !api )
            {// Value not found
                // (Setting the value)
                api = {};



                // (Setting the value)
                api.ready = false;



                // (Setting the value)
                api.keySearch = {};



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

                // Returns [Array<string>]
                api.getColumnValues = function (column)
                {
                    // Returning the value
                    return [ ...new Set( records.map( function (record) { return record.values[ api.getColumnIndex(column) ].value; } ) ) ];
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



                // Returns [object]
                api.getSearchValues = function ()
                {
                    // (Getting the value)
                    const result =
                    {
                        'global': element.querySelector('.search-box .input').value,
                        'local': {},
                        'keys':  {}
                    }
                    ;



                    // (Iterating each entry)
                    element.querySelectorAll('.jtable thead tr th[data-column]').forEach
                    (
                        function (el)
                        {
                            // (Getting the values)
                            result.local[ el.getAttribute('data-column') ] = el.querySelector('.input').value;
                            result.keys[ el.getAttribute('data-column') ]  = Array.from( el.querySelectorAll('.column-key-search-menu .input:checked') ).map( function (elem) { return elem.value; } );
                        }
                    )
                    ;



                    // Returning the value
                    return result;
                }

                // Returns [void]
                api.filter = function (fn)
                {
                    switch ( fn )
                    {
                        case 'SEARCH_GLOBAL':// (OR of values)
                            // (Getting the value)
                            const searchValue = api.getSearchValues().global;

                            for ( const i in records )
                            {// Iterating each index
                                // (Setting the value)
                                let resultFound = false;

                                for ( const column of records[i].values )
                                {// Processing each entry
                                    if ( column.value.toString().toLowerCase().indexOf( searchValue.toLowerCase() ) !== -1 )
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

                        case 'SEARCH_LOCAL':// (AND of values)
                            // (Getting the value)
                            const values = api.getSearchValues().local;

                            for ( const i in records )
                            {// Processing each entry
                                // (Setting the value)
                                let resultFound = true;

                                for ( const k in values )
                                {// Processing each entry
                                    // (Getting the value)
                                    const value = records[i].values[ api.getColumnIndex(k) ].value;

                                    if ( value.toString().toLowerCase().indexOf( values[k].toString().toLowerCase() ) === -1 )
                                    {// Match failed
                                        // (Setting the value)
                                        resultFound = false;

                                        // Breaking the iteration
                                        break;
                                    }
                                }



                                // (Getting the value)
                                records[i].hidden = !resultFound;
                            }
                        break;

                        case 'SEARCH_KEYS':// (OR of keys)
                            // (Getting the value)
                            const keys = api.getSearchValues().keys;

                            for ( const i in records )
                            {// Processing each entry
                                // (Setting the value)
                                let resultFound = true;

                                for ( const k in records[i].values )
                                {// Processing each entry
                                    // (Getting the values)
                                    const column = records[i].values[k].column;
                                    const value  = records[i].values[k].value;

                                    if ( keys[column].length > 0 && !keys[column].includes( value.toString() ) )
                                    {// Match failed
                                        console.debug(keys[column],value.toString());
                                        // (Setting the value)
                                        resultFound = false;

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



                // Returns [void]
                api.setKeySearch = function ()
                {
                    // (Setting the value)
                    const keySearch = {};



                    if ( records.length === 0 ) return;



                    for ( const i in records[0].values )
                    {// Processing each entry
                        // (Getting the value)
                        keySearch[ records[0].values[i].column ] =
                        {
                            'menuOpen': false
                        }
                        ;
                    }



                    // (Getting the value)
                    api.keySearch = keySearch;
                }



                // (Setting the value)
                api.ready = true;
            }
        }
    
    
    
    // Returns [void]
    function onGlobalSearch (event)
    {
        // (Filtering the records)
        api.filter('SEARCH_GLOBAL');
    }



    // Returns [void]
    function onLocalSearch (event)
    {
        // (Filtering the records)
        api.filter('SEARCH_LOCAL');
    }



    /*

    // Returns [void]
    function onDataChange (rr)
    {
        // (Triggering the event)
        dispatch('datachange');
    }



    $:
        // (Calling the function)
        onDataChange(records);

    */
    
    
    
    // Returns [void]
    function onKeySearchMenuBtnClick (column)
    {
        // (Getting the value)
        api.keySearch[ column ].menuOpen = !api.keySearch[ column ].menuOpen;
    }

    // Returns [void]
    function onKeySearch (event)
    {
        // (Filtering the records)
        api.filter('SEARCH_KEYS');
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
                        <div class="col d-flex align-items-center" style="justify-content: space-between;">
                            <div class="num-results">( <b>{ records.filter( function (record) { return !record.hidden; } ).length }</b> )</div>

                            <div class="search-box">
                                { #if records.filter( function (record) { return record.hidden; } ).length > 0 }
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
                                            <th data-column={ column }>
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

                                                <div class="column-search-box">
                                                    <input type="text" class="form-control form-control-sm input" on:input={ onLocalSearch }>
                                                </div>

                                                { #if Object.keys( api.keySearch ).length > 0 }
                                                    <div class="column-key-search-box mt-2">
                                                        <!-- svelte-ignore a11y-click-events-have-key-events -->
                                                        <div class="column-key-search-btn d-flex justify-content-center align-items-center" on:click={ onKeySearchMenuBtnClick(column) }>
                                                            { #if api.keySearch[ column ].menuOpen }
                                                                <i class="fa-solid fa-caret-up"></i>
                                                            { :else }
                                                                <i class="fa-solid fa-caret-down"></i>
                                                            { /if }
                                                        </div>

                                                        <div class="column-key-search-menu" data-state={ api.keySearch[ column ].menuOpen ? 'open' : 'closed' }>
                                                            <ul>
                                                                { #each api.getColumnValues(column) as key }
                                                                    <li>
                                                                        <label class="m-0 d-block">
                                                                            <input type="checkbox" class="input" value={ key } on:change={ onKeySearch }> { key }
                                                                        </label>
                                                                    </li>
                                                                { /each }
                                                            </ul>
                                                        </div>
                                                    </div>
                                                { /if }
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
        margin: 10px 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .search-box .input
    {
        flex-grow: 1;
    }

    .column-key-search-box
    {
        position: relative;
    }

    .column-key-search-btn
    {
        color: #ffffff;
        background-color: #858796;
        border-radius: 2px;
        cursor: pointer;
    }

    .column-key-search-btn.filter-active
    {
        color: #ffffff;
        background-color: #4e73df;
        border-radius: 2px;
        cursor: pointer;
    }

    .column-key-search-menu[data-state="open"]
    {
        width: 100%;
        display: table;
        position: absolute;
        background-color: #ffffff;
        border-radius: 2px;
    }

    .column-key-search-menu[data-state="closed"]
    {
        display: none;
    }

    .column-key-search-menu ul
    {
        margin: 0;
        padding: 10px;
        list-style: none;
    }

</style>