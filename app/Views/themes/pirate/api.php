    <?= $this->extend( theme_path('__layout/base') ) ?>

    <?= $this->section("content") ?>

    <div class="container-fluid">
    <div class="row align-items-center">
    <div class="col">
    <div class="section-title library">
    <h2>API Documentation</h2>
    <p>Usage API integration, For Your Movies Website</p>
    </div>
    </div>
    </div>
    <div class="row row-eq-spacing">
    <div class="col">  
    <h1 class="content-title mb-0">Get Movies API</h1>
    <div class="api-box-responsive">
    <div class="api-content-box1">
    <div class="text-capitalize">Using Imdb or Tmdb ID</div>
    <span class="badge badge-pill badge-primary">Method 1</span>
    <!--  li - method 1 -->
    <ul style="list-style: none">
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/<small class="text-muted">IMDB_ID</small></code>
        </li>
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/<small class="text-muted">TMDB_ID</small></code>
        </li>
    </ul>
    </div>
    <div class="api-content-box2">
    <div class="text-capitalize">Using Imdb or Tmdb ID</div>
    <span class="badge badge-pill badge-primary">Method 2</span>
    <ul style="list-style: none">
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/movie?imdb=<small class="text-muted">IMDB_ID</small></code>
        </li>
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/movie?tmdb=<small class="text-muted">TMDB_ID</small></code>
        </li>
    </ul>
    </div>
    </div>
    </div>
    </div>
    
    <div class="row row-eq-spacing">
    <div class="col">  
    <h1 class="content-title mb-0">Get TV Series API</h1>
    <div class="api-box-responsive">
    <div class="api-content-box1">
    <div class="text-capitalize">Using Imdb or Tmdb ID</div>
    <span class="badge badge-pill badge-primary">Method 1</span>
    <!--  li - method 1 -->
    <ul style="list-style: none">
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/<small class="text-muted">IMDB_ID</small></code>
        </li>
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/<small class="text-muted">TMDB_ID</small></code>
        </li>
    </ul>
    </div>
    <div class="api-content-box2">
    <div class="text-capitalize">Using Imdb or Tmdb ID</div>
    <span class="badge badge-pill badge-primary">Method 2</span>
    <ul style="list-style: none">
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/series?imdb=<small class="text-muted">IMDB_ID</small></code>
        </li>
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/series?tmdb=<small class="text-muted">TMDB_ID</small></code>
        </li>
    </ul>
    </div>
    </div>
    </div>
    </div>
    
    
    <div class="row row-eq-spacing">
    <div class="col">  
    <h1 class="content-title mb-0">Get Episodes API</h1>
    <div class="api-box-responsive">
    <div class="api-content-box1">
    <div class="text-capitalize">Using Imdb or Tmdb ID</div>
    <span class="badge badge-pill badge-primary">Method 1</span>
    <!--  li - method 1 -->
    <ul style="list-style: none">
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/<small class="text-muted">EPISODE_IMDB_ID</small></code>
        </li>
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/<small class="text-muted">IMDB_ID</small>/<small class="text-muted">SEASON</small>/<small class="text-muted">EPISODE</small></code>
        </li>
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/<small class="text-muted">TMDB_ID</small>/<small class="text-muted ">SEASON</small>/<small class="text-muted">EPISODE</small></code>
        </li>
    </ul>
    </div>
    <div class="api-content-box2">
    <div class="text-capitalize">Using Imdb or Tmdb ID</div>
    <span class="badge badge-pill badge-primary">Method 2</span>
    <ul style="list-style: none">
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/series?imdb=<small class="text-muted">IMDB_ID</small>&sea=<small class="text-muted">SEASON</small>&epi=<small class="text-muted">EPISODE</small></code>
        </li>
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <code><?= site_url( embed_slug() ) ?>/series?tmdb=<small class="text-muted">TMDB_ID</small>&sea=<small class="text-muted">SEASON</small>&epi=<small class="text-muted">EPISODE</small></code>
        </li>
        
    </ul>
    </div>
    </div>
    </div>
    </div>
    
    
    <div class="row row-eq-spacing">
    <div class="col">  
    <h1 class="content-title mb-0">Get Download Files API</h1>
    <div class="api-box-responsive">
    <div class="api-content-box1">
    <div class="text-capitalize">Using Imdb or Tmdb ID</div>
    <span class="badge badge-pill badge-primary">Method 1</span>
    <!--  li - method 1 -->
    <ul style="list-style: none">
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <b><?= lang('API.streaming_api_txt') ?> : </b>&nbsp;
        <?= site_url( embed_slug() ) ?>/ <small class="text-muted">IMDB_ID or TMDB_ID</small>
        </li>
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <b><?= lang('API.download_api_txt') ?> : </b>&nbsp;
        <?= site_url( download_slug() ) ?>/ <small class="text-muted">IMDB_ID or TMDB_ID</small>
        </li>
    </ul>
    </div>
    <div class="api-content-box2">
    <div class="text-capitalize"> Check Status API <i class='bx bxs-circle bx-flashing'style='color:#ffda00;font-size: 11px;'></i> <b>Using Imdb or Tmdb ID</b></div>
    <span class="badge badge-pill badge-primary">Method 2</span>
    <ul style="list-style: none">
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <b>Movie</b> : </br>
        <?= site_url('/api/status') ?><b>?imdb=</b><small class="text-muted">IMDB_ID</small><b>&type=</b><small class="text-muted">movie</small>
        </li>
        <li class="mb-5">
        <i class="bi bi-check-circle" aria-hidden="true"></i>&nbsp;
        <b>TV Series</b> : </br>
        <?= site_url('/api/status') ?><b>?imdb=</b><small class="text-muted">TV_SHOW_IMDB_ID</small>&sea=<small class="text-muted">SEASON</small>&epi=<small class="text-muted">EPISODE</small><b>&type=</b><small class="text-muted">movie</small>
        </li>
    </ul>
    </div>
    </div>
    </div>
    </div>
    </div>

<?= $this->endSection() ?>