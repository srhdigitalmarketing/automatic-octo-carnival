<!doctype html>
<html lang="en">
<head>
<meta name="robots" content="noindex, nofollow, noimageindex, nosnippet">
    <meta charset="UTF-8">
    <?php if (player_cdn_enabled()): ?><link rel="preconnect" href="https://<?= esc(player_cdn_hostname(), 'attr') ?>" crossorigin><?php endif ?>
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <?php if( has_site_favicon() ): ?>
        <link rel="shortcut icon" href="<?= site_favicon() ?>" type="image/x-icon">
        <link rel="icon" href="<?= site_favicon() ?>" type="image/x-icon">
    <?php endif; ?>

    <link href="<?= player_cdn_asset('/css/template.min.css?v=1.2') ?>" onerror="this.onerror=null;this.href='<?= esc(theme_assets('/css/template.min.css?v=1.2'), 'attr') ?>';" rel="stylesheet" />
    <link rel="stylesheet" href="<?= player_cdn_asset('/css/custom.css?v=20260906-2') ?>" onerror="this.onerror=null;this.href='<?= esc(theme_assets('/css/custom.css?v=20260906-2'), 'attr') ?>';">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous">
    
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>


    <title><?= ! empty($links) ? $movie->getMovieTitle() : 'video not found' ?> </title>
    <?php
    $playerButtonColor = get_config('player_button_color');
    $playerButtonColor = is_string($playerButtonColor) && preg_match('/^#[0-9a-f]{6}$/i', $playerButtonColor) ? $playerButtonColor : '#d28a15';
    $playerIconColor = get_config('player_icon_color');
    $playerIconColor = is_string($playerIconColor) && preg_match('/^#[0-9a-f]{6}$/i', $playerIconColor) ? $playerIconColor : '#ffffff';
    $playerButtonStyle = get_config('player_button_style');
    $playerButtonStyle = in_array($playerButtonStyle, ['solid', 'outline'], true) ? $playerButtonStyle : 'solid';
    $playerButtonSize = (int) get_config('player_button_size');
    $playerButtonSize = $playerButtonSize >= 48 && $playerButtonSize <= 140 ? $playerButtonSize : 88;
    $playerIconMap = ['play' => 'fa-play', 'play-circle' => 'fa-play-circle', 'film' => 'fa-film', 'bolt' => 'fa-bolt'];
    $playerIcon = get_config('player_button_icon');
    $playerIconClass = $playerIconMap[$playerIcon] ?? $playerIconMap['play'];
    ?>
    <style>
        body{
            background-color: var(--dm-card-bg-color) !important;
        }
        #embed-player {
            --player-button-color: <?= esc($playerButtonColor) ?>;
            --player-icon-color: <?= esc($playerIconColor) ?>;
            --player-button-size: <?= $playerButtonSize ?>px;
            --player-icon-size: <?= (int) round($playerButtonSize * .38) ?>px;
        }
    </style>

    <!-- header custom codes-->
    <?= header_custom_codes() ?>

</head>
<body  class="dark-mode overflow-y-hidden">
    <?php if(! empty($links)): ?>
    <?php reset($links); $initialLinkId = key($links); ?>
    <div id="embed-player" data-movie-id="<?= encode_id( $movie->id ) ?>">
    <div class="sticky-alerts bottom-0 top-auto mb-15"></div>
    <div id="servers" class="d-none" data-initial-id="<?= esc((string) $initialLinkId) ?>"></div>
  
        <div class="main-content">
            <div class="cover" style="background: linear-gradient( rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3) ), url(<?= banner_uri(
                $movie->banner
            ) ?>);"></div>
            <div class="play-btn" data-player-style="<?= esc($playerButtonStyle) ?>" onclick="Player.play()" role="button" tabindex="0" aria-label="Play video">
            <i class="fa <?= esc($playerIconClass) ?>" aria-hidden="true"></i>
            </div>
            <div class="frame">
                <iframe id="ve-iframe"   width="100%" scrolling="no" allowfullscreen="true" frameborder="0"></iframe>
            </div>
            <div class="loader">
            <div class="lds-ripple"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
            <div class="ve-text">
            <?= lang('Embed.please_wait') ?>
            </div>
            </div>
            <div class="error">
                <span class="lbl font-size-14"> <?= lang('Embed.unknown_error_occurred') ?> </span>
                <span class="msg"></span>
            </div>
            <div class="g-recaptcha" data-sitekey="<?= esc( get_config('gcaptcha_site_key') ) ?>"
                 data-badge="inline" data-size="invisible" data-callback="set_captcha_response"></div>

        </div>
    </div>

<?php else: ?>

    <div class="movie-not-found">
        <div class="img-wrap text-center">
            <img src="<?= player_cdn_asset('/images/icons/cat.png') ?>" onerror="this.onerror=null;this.src='<?= esc(theme_assets('/images/icons/cat.png'), 'attr') ?>';" class="w-100" alt="">
            <h3 class="font-size-24 text-muted">
                <?php if( $serverNotFound ){
                    echo lang('Embed.server_not_found');
                }else if(empty( $movie )){
                    echo lang('Embed.movie_not_found');
                }else {
                    echo lang('Embed.streaming_links_not_found');
                } ?>
            </h3>
            <hr>
            <?php if( $serverNotFound ): ?>
                <a href="<?= $movie->getEmbedLink(true) ?>?load-server=1"> <?= lang('Embed.load_another_server') ?> </a>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

<script> const BASE_URL = '<?= site_url() ?>'; </script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

<?php if( get_config('is_stream_gcaptcha_enabled') ): ?>
    <script  src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<script src="<?= player_cdn_asset('js/template.min.js?v=1.2') ?>" onerror="this.onerror=null;this.src='<?= esc(theme_assets('js/template.min.js?v=1.2'), 'attr') ?>';"></script>
<script src="<?= player_cdn_asset('js/custom.min.js?v=1.2') ?>" onerror="this.onerror=null;this.src='<?= esc(theme_assets('js/custom.min.js?v=1.2'), 'attr') ?>';"></script>
<script src="<?= player_cdn_asset('js/player.js?v=20260909-1') ?>" onerror="this.onerror=null;this.src='<?= esc(theme_assets('js/player.js?v=20260909-1'), 'attr') ?>';"></script>
<script src="<?= player_cdn_asset('js/player-guard.js?v=20260908-1') ?>" onerror="this.onerror=null;this.src='<?= esc(theme_assets('js/player-guard.js?v=20260908-1'), 'attr') ?>';"></script>

<!--footer custom codes-->
<?= footer_custom_codes () ?>

<!--popAds-->
<?php if(isset( $ads )) {
    echo display_pop_ad( $ads, $popupAdUnits ?? [] );
}  ?>

</body>
</html>
