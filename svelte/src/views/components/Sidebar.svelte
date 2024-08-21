<script>

    import { Core } from '../../lib/solenoid/solenoid.core.js';

    import { user } from '../../stores/user.js';



    const defaultProfileImageSrc = Core.asset( '/assets/images/user.png' );
    let userProfileImageSrc = null;

    $:
        if ( $user )
        {// Value found
            // (Getting the value)
            userProfileImageSrc = $user['profile']['photo'] ? `data:${ $user['profile']['photo']['type'] };base64,${ $user['profile']['photo']['content'] }` : defaultProfileImageSrc;
        }

</script>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Main</div>
                <!--<a class="nav-link" href="/admin" rel="external">
                    <div class="sb-nav-link-icon">
                        <i class="fa-solid fa-house"></i>
                    </div>
                    
                    Dashboard
                </a>-->
                <a class="nav-link" href="/admin">
                    <div class="sb-nav-link-icon">
                        <i class="fa-solid fa-house"></i>
                    </div>
                    
                    Dashboard
                </a>



                <div class="sb-sidenav-menu-heading">Objects</div>
                <a class="nav-link" href="/admin/docs">
                    <div class="sb-nav-link-icon">
                        <i class="fa-solid fa-file"></i>
                    </div>
                    
                    Documents
                </a>
                <a class="nav-link" href="/admin/tags">
                    <div class="sb-nav-link-icon">
                        <i class="fa-solid fa-tag"></i>
                    </div>
                    
                    Tags
                </a>
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <!--<div class="small">Logged in as:</div>-->

            <div class="row">
                <div class="col d-flex align-items-center">
                    <img class="profile-image me-3" src="{ userProfileImageSrc }" alt="">

                    <span title="{ $user['profile']['name'] } { $user['profile']['surname'] } <{ $user['email'] }>">
                        { $user['username'] }
                    </span>
                </div>
            </div>
        </div>
    </nav>
</div>