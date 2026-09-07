<?php $isExist = $data['is_exist'] ?? false;  ?>

<div class="card movie-card p-0 border-0 mb-10 <?= $isExist ? 'selected' : '' ?> " data-tmdb="<?= $data['tmdb_id'] ?>">
    <div class="front">
        <div class="poster-img lazy"
             data-bg-multi="linear-gradient( rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3) ), url(<?= $data['poster'] ?>)">
        </div>
    </div>
    <div class="back">
        <p class="title mt-0"> <?= esc( $data['title'] ) ?> </p>
        <div class="btn-list">
            <?php if(! $isExist): ?>
            <a href="javascript:void(0)" class="btn select-btn" onclick="Requests.select(this)">
                <?= lang('Request.movie_select') ?>
            </a>
            <?php else: ?>
                <a href="<?= site_url(view_slug() . '/' . $data['tmdb_id'])  ?>" class="btn">
                    <?= lang('Request.movie_view') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>