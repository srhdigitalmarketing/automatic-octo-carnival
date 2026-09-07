<div class="x_panel">
    <div class="x_title">
        <h2>Video Quality</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group mb-0">
            <?php

            foreach (get_stream_quality_formats() as $streamQuality) {
                $radio  = '<label class="lead mr-3">';
                $radio .= form_radio([
                    'name' => 'quality',
                    'value' => $streamQuality,
                    'checked' => ( old('quality', $movie->quality) ==  $streamQuality )
                ]);
                $radio .= $streamQuality . '</label>';
                echo $radio;
            }

            ?>


        </div>

    </div>
</div>