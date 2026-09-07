<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-8">

        <div class="x_panel">

            <div class="x_content">

                <table id="datatable" class="table text-center table-striped table-bordered data-list-table" style="width:100%">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th></th>

                    </tr>
                    </thead>


                    <tbody>

                    <?php foreach ($pages as $k => $page) :  ?>

                        <tr>
                            <td> <?= $k + 1 ?> </td>
                            <td> <b> <?= esc( $page->title ) ?> </b> </td>
                            <td>
                                <?php if($page->isPublic()): ?>

                                    <span class="badge badge-success">public</span>

                                <?php else: ?>

                                    <span class="badge badge-secondary">draft</span>

                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= page_url($page->slug) ?>" target="_blank">View</a>
                                <a href="<?= admin_url("/pages/edit/{$page->id}") ?>" class="text-info ml-3">Edit</a>
                                <a href="javascript:void(0)" data-url="<?= admin_url("/pages/delete/{$page->id}") ?>" class="text-danger del-item ml-3">Del</a>
                            </td>
                        </tr>


                    <?php endforeach; ?>

                    </tbody>
                </table>





            </div>
        </div>

    </div>
</div>

<?php $this->endSection() ?>
