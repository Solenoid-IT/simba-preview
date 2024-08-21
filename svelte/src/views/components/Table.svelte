<script>

    import { createEventDispatcher, onMount } from 'svelte';

    const dispatch = createEventDispatcher();



    // (Setting the values)
    const pvt = {};
    const pub = {};



    // (Setting the values)
    pvt.visible                  = true;
    pvt.eventListener            = {};

    pvt.sortColumn               = null;
    pvt.sortReverse              = false;

    pvt.keySearchBox             = {};

    pvt.globalSearchInputElement = null;

    pvt.activeFilter             = null;
    pvt.filterEnabled            = null;

    pvt.searchValues             = null;

    pub.element                  = null;



    // Returns [string]
    pvt.extractKey = function (record, separator)
    {
        if ( typeof separator === 'undefined' ) separator = ';';



        // (Setting the value)
        let k = [];

        for (const column in record['values'])
        {// Processing each entry
            if ( key.includes( column ) )
            {// Match OK
                // (Appending the value)
                k.push( record['values'][column]['value'] ?? pvt.extractText( record['values'][column]['text'] ) );
            }
        }



        // Returning the value
        return k.join( separator );
    }

    // Returns [string]
    pvt.extractText = function (html)
    {
        // (Creating an element)
        const element = document.createElement('span');

        // (Setting the value)
        element.innerHTML = html;



        // Returning the value
        return element.innerText/*.trim()*/;
    }



    // Returns [string|number]
    pvt.getSortValue = function (column)
    {
        // (Getting the value)
        let value = column.value ?? column.text;

        if ( /^[0-9]+$/.test(value) )
        {// Match OK
            // (Getting the value)
            value = parseInt(value);
        }
        else
        if ( /^[0-9]+\.[0-9]+$/.test(value) )
        {// Match OK
            // (Getting the value)
            value = parseFloat(value);
        }



        // Returning the value
        return value;
    }



    // Returns [object]
    pub.getSearchValues = function ()
    {
        // (Setting the value)
        const searchValues =
        {
            'global': pvt.globalSearchInputElement.value,
            'local':  {},
            'keys':   {}
        }
        ;



        // (Iterating each entry)
        pub.element.querySelectorAll('.input[data-type="local-search"]').forEach
        (
            function (inputElement)
            {
                // (Getting the value)
                searchValues['local'][ inputElement.closest('th').getAttribute('data-column') ] = inputElement.value;
            }
        )
        ;



        for (const column in searchValues['local'])
        {// Processing each entry
            // (Getting the value)
            searchValues['keys'][column] = pvt.keySearchBox[column]['values'].filter( function (value) { return !value['hidden'] && value['checked']; } ).map( function (value) { return value['content']; } );
        }



        // Returning the value
        return searchValues;
    }

    // Returns [void]
    pub.setSearchValues = function (searchValues)
    {
        // (Setting the property)
        pub.element.querySelector('.global-controls .input[name="value"]').value = searchValues['global'];



        for (const column in searchValues['local'])
        {// Processing each entry
            // (Setting the property)
            pub.element.querySelector(`.table th[data-column="${ column }"] .input[data-type="local-search"]`).value = searchValues['local'][column];
        }



        for (const column in searchValues['keys'])
        {// Processing each entry
            for (const i in pvt.keySearchBox[column].values)
            {// Iterating each index
                // (Getting the value)
                pvt.keySearchBox[column].values[i].checked = searchValues['keys'][column].includes( pvt.keySearchBox[column].values[i].content );
            }
        }
    }



    // Returns [array<string>]
    pub.getColumnValues = function (column)
    {
        // Returning the value
        return [ ... new Set( records.map( function (record) { return record['values'][column]['value'] ?? pvt.extractText( record['values'][column]['text'] ); } ) ) ];
    }



    // Returns [object]
    pub.getRecord = function (index)
    {
        // Returning the value
        return records[index];
    }

    // Returns [self]
    pub.removeRecord = function (index)
    {
        // (Splicing the array)
        records.splice( index, 1 );

        // (Getting the value)
        records = records;
    }



    // Returns [self]
    pub.sort = function (column, reverse)
    {
        if (typeof reverse === 'undefined') reverse = false;



        // (Sorting the array)
        records.sort
        (
            function (a, b)
            {
                // (Getting the values)
                const l = pvt.getSortValue( a.values[column] );
                const r = pvt.getSortValue( b.values[column] );



                if ( l < r )
                {// Match OK
                    // Returning the value
                    return reverse ? 1 : -1;
                }
                else
                if ( l > r )
                {// Match OK
                    // Returning the value
                    return reverse ? -1 : 1;
                }



                // Returning the value
                return 0;
            }
        )
        ;



        // (Getting the values)
        pvt.sortColumn  = column;
        pvt.sortReverse = reverse;



        // (Getting the value)
        records = records;



        // Returning the value
        return pub;
    }



    // Returns [self]
    pub.filter = function (condition)
    {
        if ( typeof condition === 'undefined' ) condition = 'FILTER_GLOBAL';



        if ( condition === 'FILTER_GLOBAL' )
        {// Match OK
            // (Getting the value)
            const searchValue = pvt.globalSearchInputElement.value.toLowerCase();



            // (Getting the value)
            condition = function (record)
            {
                if ( searchValue.length === 0 ) return true;



                for (const column in record['values'])
                {// Processing each entry
                    // (Getting the value)
                    const values = Object.values( record['values'][column] ).filter( function (value) { return value !== null; } ).map( function (value) { return pvt.extractText(value).toLowerCase(); } );

                    for (const value of values)
                    {// Processing each entry
                        if ( value.indexOf( searchValue ) !== -1 )
                        {// Match OK
                            // Returning the value
                            return true;
                        }
                    }
                }



                // Returning the value
                return false;
            }
        }
        else
        if ( condition === 'FILTER_LOCAL' )
        {// Match OK
            // (Getting the value)
            const searchValues = pub.getSearchValues()['local'];



            // (Getting the value)
            condition = function (record)
            {
                for (const column in record['values'])
                {// Processing each entry
                    if ( searchValues[column] === undefined ) continue;



                    // (Getting the value)
                    const values = Object.values( record['values'][column] ).filter( function (value) { return value !== null; } ).map( function (value) { return pvt.extractText(value).toLowerCase(); } );



                    // (Setting the value)
                    let result = false;

                    for (const value of values)
                    {// Processing each entry
                        if ( value.indexOf( searchValues[column] ) !== -1 )
                        {// Match OK
                            // Returning the value
                            result = true;
                        }
                    }



                    if ( !result ) return false;
                }



                // Returning the value
                return true;
            }
        }
        else
        if ( condition === 'FILTER_KEY' )
        {// Match OK
            // (Getting the value)
            const keys = pub.getSearchValues()['keys'];



            // (Getting the value)
            condition = function (record)
            {
                for (const column in record['values'])
                {// Processing each entry
                    if ( keys[column] === undefined ) continue;



                    // (Getting the value)
                    const values = Object.values( record['values'][column] ).filter( function (value) { return value !== null; } ).map( function (value) { return pvt.extractText(value); } );



                    // (Setting the value)
                    let result = false;

                    for (const key of keys[column])
                    {// Processing each entry
                        for (const value of values)
                        {// Processing each entry
                            if ( value === key )
                            {// Match OK
                                // Returning the value
                                result = true;
                            }
                        }
                    }



                    if ( keys[column].length === 0 )
                    {// Match OK
                        // (Setting the value)
                        result = true;
                    }



                    if ( !result ) return false;
                }



                // Returning the value
                return true;              
            }
        }



        for (const i in records)
        {// Iterating each index
            // (Getting the value)
            records[i]['hidden']   = !condition( records[i] );
            records[i]['selected'] = false;
        }



        // (Getting the value)
        records = records;



        // (Getting the element)
        const selectionInputElement = pub.element.querySelector('th .selection-input');

        if ( selectionInputElement )
        {// Value found
            // (Setting the property)
            selectionInputElement.checked = false;
        }



        // Returning the value
        return pub;
    }

    // Returns [self]
    pub.resetKeySearchFilter = function ()
    {
        for (const column in pvt.keySearchBox)
        {// Processing each entry
            for (const i in pvt.keySearchBox[column]['values'])
            {// Processing each entry
                // (Setting the values)
                pvt.keySearchBox[column]['values'][i]['hidden']  = false;
                pvt.keySearchBox[column]['values'][i]['checked'] = false;
            }

            // (Setting the value)
            pvt.keySearchBox[column]['hidden'] = true;
        }



        // Returning the value
        return pub;
    }

    // Returns [self]
    pub.resetFilter = function ()
    {
        // (Setting the value)
        pvt.globalSearchInputElement.value = '';



        // (Iterating each entry)
        pub.element.querySelectorAll('.input[data-type="local-search"]').forEach
        (
            function (inputElement)
            {
                // (Setting the value)
                inputElement.value = '';
            }
        )
        ;



        // (Resetting the key-search filter)
        pub.resetKeySearchFilter();



        for (const i in records)
        {// Processing each entry
            // (Setting the value)
            records[i]['hidden'] = false;
        }



        // Returning the value
        return pub;
    }



    // Returns [array<object>]
    pub.getValues = function (cols, all)
    {
        if ( typeof cols === 'undefined' ) cols = [];
        if ( typeof all === 'undefined' ) all = false;



        // (Setting the value)
        let rs = [];

        for (const i in records)
        {// Iterating each index
            if ( !all )
            {// Match OK
                if ( records[i]['hidden'] ) continue;
            }



            // (Setting the value)
            let r = {};

            if ( cols.length === 0 )
            {// Match OK
                for (const column in records[i]['values'])
                {// Iterating each index
                    // (Getting the value)
                    r[column] = records[i]['values'][column]['value'] ?? pvt.extractText( records[i]['values'][column]['text'] );
                }
            }
            else
            {// Match failed
                for (const column of cols)
                {// Processing each entry
                    // (Getting the value)
                    r[column] = records[i]['values'][column]['value'] ?? pvt.extractText( records[i]['values'][column]['text'] );
                }
            }



            // (Appending the value)
            rs.push( r );
        }



        // Returning the value
        return rs;
    }

    // Returns [string]
    pub.exportCSV = function (cols, header, columnSeparator, rowSeparator)
    {
        if ( typeof cols === 'undefined' ) cols = [];
        if ( typeof header === 'undefined' ) header = false;
        if ( typeof columnSeparator === 'undefined' ) columnSeparator = ';';
        if ( typeof rowSeparator === 'undefined' ) rowSeparator = "\n";



        // Returning the value
        return ( header ? ( cols.length === 0 ? columns.map( function (column) { return column['id']; } ) : cols ).join(columnSeparator) + rowSeparator : '' ) + pub.getValues(cols).map( function (record) { return Object.values(record).map( function (value) { return value.replace(/\n/g, "\\n").replace(/\t/g, "\\t"); } ).join(columnSeparator); } ).join(rowSeparator);
    }



    // Returns [array<object>]
    pub.getSelectedEntries = function ()
    {
        // Returning the value
        return records.filter( function (record) { return !record['hidden'] && record['selected']; } );
    }

    // Returns [void]
    pub.resetSelectedEntries = function ()
    {
        for (const i in records)
        {// Iterating each index
            // (Setting the value)
            records[i]['selected'] = false;
        }
    }



    // Returns [object|false]
    pub.getRecordById = function (id)
    {
        for (const i in records)
        {// Iterating each index
            if ( records[i].key === id ) return records[i];
        }



        // Returning the value
        return false;
    }

    // Returns [mixed|false]
    pub.getRecordValue = function (id, column)
    {
        // (Getting the value)
        const r = pub.getRecordById(id);

        if ( r === false ) return false;



        // (Getting the value)
        const v = r.values[column];



        // Returning the value
        return v.value ?? v.text;
    }



    // Returns [void]
    pub.addEventListener = function (type, callback)
    {
        if ( typeof pvt.eventListener[ type ] === 'undefined' ) pvt.eventListener[ type ] = [];

        // (Appending the value)
        pvt.eventListener[ type ].push( callback );
    }

    // Returns [void]
    pub.triggerEvent = function (type, data)
    {
        for (const callback of pvt.eventListener[ type ])
        {// Processing each entry
            // (Calling the function)
            callback( data );
        }
    }



    export let id              = null;
    export let api             = null;

    export let columns         = [];
    export let records         = [];

    export let searchEngine    = 'internal';
    export let selectable      = false;

    export let ready           = false;

    export let onRecordReady   = function () {};



    // Returns [void]
    function onSort (column)
    {
        // (Sorting the records)
        pub.sort( column, pvt.sortReverse );



        // (Getting the value)
        pvt.sortReverse = !pvt.sortReverse;
    }



    // Returns [void]
    function onGlobalSearch (event)
    {
        // (Resetting the key-search filter)
        pub.resetKeySearchFilter();



        if ( searchEngine === 'internal' )
        {// Match OK
            // (Filtering the table)
		    pub.filter( 'FILTER_GLOBAL' );
        }
        else
        {// Match failed
            // (Triggering the event)
            dispatch('global-search');
        }



        // (Getting the values)
        pvt.activeFilter  = event.target.value.length > 0 ? 'FILTER_GLOBAL' : null;
        pvt.filterEnabled = event.target.value.length > 0;
    }

    // Returns [void]
    function onLocalSearch (event)
    {
        // (Resetting the key-search filter)
        pub.resetKeySearchFilter();



        if ( searchEngine === 'internal' )
        {// Match OK
            // (Filtering the table)
		    pub.filter( 'FILTER_LOCAL' );
        }
        else
        {// Match failed
            // (Triggering the event)
            dispatch('local-search');
        }



        // (Getting the value)
        const resultFound = Object.entries( pub.getSearchValues()['local'] ).filter( function ([ column, value ]) { return value.length > 0; } ).length > 0;



        // (Getting the values)
        pvt.activeFilter  = resultFound ? 'FILTER_LOCAL' : null;
        pvt.filterEnabled = resultFound;
    }

    // Returns [void]
    function onKeySearch ()
    {
        if ( searchEngine === 'internal' )
        {// Match OK
            // (Filtering the table)
		    pub.filter( 'FILTER_KEY' );
        }
        else
        {// Match failed
            // (Triggering the event)
            dispatch('key-search');
        }



        // (Setting the value)
        let resultFound = false;

        for (const column in pvt.keySearchBox)
        {// Processing each entry
            for (const value of pvt.keySearchBox[column].values)
            {// Processing each entry
                if ( value.checked )
                {// Value is true
                    // (Setting the value)
                    resultFound = true;

                    // Breaking the iteration
                    break;
                }
            }
        }



        // (Getting the values)
        pvt.activeFilter  = resultFound ? 'FILTER_KEY' : null;
        pvt.filterEnabled = resultFound;
    }



    $:
        if ( columns )
        {// Value found
            for (const column of columns)
            {// Processing each entry
                // (Setting the value)
                let values = [];

                for (const value of pub.getColumnValues( column['id'] ))
                {// Processing each entry
                    // (Appending the value)
                    values.push
                    (
                        {
                            'hidden':  false,
                            'checked': false,

                            'content': value
                        }
                    )
                    ;
                }



                // (Getting the value)
                pvt.keySearchBox[ column['id'] ] =
                {
                    'hidden': true,
                    'values': values
                }
                ;
            }
        }
    
    

    // Returns [void]
    function onToggleKeySearchBox (column)
    {
        // (Getting the value)
        pvt.keySearchBox[column]['hidden'] = !pvt.keySearchBox[column]['hidden'];
    }

    // Returns [void]
    function onValueSearch (event, column)
    {
        // (Getting the value)
        const searchValue = event.target.value;

        for (const i in pvt.keySearchBox[column]['values'])
        {// Iterating each index
            // (Getting the values)
            pvt.keySearchBox[column]['values'][i]['hidden']  = pvt.keySearchBox[column]['values'][i]['content'].toLowerCase().indexOf( searchValue.toLowerCase() ) === -1;
            pvt.keySearchBox[column]['values'][i]['checked'] = false;
        }



        // (Setting the property)
        event.target.closest('.key-search-box').querySelector('ul[data-type="controls"] .input[data-all]').checked = false;



        // (Setting the value)
        let resultFound = false;

        for (const column in pvt.keySearchBox)
        {// Processing each entry
            for (const value of pvt.keySearchBox[column].values)
            {// Processing each entry
                if ( value.checked )
                {// Value is true
                    // (Setting the value)
                    resultFound = true;

                    // Breaking the iteration
                    break;
                }
            }
        }



        // (Getting the values)
        pvt.activeFilter  = resultFound ? 'FILTER_KEY' : null;
        pvt.filterEnabled = resultFound;
    }



    // Returns [void]
    function onAllKeysChange (column, checked)
    {
        for (const i in pvt.keySearchBox[column]['values'])
        {// Iterating each index
            if ( pvt.keySearchBox[column]['values'][i]['hidden'] ) continue;

            // (Setting the value)
            pvt.keySearchBox[column]['values'][i]['checked'] = checked;
        }



        // (Calling the function)
        onKeySearch();
    }

    // Returns [void]
    function onKeyChange (column, index, checked)
    {
        // (Getting the value)
        pvt.keySearchBox[column]['values'][index]['checked'] = checked;



        // (Calling the function)
        onKeySearch();
    }



    // Returns [void]
    function onAllSelectionChange ()
    {
        for (const i in records)
        {// Iterating each index
            // (Setting the value)
            records[i]['selected'] = records[i]['selectable'] && this.checked;
        }
    }

    // Returns [void]
    function onSelectionChange ()
    {
        // (Setting the value)
        records[ jq(this).closest('tr').index() ]['selected'] = this.checked;
    }



    // Returns [void]
    function onToggleFilter ()
    {
        if ( pvt.filterEnabled )
        {// Value is true
            // (Getting the value)
            pvt.searchValues = pub.getSearchValues();

            // (Resetting the filter)
            pub.resetFilter();
        }
        else
        {// Value is false
            if ( pvt.searchValues )
            {// Value found
                // (Setting the search values)
                pub.setSearchValues( pvt.searchValues );

                // (Filtering the table)
                pub.filter( pvt.activeFilter );
            }
        }



        // (Getting the value)
        pvt.filterEnabled = !pvt.filterEnabled;
    }



    // Returns [void]
    function onClick (event)
    {
        if ( event.target.closest('.key-search-box-inner') !== null || event.target.closest('.key-search-box-button') !== null || event.target.closest('i[data-tksb]') !== null ) return;

        for (const columnId in pvt.keySearchBox)
        {// Processing each entry
            // (Setting the value)
            pvt.keySearchBox[ columnId ]['hidden'] = true;
        }
    }



    // (Listening for the event)
    onMount
    (
        function ()
        {
            // (Listening for the events)
            jq(pub.element).delegate('.selection-controls-box .input[value]', 'click', function (event) {
                // (Triggering the event)
                dispatch
                (
                    'selection.action',
                    {
                        'entries': pub.getSelectedEntries(),
                        'value':   this.value
                    }
                )
                ;



                // (Resetting the selection)
                pub.resetSelectedEntries();
            });



            // (Listening for the events)
            jq(pub.element).delegate('.table tbody tr .input[value]:not([data-option])', 'click', function (event) {
                // (Getting the values)
                const rowElement = this.closest('tr');
                const index      = jq(rowElement).index();

                // (Triggering the event)
                dispatch
                (
                    'entry.action',
                    {
                        'rowElement': rowElement,
                        'index':      index,

                        'id':         records[index].key,
                        'value':      this.value
                    }
                )
                ;
            });

            jq(pub.element).delegate('.table tbody tr .input[value]', 'change', function (event) {
                // (Getting the values)
                const rowElement = this.closest('tr');
                const index      = jq(rowElement).index();

                // (Triggering the event)
                dispatch
                (
                    'entry.action',
                    {
                        'rowElement': rowElement,
                        'index':      index,

                        'id':         records[index].key,
                        'value':      this.value,

                        'checked':    this.checked
                    }
                )
                ;
            });



            // (Setting the value)
            ready = true;
        }
    )
    ;



    // (Getting the value)
    api = pub;

