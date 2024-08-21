<script>

    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();



    // (Setting the values)
    const pvt = {};
    const self = {};



    // (Setting the values)
    pvt.visible         = true;
    pvt.eventListener   = {};
    pvt.currentPassword =
    {
        'rank':                0,
        'threshold':
        {
            'color':           'transparent',
            'description':     ''
        },
        'progressDescription': ''
    }
    ;

    self.element          = null;
    self.passwordVisible  = false;
    self.inputElement     = null;
    self.generator        =
    {
        'length':     32,
        'minEntropy': 128
    }
    ;
    self.strengthMeter    =
    {
        'thresholds':
        [
            {
                'color': '#ad3a00',
                'description': 'Bad'
            }
            ,
            {
                'color': '#ad7400',
                'description': 'Very Weak'
            }
            ,
            {
                'color': '#adad00',
                'description': 'Weak'
            }
            ,
            {
                'color': '#74ad00',
                'description': 'Good'
            }
            ,
            {
                'color': '#3aad00',
                'description': 'Strong'
            }
        ],
        'entropy': 128
    }
    ;



    // Returns [number]
    pvt.random = function (min, max)
    {
        // Returning the value
        return Math.floor( Math.random() * ( max - min + 1 ) ) + min;
    }



    // Returns [number]
    self.absRank = function (password)
    {
        // (Setting the value)
        let numPossibleCharacters = 0;



        if ( /[a-z]/.test( password ) )
        {// (Lowercase Character)
            // (Incrementing the value)
            numPossibleCharacters += 26;
        }

        if ( /[A-Z]/.test( password ) )
        {// (Uppercase Character)
            // (Incrementing the value)
            numPossibleCharacters += 26;
        }

        if ( /[0-9]/.test( password ) )
        {// (Number)
            // (Incrementing the value)
            numPossibleCharacters += 10;
        }

        if ( /[^a-zA-Z0-9]/.test( password ) )
        {// (Symbol)
            // (Incrementing the value)
            numPossibleCharacters += 33;
        }



        // (Getting the value)
        const entropy = Math.log2( Math.pow( numPossibleCharacters, password.length ) );



        // Returning the value
        return entropy;
    }

    // Returns [number]
    self.rank = function (password)
    {
        // (Getting the value)
        let absRank = self.absRank( password );

        if ( absRank > self.strengthMeter.entropy ) absRank = self.strengthMeter.entropy;



        // Returning the value
        return ( absRank / self.strengthMeter.entropy ) * 100;
    }



    // Returns [object]
    self.generatePassword = function (length, minEntropy)
    {
        if ( typeof length === 'undefined' ) length = self.generator.length;
        if ( typeof minEntropy === 'undefined' ) minEntropy = self.generator.minEntropy;



        // (Setting the value)
        const characters = '!#$%&*+,-./0123456789:;@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_abcdefghijklmnopqrstuvwxyz{|}~';



        // (Setting the value)
        let password = null;



        while ( true )
        {// Processing each clock
            // (Setting the value)
            password = '';

            for (let i = 0; i < length; i++)
            {// Iterating each index
                // (Appending the value)
                password += characters.at( pvt.random( 0, characters.length - 1 ) );
            }



            if ( self.absRank( password ) >= minEntropy ) break;
        }



        // Returning the value
        return password;
    }



    // Returns [void]
    self.addEventListener = function (type, callback)
    {
        if ( typeof pvt.eventListener[ type ] === 'undefined' ) pvt.eventListener[ type ] = [];

        // (Appending the value)
        pvt.eventListener[ type ].push( callback );
    }

    // Returns [void]
    self.triggerEvent = function (type, data)
    {
        for (const callback of pvt.eventListener[ type ])
        {// Processing each entry
            // (Calling the function)
            pvt.eventListener[ type ]( data );
        }
    }



    export let id  = null;
    export let api = null;

    export let name           = null;
    export let placeholder    = null;
    export let required       = null;
    export let generateButton = false;
    export let strengthMeter  = false;



    $:
        if ( self.element )
        {// Value found
            // (Getting the value)
            api = self;
        }



    $:
        if ( self.inputElement )
        {// Value found
            // (Listening for the event)
            self.inputElement.addEventListener('input', function (event) {
                // (Getting the values)
                pvt.currentPassword.rank = self.rank( this.value );

                let thresholdIndex = Math.floor( ( pvt.currentPassword.rank * self.strengthMeter.thresholds.length ) / 100 );
                thresholdIndex     = thresholdIndex === self.strengthMeter.thresholds.length ? self.strengthMeter.thresholds.length - 1 : thresholdIndex;

                pvt.currentPassword.threshold = self.strengthMeter.thresholds[ thresholdIndex ];
                pvt.currentPassword.progressDescription = `${ pvt.currentPassword.threshold.description } ( ${ Math.floor( self.absRank( this.value ) ) } bits )`;
            });
        }

</script>

{ #if pvt.visible }
    <div class="swg swg-passwordfield { $$restProps.class }" id={ id } bind:this={ self.element }>
        <div class="passwordfield-main input-group mb-3">
            <input type="{ self.passwordVisible ? 'text' : 'password' }" class="form-control input" name="{ name }" placeholder="{ placeholder }" data-required={ required ? 'true' : null } bind:this={ self.inputElement }>
            <div class="input-group-append d-flex">
                { #if generateButton }
                    <button type="button" class="btn btn-secondary passwordfield-button" value="generate" title="generate"
                        on:click={ () => { self.inputElement.value = self.generatePassword(); self.inputElement.dispatchEvent( new Event('input') ); } }
                    >
                        <i class="fa-solid fa-dice"></i>
                    </button>
                { /if }
    
                <button type="button" class="btn btn-secondary passwordfield-button" value="toggle" title="{ self.passwordVisible ? 'hide' : 'show' }" style="width: 46px;"
                    on:click={ () => { self.passwordVisible = !self.passwordVisible; } }
                >
                    { #if self.passwordVisible }
                        <span class="passwordfield-button-state" data-value="password">
                            <i class="fa-solid fa-eye-slash"></i>
                        </span>
                    { :else }
                        <span class="passwordfield-button-state" data-value="text">
                            <i class="fa-solid fa-eye"></i>
                        </span>
                    { /if }
                </button>
            </div>
        </div>

        { #if strengthMeter }
            <div class="passwordfield-strengthmeter">
                <div class="progress-bar">
                    <div class="progress-value" style="width: { pvt.currentPassword.rank + '%' }; background-color: { pvt.currentPassword.threshold.color };">{ Math.floor( pvt.currentPassword.rank ) + ' %' }</div>
                </div>
                <div class="progress-description">{ pvt.currentPassword.progressDescription }</div>
            </div>
        { /if }
    </div>
{ /if }

<style>

    .swg.swg-passwordfield
    {
        
    }



    .passwordfield-strengthmeter
    {

    }

    .passwordfield-strengthmeter .progress-value
    {
        font-size: 10px;
        font-weight: 400;
        color: #ffffff;
        border-radius: 4px;

        transition: .2s all ease-in-out;
    }

    .passwordfield-strengthmeter .progress-description
    {
        font-size: 10px;
        font-weight: 700;
    }

</style>