

<div class="ve-admin--bar">
        <ul class="ve-left">
            <li><a href="<?= admin_url('/dashboard') ?>" class="ve-link">
                    <img src="<?= admin_assets('/images/svg-icons/dashboard.svg') ?>" height="15" alt="">&nbsp;
                    Dashboard </a> </li>

            <li class="ve-dropdown">
                <a href="javascript:void(0)" class="ve-link ">
                    <img src="<?= admin_assets('/images/svg-icons/plus.svg') ?>" height="13" alt="">&nbsp;
                    New</a>
                <ul>
                    <li><a href="<?= admin_url('/movies/new') ?>" class="ve-sub--link"> Movie </a> </li>
                    <li><a href="<?= admin_url('/episodes/new') ?>" class="ve-sub--link"> Episode </a></li>
                    <li><a href="<?= admin_url('/series/new') ?>" class="ve-sub--link"> TV Show </a></li>
                </ul>
            </li>

            <?php $cPage = current_url(true)->getSegment(1); ?>
            <?php if($cPage == view_slug() || $cPage == download_slug()):
                if(! empty( $activeMovie )):  ?>
                    <li>
                        <a href="<?= admin_url("/movies/edit/{$activeMovie->id}") ?>" class="ve-link">
                            <img src="<?= admin_assets('/images/svg-icons/edit.svg') ?>" height="15" alt="">&nbsp;
                            Edit
                        </a>
                    </li>
                <?php   endif;
            endif; ?>

            <li class="ve-dropdown">
                <a href="javascript:void(0)" class="ve-link ">
                    <img src="<?= admin_assets('/images/svg-icons/list.svg') ?>" height="15" alt="">&nbsp;
                    List</a>
                <ul>
                    <li><a href="<?= admin_url('/movies') ?>" class="ve-sub--link"> Movies </a> </li>
                    <li><a href="<?= admin_url('/episodes') ?>" class="ve-sub--link"> Episodes </a></li>
                    <li><a href="<?= admin_url('/series') ?>" class="ve-sub--link"> TV Shows </a></li>
                </ul>
            </li>
            <li>
                <a href="<?= admin_url('/settings/cache/clean') ?>" class="ve-link d-none d-lg-inline-block ">
                    <img src="<?= admin_assets('/images/svg-icons/clear.svg') ?>" height="18" alt="">&nbsp;
                    Purge Cache </a> </li>

        </ul>
        <ul class="ve-right">
            <li><a href="<?= admin_url('/logout?rd_back=1') ?>" class="ve-link">
                    <img src="<?= admin_assets('/images/svg-icons/logout.svg') ?>" height="13" alt="">&nbsp;
                    Logout</a> </li>
        </ul>

    </div>
