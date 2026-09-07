<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="author" content="John Antonio">
    <meta name="keywords" content="<?= isset($metaKeywords) ? esc( $metaKeywords ) : '' ?>">
    <meta name="description" content="<?= isset($metaDescription) ? esc( $metaDescription ) : '' ?>">

    <?= $this->renderSection('head') ?>

    <?php if( has_site_favicon() ): ?>
    <link rel="shortcut icon" href="<?= site_favicon() ?>" type="image/x-icon">
    <link rel="icon" href="<?= site_favicon() ?>" type="image/x-icon">
    <?php endif; ?>
    
    <link href="<?= theme_assets('/fontawesome/css/all.css') ?>" rel="stylesheet" />

    <link href="<?= theme_assets('/css/template.min.css?v=1.2') ?>" rel="stylesheet" />
    <link rel="stylesheet" href="<?= theme_assets('/css/custom.css?v=20260906') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous">
    
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <?php if( service('auth')->isLogged() ){ ?>
        <link href="<?= admin_assets('/css/admin-bar.css?v=1.2') ?>" rel="stylesheet">
    <?php } ?>

    <title> <?php echo isset( $title ) ? esc( get_page_title( $title ) ) : ''  ?> </title>

    <!-- header custom codes-->
    <?= header_custom_codes() ?>

</head>


<body  class="dark-mode with-custom-webkit-scrollbars with-custom-css-scrollbars overflow-x-hidden

<?= service('auth')->isLogged() ? 'with-admin-bar1' : '' ?> " data-dm-shortcut-enabled="true" data-sidebar-shortcut-enabled="true" data-new-gr-c-s-check-loaded="14.1008.0" data-gr-ext-installed="">
<!-- admin bar-->

<!-- Page wrapper -->
<div id="page-wrapper" class="page-wrapper with-navbar  with-transitions <?= ! sidebar_disabled() ? 'with-sidebar' : '' ?>" data-sidebar-type="default">

    <!-- Sticky alerts -->
    <div class="sticky-alerts"></div>

    <!-- Navbar start -->
    <?= $this->include( theme_path('__layout/navbar') ) ?>
    <!-- Navbar end -->

    <!-- Sidebar start -->
    <div class="sidebar-overlay" onclick="halfmoon.toggleSidebar()"></div>
    <?= $this->include( theme_path('__layout/sidebar') ) ?>
    <!-- Sidebar end -->
