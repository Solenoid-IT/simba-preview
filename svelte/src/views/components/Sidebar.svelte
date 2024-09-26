<svelte:window on:keydown={ onKeyDown }/>

<script>

    import { onMount } from 'svelte';
    import { envs } from '../../envs.js';


    let closed = false;



    // Returns [void]
    function toggleSidebar ()
    {
        // (Getting the value)
        closed = !closed;

        // (Setting the value)
        localStorage.setItem( 'sidebarClosed', JSON.stringify( closed ) );
    }

    // Returns [void]
    function onKeyDown (event)
    {
        if ( document.activeElement.classList.contains('input') ) return;

        switch ( event.key )
        {
            case 's':// (Sidebar controls)
                // (Calling the function)
                toggleSidebar();
            break;
        }
    }



    // (Listening for the event)
    onMount
    (
        function ()
        {
            // (Getting the value)
            closed = JSON.parse( localStorage.getItem('sidebarClosed') ?? false );
        }
    )
    ;

</script>

<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion { closed ? 'closed' : '' }" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <!--<a class="sidebar-brand d-flex align-items-center justify-content-center" href="/">
        <img src="{ envs.APP_URL }/assets/images/simba.jpg" alt="" class="app-logo">
        <div class="sidebar-brand-text mx-3">SIMBA</div>
    </a>-->

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="/admin/dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Modules
    </div>

    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages"
            aria-expanded="true" aria-controls="collapsePages">
            <i class="fas fa-fw fa-folder"></i>
            <span>Pages</span>
        </a>
        <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Login Screens:</h6>
                <a class="collapse-item" href="/admin/login">Login</a>
                <a class="collapse-item" href="#">Register</a>
                <a class="collapse-item" href="/admin/login">Forgot Password</a>
                <div class="collapse-divider"></div>
                <h6 class="collapse-header">Other Pages:</h6>
                <a class="collapse-item" href="/404">404 Page</a>
                <a class="collapse-item" href="#">Blank Page</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Charts -->
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="fas fa-fw fa-chart-area"></i>
            <span>Charts</span></a>
    </li>

    <!-- Nav Item - Tables -->
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="fas fa-fw fa-table"></i>
            <span>Tables</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle" on:click={ toggleSidebar } title="Sidebar ON/OFF (S)"></button>
    </div>

</ul>
<!-- End of Sidebar -->

<style>

    .sidebar
    {
        padding-top: 70px;
        /*background-color: #212529;*/
        background-color: var( --simba-dark );
    }

    .sidebar.closed
    {
        width: 0 !important;
    }

    .sidebar.closed #sidebarToggle
    {
        width: 18px;
        margin-left: 10px;
        position: fixed;
        left: 0;
        z-index: 9999;
        background-color: var( --simba-dark );
        color: #ffffff;
        border-radius: 4px !important;
    }

    .sidebar.closed #sidebarToggle::after
    {
        content: '\f105';
    }

</style>