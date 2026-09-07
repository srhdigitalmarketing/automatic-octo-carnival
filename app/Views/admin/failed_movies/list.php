<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>


<div class="alert alert-info">
    Users are tried to find these movies/episodes in your site but results not found
</div>
<div class="x_panel">

    <div class="x_content">



        <table id="datatable" class="table text-center table-striped table-bordered data-list-table" style="width:100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Imdb Id</th>
                <th>Type</th>
                <th>Requests</th>
                <th>Request At</th>
                <th></th>

            </tr>
            </thead>


            <tbody>

            <?php foreach ($movies as $movie) : ?>
                <tr>
                    <td> <?= $movie['id'] ?> </td>
                    <td class="text-left"> <b> <?= esc( $movie['title'] ) ?> </b> </td>
                    <td>
                        <a href="https://www.imdb.com/title/<?=$movie['imdb_id']?>" target="_blank">
                            <?= $movie['imdb_id'] ?>
                        </a>
                    </td>
                    <td> <?= $movie['type'] ?> </td>
                    <td> <?= $movie['requests'] ?> </td>
                    <td> <?= $movie['created_at'] ?> </td>
                    <td>
                        <?php
                            $type = $movie['type'];

                            if($type != 'series') {
                                $type .= 's';
                            }

                            $isHidden = $type == 'episodes' ? 'visibility: hidden;' : '';

                        echo anchor("/admin/{$type}/new?imdb={$movie['imdb_id']}", 'Import', [
                            'class' => 'text-info',
                            'style' => $isHidden
                        ]);


                        ?>
                        <a href="javascript:void(0)" class="text-danger ml-2 del-item" data-url="<?= admin_url("/next-for-you/delete?id={$movie['id']}") ?>">Del</a>


                    </td>
                </tr>
            <?php endforeach; ?>







            </tbody>
        </table>





    </div>
</div>

<?php $this->endSection() ?>
