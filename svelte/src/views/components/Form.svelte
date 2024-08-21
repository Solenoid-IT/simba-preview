<script>

    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();



    // (Setting the values)
    const pvt = {};
    const pub = {};



    // (Setting the values)
    pvt.visible            = true;
    pvt.eventListener      = {};

    pvt.inputNameSeparator = '.';
    pvt.invalidInputClass  = 'input-invalid';

    pub.element           = null;



    // Returns [void]
    pvt.parseDotNotation = function (str, val, obj, separator)
    {
        let currentObj = obj,
            keys = str.split(separator),
            i, l = Math.max(1, keys.length - 1),
            key;
    
        for (i = 0; i < l; ++i) {
            key = keys[i];
            currentObj[key] = currentObj[key] || {};
            currentObj = currentObj[key];
        }
    
        currentObj[keys[i]] = val;
        delete obj[str];
    }
    ;

    // Returns [object]
    pvt.objectExpand = function (object, separator)
    {
        for (const key in object)
        {// Processing each entry
            if (key.indexOf(separator) !== -1)
            {// Match OK
                // (Parsing the dot notation)
                pvt.parseDotNotation(key, object[key], object, separator);
            }            
        }
    
    
    
        // Returning the value
        return object;
    }
    ;

    // Returns [object]
    pvt.objectCompress = function (object, separator)
    {
        // (Setting the value)
        let output = {};

        for (const i in object)
        {// Processing each entry
            if (!object.hasOwnProperty(i))
            {// Match failed
                // Continuing the iteration
                continue;
            }



            if ((typeof object[i]) === 'object' && object[i] !== null && !(object[i] instanceof Array))
            {// Match OK
                // (Calling the function)
                let currentObject = pvt.objectCompress(object[i], separator);

                for (const x in currentObject)
                {// Processing each entry
                    if (!currentObject.hasOwnProperty(x))
                    {// Match failed
                        // Continuing the iteration
                        continue;
                    }
    


                    // (Getting the value)
                    output[i+separator+x] = currentObject[x];
                }
            }
            else
            {// Match failed
                // (Getting the value)
                output[i] = object[i];
            }
        }



        // Returning the value
        return output;
    }

    // Returns [Promise:object]
    pvt.readFile = async function (file)
    {
        // Returning the value
        return new Promise
        (
            function ( resolve, reject )
            {
                // (Creating a FileReader)
                const fr = new FileReader();



                // (Handling the events)
                fr.onloadend = function (event)
                {
                    // (Calling the function)
                    resolve
                    (
                        {
                            'name':    file.name,
                            'type':    file.type,
                            'size':    file.size,

                            'content': btoa( event.target.result )
                        }
                    )
                    ;
                }

                fr.onerror = function (event)
                {
                    // (Calling the function)
                    reject( event.type );
                }



                // (Reading the file as binary string)
                fr.readAsBinaryString( file );
            }
        )
        ;
    }



    // Returns [void]
    pub.reset = function ()
    {
        // (Iterating each entry)
        pub.element.querySelectorAll('.input:not(.input-ignore)').forEach
        (
            function (inputElement)
            {
                // (Setting the value)
                inputElement.value = '';
            }
        )
        ;

        // (Iterating each entry)
        pub.element.querySelectorAll('.input.input-invalid').forEach
        (
            function (inputElement)
            {
                // (Setting the class name)
                inputElement.classList.remove( 'input-invalid' );
            }
        )
        ;
    }



    // Returns [Promise:object]
    pub.getInput = async function (mode)
    {
        // Returning the value
        return new Promise
        (
            async function (resolve, reject)
            {
                if ( typeof mode === 'undefined' ) mode = 'value';



                // (Setting the value)
                let input = {};

                for (let inputElement of pub.element.querySelectorAll('.input:not(.input-ignore)'))
                {// Processing each entry
                    // (Setting the value)
                    let inputValue = null;

                    switch ( inputElement.getAttribute('type') )
                    {
                        case 'checkbox':
                            // (Getting the value)
                            inputValue = inputElement.checked;
                        break;

                        case 'file':
                            // (Setting the value)
                            inputValue = [];

                            for (const file of inputElement.files)
                            {// Processing each entry
                                // (Appending the value)
                                inputValue.push( await pvt.readFile( file ) )
                            }



                            if ( inputElement.getAttribute( 'multiple' ) === null )
                            {// Value not found
                                // (Getting the value)
                                inputValue = inputValue[0];
                            }
                        break;

                        default:
                            // (Getting the value)
                            inputValue = inputElement.value;

                            switch ( inputElement.getAttribute('data-type') )
                            {
                                case 'int':
                                    // (Getting the value)
                                    inputValue = parseInt(inputValue);
                                break;

                                case 'float':
                                    // (Getting the value)
                                    inputValue = parseFloat(inputValue);
                                break;

                                case 'string':
                                    // (Doing nothing)
                                break;
                            }
                    }



                    // (Getting the value)
                    input[ inputElement.getAttribute('name') ] =
                        mode === 'value'
                            ?
                        inputValue
                            :
                        {
                            'element': inputElement,
                            'value':   inputValue
                        }
                    ;
                }
                ;

                // (Getting the value)
                input = pvt.objectExpand( input, pvt.inputNameSeparator );



                // (Calling the function)
                resolve( input );
            }
        )
        ;
    }

    // Returns [void]
    pub.setInput = function (input)
    {
        // (Getting the value)
        const kvData = pvt.objectCompress( input, pvt.inputNameSeparator );

        for (const k in kvData)
        {// Processing each entry
            // (Getting the element)
            const inputElement = pub.element.querySelector( `.input[name="${ k }"]` );

            if ( inputElement )
            {// Value found
                // (Getting the value)
                inputElement.value = kvData[ k ];
            }
        }
    }



    // Returns [Array<HTMLElement>]
    pub.validate = function (display)
    {
        if ( typeof display === 'undefined' ) display = false;



        // (Setting the values)
        let input        = {};
        let firstInvalid = false;

        // (Iterating each entry)
        pub.element.querySelectorAll('.input:not(.input-ignore)[data-required]').forEach
        (
            function (inputElement)
            {
                // (Getting the values)
                const regex = inputElement.getAttribute('data-regex');
                const valid = regex ? ( new RegExp( regex ).test( inputElement.value ) ) : ( inputElement.value.length === 0 ? false : true );

                if ( !valid )
                {// (Input is not valid)
                    if ( !firstInvalid )
                    {// Match OK
                        // (Setting the value)
                        firstInvalid = true;

                        if ( display )
                        {// Value is true
                            // (Focusing the element)
                            inputElement.focus();
                        }
                    }



                    // (Setting the value)
                    input[ inputElement.getAttribute('name') ] = inputElement;
                }



                if ( display )
                {// Value is true
                    // (Calling the function)
                    inputElement.classList[ valid ? 'remove' : 'add' ]( pvt.invalidInputClass );
                }
            }
        )
        ;



        // Returning the value
        return input;
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
        if ( typeof pvt.eventListener[ type ] === 'undefined' ) return;

        for (const callback of pvt.eventListener[ type ])
        {// Processing each entry
            // (Calling the function)
            callback( data );
        }
    }



    export let id  = null;
    export let api = null;



    // Returns [void]
    function onSubmit (event)
    {
        // (Preventing the default)
        event.preventDefault();

        // (Triggering the event)
        //pub.triggerEvent( 'submit', event );



        // (Triggering the event)
        dispatch( 'submit', { 'originalEvent': event } );
    }



    $:
        if ( pub.element )
        {// Value found
            // (Getting the value)
            api = pub;
        }

</script>

{ #if pvt.visible }
    <div class="swg swg-form { $$restProps.class }" id="{ id }" bind:this={ pub.element }>
        <form on:submit={ onSubmit }>
            <slot name="body"/>
        </form>
    </div>
{ /if }

<style>

    .swg.swg-form :global(.input.is-invalid),
    .swg.swg-form :global(.input.input-invalid)
    {
        border-color: #dc3545 !important;
        padding-right: calc(1.5em + .75rem) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right calc(.375em + .1875rem) center !important;
        background-size: calc(.75em + .375rem) calc(.75em + .375rem) !important;
    }
    
    .swg.swg-form :global(.input.is-invalid:focus),
    .swg.swg-form :global(.input.input-invalid:focus)
    {
        box-shadow: 0 0 0 .2rem rgba(220,53,69,.25) !important;
    }

</style>