<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title> <?= esc( $title ?? '' ) ?> - <?= esc( site_name() ) ?> </title>

    <!-- Bootstrap 5.3 -->
    <link href="<?= site_url('/admin-assets/vendors/bootstrap5/css/bootstrap.min.css') ?>" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="<?= site_url('/admin-assets/vendors/font-awesome/css/font-awesome.min.css') ?>" rel="stylesheet">
    <!-- NProgress -->
    <link href="<?= site_url('/admin-assets/vendors/nprogress/nprogress.css') ?>" rel="stylesheet">
    <!-- Select2 -->
    <link href="<?= site_url('/admin-assets/vendors/select2/dist/css/select2.min.css') ?>" rel="stylesheet">


    <!-- Datatables -->
    <link href="<?= site_url('/admin-assets/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= site_url('/admin-assets/vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">

    <!-- summernote -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="<?= site_url('/admin-assets/css/custom.min.css?v=1.2') ?>" rel="stylesheet">
    <link href="<?= site_url('/admin-assets/css/bootstrap5-compat.css?v=20260906-01') ?>" rel="stylesheet">
    <link href="<?= site_url('/admin-assets/css/admin-polish.css?v=20260907-02') ?>" rel="stylesheet">
</head>

<body class="nav-md">


<div class="container body">
    <div class="main_container">
