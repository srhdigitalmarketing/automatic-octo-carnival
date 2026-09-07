<div class="col-md-3 left_col">
    <div class="left_col scroll-view">
        <div class="navbar nav_title" style=" background: #172d44;">
            <a href="<?= site_url('/admin') ?>" class="site_title logoadmin">
                <span class="brand-mark"><i class="fa fa-play"></i></span>
                <span class="brand-copy"><b>StreamAPI</b><small>VIDEO CONTROL CENTER</small></span>
            </a>
        </div>

        <div class="clearfix"></div>
        <!-- sidebar menu -->
        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
            <div class="menu_section">
                <ul class="nav side-menu">
                    <li class="sidebar-menu-label"><span>Workspace</span></li>
                    <li><a href="<?= admin_url('/dashboard') ?>"><i class="fa fa-dashboard"></i><span class="sidebar-item-label">Dashboard</span></a></li>

                    <li><a><i class="fa fa-play-circle"></i><span class="sidebar-item-label">Video</span><span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="<?= admin_url('/movies/new') ?>">Add Video</a></li>
                            <li><a href="<?= admin_url('/movies') ?>">All Videos</a></li>
                        </ul>
                    </li>

                    <li><a><i class="fa fa-dollar"></i><span class="sidebar-item-label">Advertisement</span><span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="<?= admin_url('/ads/embed_page') ?>">Embed page</a></li>
                        </ul>
                    </li>

                    <li><a><i class="fa fa-unlink"></i><span class="sidebar-item-label">Links</span><span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="<?= admin_url('/links') ?>">View All</a></li>
                            <li><a href="<?= admin_url('/links/reported') ?>">Reported</a></li>
                        </ul>
                    </li>

                    <li><a><i class="fa fa-cloud-upload"></i><span class="sidebar-item-label">R2 Storage</span><span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="<?= admin_url('/third-party-apis') ?>">All R2 Storage</a></li>
                            <li><a href="<?= admin_url('/third-party-apis/new') ?>">Add R2 Storage</a></li>
                        </ul>
                    </li>

                    <li class="sidebar-menu-label sidebar-menu-label--system"><span>System</span></li>
                    <li><a><i class="fa fa-gear"></i><span class="sidebar-item-label">Settings</span><span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                            <li><a href="<?= admin_url('/settings/site') ?>">Site</a></li>
                            <li><a href="<?= admin_url('/settings/profile') ?>">Profile</a></li>
                            <li><a href="<?= admin_url('/settings/general') ?>">General</a></li>
                            <li><a href="<?= admin_url('/settings/player') ?>">Player</a></li>
                            <li><a href="<?= admin_url('/settings/servers') ?>">Servers</a></li>
                            <li><a href="<?= admin_url('/settings/firewall') ?>">Firewall</a></li>
                            <li><a href="<?= admin_url('/settings/cache') ?>">Cache</a></li>
                            <li><a href="<?= admin_url('/settings/email') ?>">Email</a></li>
                            <li><a href="<?= admin_url('/settings/api') ?>">Dev API</a></li>
                            <li><a href="<?= admin_url('/settings/translations') ?>">Translations</a></li>
                        </ul>
                    </li>


                </ul>
            </div>
        </div>
        <!-- /sidebar menu -->

    </div>
</div>
