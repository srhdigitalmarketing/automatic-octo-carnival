<?= $this->include( theme_path('__layout/header') ) ?>

    <!-- Content wrapper start -->
    <div class="content-wrapper overflow-x-hidden">
        <?= $this->renderSection('content') ?>
        <?= $this->include( theme_path('__layout/footer_inner') ) ?>
    </div>

<?= $this->renderSection('end-of-content') ?>

<?= $this->include( theme_path('__layout/footer') ) ?>
