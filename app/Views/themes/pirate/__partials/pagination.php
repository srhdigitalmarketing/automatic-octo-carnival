<?php $pager->setSurroundCount(2) ?>

<nav aria-label="Page navigation">
    <ul class="pagination text-center">
        <?php if ($pager->hasPrevious()) : ?>
            <li class="page-item">
                <a href="<?= $pager->getFirst() ?>" class="page-link w-50" aria-label="first">
                    <span aria-hidden="true"> <?= lang('Pagination.first') ?> </span>
                </a>
            </li>
            <li class="page-item">
                <a href="<?= $pager->getPrevious() ?>" class="page-link" aria-label="prev">
                    <i class="fa fa-angle-left" aria-hidden="true"></i>
                    <span class="sr-only"> <?= lang('Pagination.previous') ?> </span>
                </a>
            </li>
        <?php endif ?>

        <?php foreach ($pager->links() as $link): ?>
            <li class="page-item <?= $link['active'] ? 'active' : '' ?>"  >
                <a href="<?= $link['uri'] ?>" class="page-link">
                    <?= $link['title'] ?>
                </a>
            </li>
        <?php endforeach ?>

        <?php if ($pager->hasNext()) : ?>
            <li class="page-item">
                <a href="<?= $pager->getNext() ?>" class="page-link" aria-label="next">
                    <i class="fa fa-angle-right" aria-hidden="true"></i>
                    <span class="sr-only"> <?= lang('Pagination.next') ?> </span>
                </a>
            </li>
            <li class="page-item">
                <a href="<?= $pager->getLast() ?>" class="page-link w-50">
                    <span aria-hidden="true">
                        <?= lang('Pagination.last') ?>
                    </span>
                </a>
            </li>
        <?php endif ?>
    </ul>
</nav>