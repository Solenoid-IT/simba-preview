/*import { readable } from 'svelte/store';

export const store = readable( null );

async function fetchData ()
{
    // (Getting the value)
    store.set( await ( await fetch( '/app', { 'method': 'RPC', 'headers': { 'Action': 'get_version' } } ) ).json() );
}
*/



import { writable } from 'svelte/store';

export const appMetadata = writable( null );