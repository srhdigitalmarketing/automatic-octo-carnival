<div class="x_panel">

    <div class="x_content">

        <table id="datatable" class="table text-center table-striped table-bordered data-list-table" style="width:100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Num Of movies</th>
                <th>Num Of Series</th>
                <th></th>

            </tr>
            </thead>


            <tbody>

                <?php foreach ($genres as $genre) :  ?>

                <tr>
                    <td> <?= $genre->id ?> </td>
                    <td> <b> <?= esc( $genre->name ) ?> </b> </td>
                    <td> <?= $genre->num_of_movies ?> </td>
                    <td> <?= $genre->num_of_series ?> </td>
                    <td>
                        <a href="<?= admin_url("/genres/edit/{$genre->id}") ?>" class="text-info">Edit</a>
                        <a href="<?= admin_url("/genres/delete/{$genre->id}") ?>" class="text-danger ml-2">Del</a>
                    </td>
                </tr>


                <?php endforeach; ?>

            </tbody>
        </table>





    </div>
</div>