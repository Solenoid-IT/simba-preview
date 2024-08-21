<script>

    import { createEventDispatcher, onMount } from 'svelte';

    const dispatch = createEventDispatcher();



    // (Setting the values)
    const pvt = {};
    const self = {};



    // (Setting the values)
    pvt.visible            = true;
    pvt.eventListener      = {};

    self.element           = null;



    // Returns [void]
    self.open = function ()
    {
        // (Showing the modal)
        jq(self.element).modal('show');
    }

    // Returns [void]
    self.close = function ()
    {
        // (Hiding the modal)
        jq(self.element).modal('hide');
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
        if ( typeof pvt.eventListener[ type ] === 'undefined' ) return;

        for (const callback of pvt.eventListener[ type ])
        {// Processing each entry
            // (Calling the function)
            callback( data );
        }
    }



    export let id    = null;
    export let width = null;
    export let api   = null;



    // (Listening for the event)
    onMount
    (
        function ()
        {
            // (Listening for the event)
            jq(self.element).on('hidden.bs.modal', function () {
                // (Triggering the event)
                dispatch('close');
            });
        }
    )
    ;



    // (Getting the value)
    api = self;

</script>

{ #if api }
    { #if pvt.visible }
        <div class="modal fade { $$restProps.class }" id="{ id }" tabindex="-1" role="dialog" aria-hidden="true" bind:this={ self.element }>
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: { width };">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <slot name="title"/>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" on:click={ () => { self.close(); } }>
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <slot name="body"/>
                </div>
            </div>
            </div>
        </div>
    { /if }
{ /if }

<style>

    .modal
    {
        
    }

</style>