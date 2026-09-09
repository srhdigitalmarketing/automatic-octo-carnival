<?php
$histatsCode = (string) get_config('histats_code');
$histatsScope = get_config('histats_scope') === 'public' ? 'public' : 'embed';
if (get_config('histats_enabled') && \App\Libraries\HistatsCode::isValid($histatsCode)
    && (($histatsContext ?? '') === 'embed' || ($histatsScope === 'public' && ($histatsContext ?? '') === 'public'))): ?>
<!-- HiStats: trusted administrator's official async counter code, kept intact. -->
<?= $histatsCode ?>
<?php endif ?>
