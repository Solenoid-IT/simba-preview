<script>

    import Header from '../components/Header.svelte';
    import Sidebar from '../components/Sidebar.svelte';
    import Footer from '../components/Footer.svelte';
    
    import ActivityStack from './ActivityStack.svelte';

</script>

<Header/>

<div class="sb-nav-fixed">
    <div id="layoutSidenav">
        <Sidebar/>
    
        <div id="layoutSidenav_content">
            <main class="page-main">
                <div class="container-fluid px-4">
                    <slot name="body"/>
                </div>

                <ActivityStack/>
            </main>
    
            <Footer/>
        </div>
    </div>
</div>

<style>

    @media screen and (max-width: 860px)
    {
        :global( .row.resp )
        {
            flex-direction: column;
        }
    }

</style>