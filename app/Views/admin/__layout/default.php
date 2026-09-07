<?= $this->include('admin/__layout//header') ?>

        <!-- left sidebar -->
        <?= $this->include('admin/__layout/sidebar') ?>
        <!-- /left sidebar -->

        <!-- top navigation -->
        <?= $this->include('admin/__layout/top-nav') ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
            <div class="admin-content-container">

                <?php if (empty($hidePageTitle)): ?>
                    <div class="page-title">
                        <div class="title_left">
                            <h3> <?= $title ?? 'Unknown Page' ?> </h3>
                        </div>
                        <div class="title_right text-right">
                            <?= $topBtnGroup ?? '' ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="clearfix"></div>

                <div class="alerts-list">
                    <?php displayAlerts(); ?>
                </div>

                <div class="clearfix"></div>

                <?= $this->renderSection('content') ?>

            </div>
        </div>
        <!-- /page content -->

<?= $this->include('admin/__layout/footer') ?>
