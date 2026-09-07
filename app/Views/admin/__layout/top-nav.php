<?php $adminUser = get_admin_user(); ?>
<div class="top_nav">
    <div class="nav_menu admin-topbar">
        <div class="nav toggle admin-topbar__toggle">
            <a id="menu_toggle" href="javascript:;" aria-label="Toggle navigation"><i class="fa fa-bars"></i></a>
        </div>
        <div class="admin-topbar__greeting">
            <strong>Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= esc($adminUser->display_name ?? 'Admin') ?>!</strong>
        </div>
        <nav class="admin-topbar__actions" aria-label="Quick actions">
            <a class="admin-topbar__action" href="<?= site_url('/home') ?>" target="_blank" title="View site" aria-label="View site">
                <i class="fa fa-globe" aria-hidden="true"></i>
            </a>
            <span class="admin-topbar__action" title="Admin workspace" aria-hidden="true">
                <i class="fa fa-sun-o"></i>
            </span>
            <span class="admin-topbar__action" title="Notifications" aria-hidden="true">
                <i class="fa fa-bell-o"></i>
            </span>
            <div class="dropdown admin-topbar__profile">
                <a href="javascript:;" class="user-profile dropdown-toggle" aria-haspopup="true" id="navbarDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= esc($adminUser->display_name ?? 'Admin') ?> menu">
                    <img src="<?= site_url('/admin-assets/images/avatar.jpg') ?>" alt="">
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-usermenu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="<?= admin_url('/settings/profile') ?>">Profile</a>
                    <a class="dropdown-item" href="<?= admin_url('/logout') ?>"><i class="fa fa-sign-out"></i> Log Out</a>
                </div>
            </div>
        </nav>
    </div>
</div>
