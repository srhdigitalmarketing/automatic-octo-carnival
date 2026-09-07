<div class="x_panel">

    <div class="x_content">

        <div class="form-group">
            <?= form_label('Title *:') ?>
            <?= form_input( [
                'name' => 'title',
                'value' => old('title', $page->title),
                'class' => 'form-control'
            ] ) ?>
        </div>

        <div class="form-group">
            <?= form_label('Custom slug :') ?>
            <?= form_input( [
                'name' => 'slug',
                'value' => old('slug', $page->slug),
                'class' => 'form-control'
            ] ) ?>
        </div>

        <div class="form-group">
            <?= form_label('Content :') ?>
            <?= form_textarea( [
                'name' => 'content',
                'class' => 'form-control summernote',
                'rows' => 8
            ], old('content', $page->content ?? '' )) ?>
        </div>


    </div>
</div>