</script>

<svelte:window on:click={ (event) => { onClick(event); } }/>

{ #if api }
    { #if pvt.visible }
        <div class="swg swg-table { $$restProps.class }" id={ id } bind:this={ pub.element }>
            <div class="search-box">
                <div class="num-results-box">
                    Results : <span class="num-results">{ records.filter( function (record) { return !record['hidden']; } ).length }</span>
                </div>

                <div class="global-controls">
                    { #if pvt.filterEnabled !== null }
                        <button class={ pvt.filterEnabled ? "btn btn-danger input" : "btn btn-secondary input" } value="toggle_filter" title="Filter ON/OFF" on:click={ onToggleFilter }>
                            { #if pvt.filterEnabled }
                                <i class="fa-solid fa-filter-circle-xmark"></i>
                            { :else }
                                <i class="fa-solid fa-filter"></i>
                            { /if }

                            <span class="btn-label ms-1">
                                Filter ON/OFF
                            </span>
                        </button>
                    { /if }
                    <input type="text" class="form-control input" name="value" placeholder="Search ..." bind:this={ pvt.globalSearchInputElement } on:input={ (event) => { onGlobalSearch( event ); } }>
                </div>
            </div>

            { #if selectable }
                <div class="selection-box mt-2">
                    { #if records.filter( function (record) { return !record['hidden'] && record['selected']; } ).length > 0 }
                        <div class="num-results-box">
                            Selected : <span class="num-results">{ records.filter( function (record) { return !record['hidden'] && record['selected']; } ).length }</span>
                        </div>

                        <div class="selection-controls-box">
                            <slot name="selection-controls-box"/>
                        </div>
                    { /if }
                </div>
            { /if }

            <table class="table">
                <thead>
                    <tr>
                        { #if selectable }
                            <th data-type="selection">
                                <input type="checkbox" class="selection-input"
                                    checked={ records.filter( function (record) { return record['selected']; } ).length === records.filter( function (record) { return !record['hidden']; } ).length }
                                    on:change={ onAllSelectionChange }
                                >
                            </th>
                        { /if }

                        { #each columns.filter( function (column) { return !column['hidden']; } ) as column }
                            <th class="{ column['fixed'] ? 'fixed' : 'compressible' }" data-column="{ column['id'] }">
                                <!-- svelte-ignore a11y-click-events-have-key-events -->
                                <div class="title" on:click={ onSort( column['id'] ) }>
                                    { column['label'] }

                                    { #if pvt.sortColumn === column['id'] }
                                        { #if pvt.sortReverse }
                                            <span class="sort-dir-indicator-box">
                                                <i class="fa-solid fa-caret-up current-indicator"></i>
                                                <i class="fa-solid fa-caret-down"></i>
                                            </span>
                                        { :else }
                                            <span class="sort-dir-indicator-box">
                                                <i class="fa-solid fa-caret-up"></i>
                                                <i class="fa-solid fa-caret-down current-indicator"></i>
                                            </span>
                                        { /if }
                                    { :else }
                                        <span class="sort-dir-indicator-box">
                                            <i class="fa-solid fa-caret-up"></i>
                                            <i class="fa-solid fa-caret-down"></i>
                                        </span>
                                    { /if }
                                </div>

                                <input type="text" class="form-control input" data-type="local-search" on:input={ (event) => { onLocalSearch( event ); } }>

                                <!-- svelte-ignore a11y-click-events-have-key-events -->
                                <div class="key-search-box-button" data-state={ pvt.keySearchBox[ column['id'] ]['values'].filter( function(value) { return !value['hidden'] && value['checked']; } ).length === 0 ? '' : 'filter-active' }
                                    on:click={ onToggleKeySearchBox( column['id'] ) }
                                >
                                    { #if pvt.keySearchBox[ column['id'] ]['hidden'] }
                                        <i class="fa-solid fa-caret-down" style="line-height: 0;" data-tksb></i>
                                    { :else }
                                        <i class="fa-solid fa-caret-up" style="line-height: 0;" data-tksb></i>
                                    { /if }
                                </div>

                                { #if !pvt.keySearchBox[ column['id'] ]['hidden'] }
                                    <div class="key-search-box">
                                        <div class="key-search-box-inner">
                                            <div class="num-results-box">
                                                Total : <span class="num-results" data-type="total">{ pvt.keySearchBox[ column['id'] ]['values'].length }</span>
                                            </div>
                                            <div class="num-results-box">
                                                Selected : <span class="num-results" data-type="selected">{ pvt.keySearchBox[ column['id'] ]['values'].filter( function(value) { return !value['hidden'] && value['checked']; } ).length }</span>
                                            </div>
                                            <input type="text" class="form-control input" data-type="value-search"
                                                on:input={ (event) => { onValueSearch( event, column['id'] ); } }
                                            >
                                            <ul data-type="controls">
                                                <li>
                                                    <label>
                                                        <input type="checkbox" class="input" data-all
                                                            checked={ pvt.keySearchBox[ column['id'] ]['values'].filter( function (value) { return !value['hidden'] && value['checked']; } ).length === pvt.keySearchBox[ column['id'] ]['values'].length }
                                                            on:change={ (event) => { onAllKeysChange( column['id'], event.target.checked ); } }
                                                        >
                                                        <span class="key-value">[ ALL ] : </span>
                                                        <span class="num-results" data-type="keys">{ pvt.keySearchBox[ column['id'] ]['values'].filter( function (value) { return !value['hidden']; } ).length }</span>
                                                    </label>
                                                </li>
                                            </ul>
                                            <ul data-type="values">
                                                { #each pvt.keySearchBox[ column['id'] ]['values'].filter( function (value) { return !value['hidden']; } ) as value, index }
                                                    <li>
                                                        <label>
                                                            <input type="checkbox" class="input" checked={ value['checked'] ? 'true' : null }
                                                                on:change={ (event) => { onKeyChange( column['id'], index, event.target.checked ); } }
                                                            >
                                                            <span class="key-value">{ value['content'] }</span>
                                                        </label>
                                                    </li>
                                                { /each }
                                            </ul>
                                        </div>
                                    </div>
                                { /if }
                            </th>
                        { /each }

                        <th data-type="controls"></th>
                    </tr>
                </thead>
                <tbody>
                    { #if records.length === 0 }
                        <tr class="tr-nodata">
                            <td class="text-center" colspan="100%">
                                <div>
                                    <i class="fa-solid fa-box"></i>
                                    <div class="tr-nodata-text">
                                        NO DATA
                                    </div>
                                </div>
                            </td>
                        </tr>
                    { :else }
                        { #each records.filter( function (record) { return !record['hidden']; } ) as record }
                        <tr data-id="{ record['key'] }" use:onRecordReady>
                            { #if selectable }
                                <td data-type="selection">
                                    <input type="checkbox" class="selection-input"
                                        disabled={ record['selectable'] ? null : 'true' }
                                        checked={ record['selected'] ? 'true' : null }
                                        on:change={ onSelectionChange }
                                    >
                                </td>
                            { /if }

                            { #each Object.entries( record['values'] ) as [ k, v ] }
                                { #if !columns.filter( function (column) { return column['id'] === k; } )[0]['hidden'] }
                                    <td class="{ columns.filter( function (column) { return column['id'] === k; } )[0]['fixed'] ? 'fixed' : 'compressible' }" data-column="{ k }" data-value={ v['value'] }>{@html v['text'] }</td>
                                { /if }
                            { /each }

                            <td data-type="controls">
                                <div class="table-row-controls">
                                    {@html record['controls'] ?? '' }
                                </div>
                            </td>
                        </tr>
                    { /each }
                    { /if }
                </tbody>
            </table>
        </div>
    { /if }
{ /if }

<style>

    .swg.swg-table
    {
        width: 100%;
        min-height: 600px;
        font-size: 14px;
        overflow: auto;
    }

    .swg.swg-table table thead th[data-type="selection"],
    .swg.swg-table table tbody td[data-type="selection"]
    {
        width: 60px;
        text-align: center;
    }

    .swg.swg-table table thead th[data-column] .title
    {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;

        cursor: pointer;
    }

    .swg.swg-table table thead th[data-column] .sort-dir-indicator-box
    {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;

        font-size: 8px;
        color: #c8c8c8;
    }

    .swg.swg-table .sort-dir-indicator-box > *
    {

    }

    .swg.swg-table .sort-dir-indicator-box .current-indicator
    {
        color: #000000;
    }



    .swg.swg-table table thead th,
    .swg.swg-table table tbody td
    {
        padding: 4px 12px;
        text-align: left;
        vertical-align: middle;
    }
    .swg.swg-table table tbody tr:nth-child(even)
    {
        background-color: #e4e4e4;
    }

    .swg.swg-table table tbody tr:hover
    {
        background-color: #d9d9d9;
    }



    .swg.swg-table .box-hidden
    {
        display: none !important;
    }



    .swg.swg-table .selection-box
    {
        min-height: 40px;
    }

    .swg.swg-table .search-box,
    .swg.swg-table .selection-box
    {
        margin: 6px 0;

        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }

    .swg.swg-table .num-results-box
    {
        font-size: 12px;
        white-space: nowrap;
    }

    .swg.swg-table .num-results
    {
        color: #0d6efd;
        font-weight: 600;
    }

    .swg.swg-table .num-results[data-type="keys"]
    {
        margin-left: 6px;
    }

    .swg.swg-table .search-box .input
    {
        margin-left: 20px;
    }



    .swg.swg-table .input[data-type="local-search"]
    {
        margin: 10px 0;
    }



    .swg.swg-table .key-search-box-button
    {
        width: 100%;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        background-color: #dbdbdb;
        border-radius: 2px;
        cursor: pointer;
    }

    .swg.swg-table .key-search-box-button[data-state="filter-active"]
    {
        background-color: #0d6efd;
        color: #ffffff;
    }



    .swg.swg-table .key-search-box
    {
        position: relative;
    }

    .swg.swg-table .key-search-box-inner
    {
        padding: 14px 6px;
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        background-color: #e6e6e6;
        border-radius: 4px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .swg.swg-table .key-search-box ul
    {
        margin: 10px 0;
        padding: 0;
        list-style: none;
    }

    .swg.swg-table .key-search-box ul[data-type="values"]
    {
        min-height: 160px;
        max-height: 300px;
        overflow-y: auto;
    }

    .swg.swg-table .key-search-box ul li label
    {
        display: flex;
        flex-direction: row;
        justify-content: start;
        align-items: center;
    }

    .swg.swg-table .key-search-box ul li .input
    {
        margin-right: 20px;
        font-size: 10px;
    }

    .swg.swg-table .input[data-type="key-search"]
    {
        margin-top: 10px;
    }

    .swg.swg-table .input[data-type="local-search"],
    .swg.swg-table .key-search-box *
    {
        font-size: 10px;
    }

    .swg.swg-table .key-search-box .key-value
    {
        position: relative;
        bottom: 1px;
    }

    .swg.swg-table .key-search-box .num-results[data-type="keys"]
    {
        margin-top: -1px;
    }

    .swg.swg-table .global-controls
    {
        min-width: 400px;
        max-width: 600px;
        display: flex;
        flex-direction: row;
        justify-content: end;
        align-items: center;
    }



    .tr-nodata
    {
        
    }

    .tr-nodata:hover
    {
        background-color: transparent !important;
    }

    .tr-nodata td
    {
        padding: 70px 30px !important;
    }

    .tr-nodata i
    {
        font-size: 32px;
    }

    .tr-nodata-text
    {
        font-size: 14px;
        font-weight: 600;
    }

</style>