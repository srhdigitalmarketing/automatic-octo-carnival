<?php
$isSaved = ! empty($movie->id);
$shortLink = $isSaved ? $movie->getEmbedLink(true) : '';
$fullLink = $isSaved ? $movie->getEmbedLink() : '';
$embedCode = $isSaved
    ? '<iframe id="ve-iframe" src="' . $fullLink . '" width="100%" height="100%" allowfullscreen="allowfullscreen" frameborder="0"></iframe>'
    : '';
?>

<div class="x_panel video-share-panel">
    <div class="x_title">
        <h2>Share Links</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <?php if (! $isSaved): ?>
            <div class="share-links-empty">
                <i class="fa fa-link"></i>
                <span>Save this video first to generate its direct links and embed code.</span>
            </div>
        <?php else: ?>
            <div class="share-link-tabs" role="tablist" aria-label="Video sharing options">
                <button type="button" class="share-link-tab active" data-share-tab="direct-links">
                    <i class="fa fa-link"></i> Direct Links
                </button>
                <button type="button" class="share-link-tab" data-share-tab="embed-code">
                    <i class="fa fa-code"></i> Embed Code
                </button>
            </div>

            <div class="share-link-content active" data-share-content="direct-links">
                <div class="form-group">
                    <label for="video-direct-link-short">Direct link 1</label>
                    <div class="input-group share-link-input">
                        <input id="video-direct-link-short" type="text" class="form-control" value="<?= esc($shortLink) ?>" readonly>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-light share-copy-button" data-copy-target="#video-direct-link-short" title="Copy direct link 1" aria-label="Copy direct link 1">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label for="video-direct-link-full">Direct link 2</label>
                    <div class="input-group share-link-input">
                        <input id="video-direct-link-full" type="text" class="form-control" value="<?= esc($fullLink) ?>" readonly>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-light share-copy-button" data-copy-target="#video-direct-link-full" title="Copy direct link 2" aria-label="Copy direct link 2">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="share-link-content" data-share-content="embed-code">
                <label for="video-embed-code">Embed code</label>
                <div class="share-embed-wrap">
                    <textarea id="video-embed-code" class="form-control" rows="5" readonly><?= esc($embedCode) ?></textarea>
                    <button type="button" class="btn btn-light share-copy-button" data-copy-target="#video-embed-code" title="Copy embed code" aria-label="Copy embed code">
                        <i class="fa fa-copy"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
if (!window.videoShareLinksReady) {
    window.videoShareLinksReady = true;
    document.addEventListener('click', function (event) {
        var tab = event.target.closest('[data-share-tab]');
        if (tab) {
            var panel = tab.closest('.video-share-panel');
            panel.querySelectorAll('[data-share-tab]').forEach(function (item) { item.classList.remove('active'); });
            panel.querySelectorAll('[data-share-content]').forEach(function (item) { item.classList.remove('active'); });
            tab.classList.add('active');
            panel.querySelector('[data-share-content="' + tab.dataset.shareTab + '"]').classList.add('active');
            return;
        }

        var copyButton = event.target.closest('[data-copy-target]');
        if (!copyButton) return;

        var field = document.querySelector(copyButton.dataset.copyTarget);
        if (!field) return;
        field.select();
        field.setSelectionRange(0, 99999);

        var copied = function () {
            var original = copyButton.innerHTML;
            copyButton.innerHTML = '<i class="fa fa-check"></i>';
            copyButton.classList.add('copied');
            window.setTimeout(function () {
                copyButton.innerHTML = original;
                copyButton.classList.remove('copied');
            }, 1400);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(field.value).then(copied);
        } else if (document.execCommand('copy')) {
            copied();
        }
    });
}
</script>
