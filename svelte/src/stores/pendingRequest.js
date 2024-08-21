import { writable } from 'svelte/store';

export const pendingRequest = writable( false );