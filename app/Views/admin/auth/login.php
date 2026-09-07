<!DOCTYPE html>
<html
  lang="en"
  class="light-style customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> <?= esc( $title ?? '' ) ?> - <?= esc( site_name() ) ?> </title>
    
    <?php if( has_site_favicon() ): ?>
    <link rel="shortcut icon" href="<?= site_favicon() ?>" type="image/x-icon">
    <link rel="icon" href="<?= site_favicon() ?>" type="image/x-icon">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link href="<?= site_url('/admin-assets/vendor/css/core.css') ?>" rel="stylesheet">
    <link href="<?= site_url('/admin-assets/vendor/css/theme-default.css') ?>" rel="stylesheet">
    <link href="<?= site_url('/admin-assets/vendor/css/pages/page-auth.css') ?>" rel="stylesheet">
</head>
<body>
    <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
    
    <div class="card">
    <div class="card-body">
    <!-- Logo -->
    <div class="app-brand justify-content-center mb-3">
    <a href="<?= site_url() ?>" class="app-brand-link gap-2">
    <span class="app-brand-logo demo">
    <img src="<?= site_logo() ?>" width="auto" height="60" alt="">
    </span>
    </a>
    </div>
    <!-- /Logo -->
    <h4 class="mb-4 text-center">Please Login To Your Admin</h4>

    <?= form_open('/admin_login', ['method'=>'post']) ?>

    <?php if(session()->has('error')): ?>
    <div class="alert alert-danger">
    <span> <?= esc( session()->get('error') ) ?> </span>
    </div>
    <?php endif; ?>
    <div>
        
    <label for="email" class="form-label">Email or Username</label>     
    <div class="input-group mb-3">
    <span class="input-group-text" id="basic-addon1"><i class="bi bi-person"></i></span>
    <?= form_input([
    'name' => 'username',
    'class' => 'form-control',
    'placeholder' => 'Username',
    'required' => 'required'
    ]) ?>
    </div>
    <div>
        
    <label class="form-label" for="password">Password</label>    
    <div class="input-group mb-3">
    <span class="input-group-text" id="basic-addon1"><i class="bi bi-shield-lock"></i></span>
    <?= form_password([
    'name' => 'password',
    'class' => 'form-control',
    'placeholder' => 'Password',
    'required' => 'required'
    ]) ?>
    </div>
    <div>   
    <div>
    <?= form_button([
    'class' => 'btn btn-primary mb-3 d-grid w-100',
    'type' => 'submit'
    ], 'Sign') ?>
    </div>
    <?= form_close() ?>
    </div>
    </div>
</body>
</html>