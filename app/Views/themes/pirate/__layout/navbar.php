<nav class="navbar">


    <div class="navbar-content <?= sidebar_disabled() ? 'd-inline-block d-md-none' : '' ?>">
        <button id="toggle-sidebar-btn" class="btn btn-action" type="button" onclick="halfmoon.toggleSidebar()">
            <i class="fa fa-bars" aria-hidden="true"></i>
        </button>
    </div>
    <a href="<?= site_url() ?>" class="navbar-brand streamapilite-left">
        <?php if(! has_site_logo()): ?>
            <h1 class="ve-logo-text"> <?= esc( site_name() ) ?> </h1>
        <?php else: ?>
            <img src="<?= site_logo() ?>" class="h-20 h-sm-30" alt="">
        <?php endif; ?>
    </a>

    <?php $uri = http_uri(); ?>
    <!-- Navbar nav -->
    <ul class="navbar-nav d-none d-md-flex ml-0"> <!-- d-none = display: none, d-md-flex = display: flex on medium screens and up (width > 768px) -->
        <li class="nav-item <?php if( $uri->getPath() == '/' || $uri->getSegment(1) === 'home'  ) echo 'active' ?>   ">
            <a href="<?= site_url() ?>" class="nav-link font-weight-semi-bold"><i class="fa fa-home" aria-hidden="true"></i>&nbsp;
                <?= lang('TopNav.home') ?>
            </a>
        </li>

        <li class="nav-item <?php if($uri->getTotalSegments() >= 2 && $uri->getSegment(2) === 'movies'  ) echo 'active' ?>">
            <a href="<?= library_url() ?>" class="nav-link font-weight-semi-bold "><i class="fa fa-film" aria-hidden="true"></i>&nbsp;
                <?= lang('TopNav.videos') ?>
            </a>
        </li>

	<?php  if(! is_referer_blocked()): ?>
    <li class="nav-item <?php if( $uri->getSegment(1) === 'api'  ) echo 'active' ?>">
    <a href="<?= site_url('api') ?>" class="nav-link font-weight-semi-bold"><i class="bi bi-gear-wide-connected"></i>&nbsp;
    <?= lang('TopNav.api') ?>
    </a>
    </li>
	<?php endif; ?>
	</ul>
    <!-- Navbar nav -->
    
    </nav>
