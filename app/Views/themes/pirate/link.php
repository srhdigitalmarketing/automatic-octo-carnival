<?= $this->extend( theme_path('__layout/base') ) ?>

<?= $this->section("head") ?>

<meta data-name="token" content="<?= $token ?>">

<?= $this->endSection() ?>


<?= $this->section("content") ?>

<div class="container-fluid mb-20">

    <!-- row -->
    <div class="row">

        <!-- col-md-7 col-xl-6 -->
        <div class="col-md-7  col-xl-6 mx-auto">

            <!-- Server dest card -->
           <div class="card">
                <h3 class="font-weight-semi-bold font-size-16 my-0 "> 
                    <?= lang('Link.destination_server') ?> :
                    <span class="text-primary float-right">
                        <?= esc( $link->getHost() ); ?>
                    </span>
				</h3>
            </div>
            <!-- /. Server dest card -->

            <!-- leaderboard ad-->
            <?php if( has_display_banner_ad('link.banner.counter-top', $ads) ) {
                echo display_banner_ad('link.banner.counter-top', $ads);
            } ?>
            <!-- /. leaderboard ad-->

            <!-- Card -->
            <div class="card text-center">

                <!-- Errors -->
                <?php if( session()->has('error') ): ?>
                <div class="alert alert-danger mb-15" role="alert">
                    <?= esc(  session()->get('error') ) ?>
                </div>
                <?php endif; ?>
                <!-- /. Errors -->

                <!-- Form -->
                <?= form_open(link_slug() . '/get/' . encode_id( $link->id ), [
                    'method' => 'post',
                    'class' => 'link-out-form'
                ]) ?>

                    <h3 class="card-title mb-20 pb-10" style="<?= empty( get_config('dl_link_waiting_time') ) ? 'display:none' : ''; ?>">
                        <?= sprintf(lang('Link.waiting_msg'),
                            '<span id="dl-timer"  class="font-weight-semi-bold bg-primary d-inline-block px-10 text-center rounded text-dark"></span>') ?>.
                    </h3>

                    <!-- Captcha Form Group -->
                    <?php if( is_dl_captcha_enabled() ) {
                            the_math_captcha( true );
                    } ?>
                    <!-- /. Captcha Form Group -->

                    <!-- Security token -->
                    <div class="token"></div>
                    <!-- /. Security token -->

                    <button type="button" class="btn btn-primary font-weight-semi-bold submit mt-15" disabled="disabled">
                        <?= lang('Link.get_link_btn') ?>
                    </button>

                <?= form_close() ?>
                <!-- /. Form -->

            </div>
            <!-- /. Card -->

            <div class="content">
                <?= display_banner_ad('link.banner.counter-bottom', $ads) ?>
            </div>

        </div>
        <!-- /. col-md-7 col-xl-6 -->

    </div>
    <!-- /. row -->

</div>


<?= $this->endSection() ?>


<?= $this->section('scripts') ?>

<script src="<?= site_url('/vendors/countdown-timer/script.min.js') ?>"></script>

<script>

    const timerSec = <?= get_config('dl_link_waiting_time') ?>;
    let readyMsg = "<?= lang('Link.ready_to_get_msg') ?>";

</script>

<?= $this->endSection() ?>
