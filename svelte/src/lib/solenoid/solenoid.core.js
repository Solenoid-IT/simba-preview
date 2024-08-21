import { P_APP_ID, P_APP_NAME, P_APP_VERSION, P_APP_BUILD_TIME, P_DEV_SID, P_BE_HOST, P_BE_TYPE } from '$env/static/public';

import { goto } from '$app/navigation';



const inst = {};



inst.envs =
{
    'APP_ID':         P_APP_ID,
    'APP_NAME':       P_APP_NAME,
    'APP_VERSION':    P_APP_VERSION,
    'APP_BUILD_TIME': P_APP_BUILD_TIME,

    'DEV_SID':        P_DEV_SID,

    'BE_HOST':        P_BE_HOST,
    'BE_TYPE':        P_BE_TYPE,

    'ENV_TYPE':       import.meta.env.MODE === 'development' ? 'dev' : 'prod'
}
;



// Returns [string]
inst.asset = function (url)
{
    // Returning the value
    return ( url.at(0) === '/' ? `https://${ inst.envs.BE_HOST }` : '' ) + url + ( inst.envs.ENV_TYPE === 'dev' ? '?ts=' + new Date().valueOf() : `?v=${ inst.envs.APP_VERSION }` );
}
;



// (Setting the value)
inst.URL = {};



// Returns [string]
inst.URL.build = function (url)
{
    // Returning the value
    return ( url.at(0) === '/' ? `https://${ inst.envs.BE_HOST }` : '' ) + url;
}
;

// Returns [void]
inst.URL.open = function (url)
{
    // (Setting the location)
    window.location.href = inst.URL.build(url);
}
;



// Returns [void]
inst.navigate = function (url, replaceState)
{
    if ( typeof replaceState === 'undefined' ) replaceState = false;

    // (Going to the URL)
    goto( url, replaceState );
}
;



export const Core = inst;