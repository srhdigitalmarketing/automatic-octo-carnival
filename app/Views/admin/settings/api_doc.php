<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">

    <div class="col-lg-9">

        <div class="x_panel">
            <div class="x_title">
                <h2>Insert Movies</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <p>Insert single movie or multiple movies via Imdb ids</p>

                <div class="p-1 bg-light border">
                    <span class="font-weight-bold bg-dark text-light py-1 px-3 mr-2">GET</span>
                    <span class="font-weight-bold "> <?= site_url('/devapi/movie/create') ?> </span>
                </div>

                <table class="table table-bordered mt-3">
                    <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Description</th>
                        <th class="text-center">Required</th>
                    </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <span class="bg-light font-weight-bold border py-1 px-2">apikey</span>
                            </td>
                            <td>Your API Key</td>
                            <td class="text-center">
                                <span class="text-danger">yes</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="bg-light font-weight-bold border py-1 px-2">imdb</span>
                            </td>
                            <td>Single Imdb Id or Ids List</td>
                            <td class="text-center">
                                <span class="text-danger">yes</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <small>If you insert multiple IMDB ids,then separate each id by comma</small>

            </div>
        </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Insert TV Show</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <p>Insert single tv show via Imdb ids</p>

                <div class="p-1 bg-light border">
                    <span class="font-weight-bold bg-dark text-light py-1 px-3 mr-2">GET</span>
                    <span class="font-weight-bold "> <?= site_url('/devapi/series/create') ?> </span>
                </div>

                <table class="table table-bordered mt-3">
                    <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Description</th>
                        <th class="text-center">Required</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>
                            <span class="bg-light font-weight-bold border py-1 px-2">apikey</span>
                        </td>
                        <td>Your API Key</td>
                        <td class="text-center">
                            <span class="text-danger">yes</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="bg-light font-weight-bold border py-1 px-2">imdb</span>
                        </td>
                        <td>Single TV Show Imdb Id</td>
                        <td class="text-center">
                            <span class="text-danger">yes</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="bg-light font-weight-bold border py-1 px-2">sea</span>
                        </td>
                        <td>Season Number</td>
                        <td class="text-center">
                            <span class="">no</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="bg-light font-weight-bold border py-1 px-2">epi</span>
                        </td>
                        <td>Episode Number</td>
                        <td class="text-center">
                            <span class="">no</span>
                        </td>
                    </tr>
                    </tbody>
                </table>



            </div>
        </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Insert Links</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <p>Insert single links or multiple links via movies or episode imdb id</p>

                <div class="p-1 bg-light border">
                    <span class="font-weight-bold bg-dark text-light py-1 px-3 mr-2">GET</span>
                    <span class="font-weight-bold "> <?= site_url('/devapi/links/add') ?> </span>
                </div>

                <table class="table table-bordered mt-3">
                    <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Description</th>
                        <th class="text-center">Required</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>
                            <span class="bg-light font-weight-bold border py-1 px-2">apikey</span>
                        </td>
                        <td>Your API Key</td>
                        <td class="text-center">
                            <span class="text-danger">yes</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="bg-light font-weight-bold border py-1 px-2">imdb</span>
                        </td>
                        <td>Movie or Episode IMDB Id</td>
                        <td class="text-center">
                            <span class="text-danger">yes</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="bg-light font-weight-bold border py-1 px-2">links</span>
                        </td>
                        <td>Streaming or Download Links List</td>
                        <td class="text-center">
                            <span class="text-danger">yes</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="bg-light font-weight-bold border py-1 px-2">type</span>
                        </td>
                        <td>stream, direct_download, torrent_download</td>
                        <td class="text-center">
                            <span class="text-danger">yes</span>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <small>If you insert multiple links,then separate each link by comma</small>

            </div>
        </div>

    </div>

</div>

<?php $this->endSection() ?>
