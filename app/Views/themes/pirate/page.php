<?= $this->extend( theme_path('__layout/base') ) ?>



<?= $this->section("content") ?>

<div class="container-fluid">
    <div class="row align-items-center">
    <div class="col">
    <div class="section-title spacer_- library">
    <h2><?= esc( $title ) ?></h2>
    </div>
    </div>
    </div>
    <div class="content library_space">
    <div class="row">
    <div class="col">
    <p class="text-justify"><?= $page->content ?></p>
    </div>
    </div>
    </div>
    </div>

<?= $this->endSection() ?>
