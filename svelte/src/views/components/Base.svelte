<script>

    import Sidebar from '../components/Sidebar.svelte';
    import Footer from '../components/Footer.svelte';
    import Header from '../components/Header.svelte';

    import { user } from '../../stores/user.js';

</script>

{ #if $user }
    <!-- Page Wrapper -->
    <div id="wrapper">
        <Sidebar/>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">
                <Header/>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <slot/>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <Footer/>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
{ /if }