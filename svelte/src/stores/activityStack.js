import { writable } from 'svelte/store';



// Returns [object]
function createStore ()
{
    // (Getting the values)
    const store = writable( [] );

    // (Getting the value)
    const object =
    {
        'subscribe': store.subscribe,
        'set':       store.set,
        'update':    store.update,

        'push': function (activity)
        {
            // (Calling the function)
            store.update( function (value) { value.push( activity ); return value; } );
        },

        'remove': function (index)
        {
            // (Calling the function)
            store.update( function (value) { value.splice( index, 1 ); return value; } );
        }
    }
    ;



    // Returning the value
    return object;
}



export const activityStack = createStore();