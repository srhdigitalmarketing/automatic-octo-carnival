<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="x_panel">

    <div class="x_content">



        <table id="datatable" class="table text-center table-striped table-bordered data-list-table" style="width:100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Type</th>
                <th>Requests</th>
                <th>Subscribes</th>
                <th>Status</th>
                <th>Last Request At</th>
                <th></th>

            </tr>
            </thead>


            <tbody>

            <?php foreach ($requests as $k => $request) : ?>
                <tr>
                    <td> <?= $k+1 ?> </td>
                    <td class="text-left"> <b> <?= esc( $request->title ) ?> </b> </td>
                    <td> <?= $request->type ?> </td>
                    <td>
                        <a href="javascript:void(0)">
                            <?= $request->requests ?>
                        </a>
                    </td>
                    <td>
                        <a href="javascript:void(0)" class="font-weight-bold">
                            <?= $request->subscribes ?>
                        </a>
                    </td>
                    <td>
                        <?php switch ($request->status){
                            case 'pending':
                                echo '<span class="badge badge-warning"> pending </span>';
                                break;
                            case 'imported':
                                echo '<span class="badge badge-success"> imported </span>';
                                break;
                            default:
                                echo '<span class="badge"> canceled </span>';
                        } ?>

                    </td>
                    <td> <?= $request->updated_at ?> </td>
                    <td>
                        <?php
                        if(! $request->isImported()){
                            $type = $request->type == 'movie' ? 'movies' : 'series' ;
                            echo anchor("/admin/{$type}/new?tmdb={$request->tmdb_id}", 'Import', [
                                'class' => 'text-info'
                            ]);
                        }else{
                            echo anchor(view_slug() . "/" . $request->tmdb_id , 'View', [
                                'class' => 'text-info',
                                'target' => '_blank'
                            ]);
                        }

                        ?>
                        <a href="<?= admin_url("/requests/subs/{$request->id}") ?>" class="ml-2" target="_blank" >Subs</a>
                        <a href="javascript:void(0)" class="text-danger ml-2 del-item" data-url="<?= admin_url("/requests/delete/{$request->id}") ?>">Del</a>


                    </td>
                </tr>
            <?php endforeach; ?>







            </tbody>
        </table>





    </div>
</div>

<?php $this->endSection() ?>
