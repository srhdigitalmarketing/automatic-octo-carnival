
<!--search modal-->
<div class="modal modal-full ie-scroll-fix " id="search-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-transparent">
            <a href="javascript:void(0)" class="btn close" data-dismiss="modal" role="button" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </a>
            <div class="container">
                <div class="row">
                    <div class="col-md-8 offset-md-2">

                        <div class="form-group">
                            <input type="text" id="search-input" onkeyup="search.find()"  class="form-control border-0 bg-transparent" placeholder="<?= lang('TopNav.type_to_search') ?>" autofocus>
                        </div>

                            <div class="results-not-found text-muted font-size-18 ml-20" style="display: none">
                                <p><?= lang('TopNav.search_results_not_found') ?></p>
                            </div>
                            <div class="row row-eq-spacing m-5 " id="search-results"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--embed links modal-->
<div class="modal " id="embed-links-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content w-600">
            <a href="javascript:void(0)" class="btn close" data-dismiss="modal" role="button" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </a>
            <h5 class="modal-title">Pirates of the Caribbean: Dead Man's Chest</h5>


            <?php the_embed_links_group( $activeMovie ?? null ); ?>

        </div>
    </div>
</div>

<!--ads block detector-->
<div class="modal" id="ad-block-detected-modal" tabindex="-1" role="dialog" data-overlay-dismissal-disabled="true" data-esc-dismissal-disabled="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content text-center">
            <h5 class="modal-title">Adblock Detected !</h5>
            <p>
               Please disable adblock to use our site
            </p>
            <div class="mt-20"> <!-- text-right = text-align: right, mt-20 = margin-top: 2rem (20px) -->
                <a href="<?= current_url() ?>" class="btn btn-danger" >I've disabled adblock</a>
            </div>
        </div>
    </div>
</div>




<?php if( service('auth')->isLogged() ){
    echo $this->include( 'partials/admin_bar' );
} ?>


<script>

    const BASE_URL = '<?= esc( site_url() ) ?>';
    const EMBED_SLUG = '<?= esc( embed_slug() ) ?>';
    const DOWNLOAD_SLUG = '<?= esc( download_slug() ) ?>';
    const VIEW_SLUG = '<?= esc( view_slug() ) ?>';

    const isAdblockDetectorEnabled = '';

</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script  src="https://cdn.jsdelivr.net/npm/vanilla-lazyload@17.6.1/dist/lazyload.min.js"></script>

<?php $this->renderSection('scripts'); ?>

<script src="<?= theme_assets('js/template.min.js?v=1.2') ?>"></script>
<script src="<?= theme_assets('js/custom.min.js?v=1.2') ?>"></script>

<!--footer custom codes-->
<?= footer_custom_codes () ?>

<!--popAds-->
<?php if(isset( $ads )) {
    echo display_pop_ad( $ads );
}  ?>

<?php if(get_config('ad_block_detector') && ! service('auth')->isLogged()){ ?>
    <script>
        $(document).ready(function() {
            AdblockDetector.init();
        });
    </script>
<?php
} ?>


</body>
</html>
