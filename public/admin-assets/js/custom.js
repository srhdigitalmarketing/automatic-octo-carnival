(function($) {

    "use strict";

    $(document).ready(function() {

        init_bulk_import();
        init_links_groups();
        init_autoload();
        init_tv_shows_selection();
        init_data_list_datatable();
        init_discover();
        init_suggestion();
        init_banner_image_controls();
        init_host_api_provider_structure();
        init_host_api_connection_test();
        init_stream_poster();

        if($('.summernote').length > 0){
            $('.summernote').summernote({
                height: 300
            });
        }

    });


    // Table rows are loaded after the initial page render. Delegation keeps the
    // delete confirmation working for every newly loaded page of results.
    $(document).on('click', '.del-item', function (){
        let delConfirmModal = $('#del-confirm-modal');
        let url = $(this).attr('data-url');
        delConfirmModal.find('.del-link').attr('href', url);
        delConfirmModal.modal('show');
    });

    $(".select2_multiple, #select-genres").select2({

    });

    function init_bulk_import()
    {
        let summary = {
            'init_queried' : 0,
            'queried' : 0,
            'success' : 0,
            'exist' : 0,
            'failed' : 0
        };

        let type = 'movie';
        let imdbIds = [];
        let ajaxData = [];
        let links = [];

        let importBtn = null;
        let textarea = null;
        let isRunning = false;


        $(window).bind('beforeunload', function(){
            if( isRunning ){
                return 'Are you sure you want to leave?';
            }
        });

        function appendResults( data )
        {

            let listItem = '<li class="list-group-item py-1">\n' +
                '<b class="uniq_id"></b> - ' +
                '<span class="title"></span>' +
                '<a href="#" class="float-right edit-link" target="_blank">' +
                'Edit' +
                '</a>' +
                '</li>';

            let listItemError = '<li class="list-group-item py-1">\n' +
                '<b class="uniq_id text-danger"></b> - ' +
                '<span class="error"></span>' +
                '</li>';

            $.each(data, function(index, value) {

                if( value.success ) {

                    let item = $(listItem);
                    let edit_link = '/admin/' + value.type + '/edit/' + value.data.id;

                    item.find('.uniq_id').text( index );
                    item.find('.title').text( value.data.title );
                    item.find('.edit-link').attr( 'href', edit_link );

                    if(! value.is_exist ){
                        $("#success-list").append( item  );
                    }else{
                        $("#exist-list").append( item  );
                    }


                    if(value.type === 'series' ){

                        $.each(value.data.episodes, function(index, value) {
                            appendResults(value.episodes);
                        });

                    }

                    if(! value.is_exist ){
                        updateImportedLinks( value.data.imdb_id );
                    }


                }else{

                    let item = $(listItemError);
                    item.find('.uniq_id').text( index );
                    item.find('.error').text( value.error );

                    $("#failed-list").append( item  );
                }

                let status = '';
                if(value.success){
                    if(! value.is_exist){
                        status = 'success';
                    }else{
                        status = 'exist';
                    }
                }else{
                    status = 'failed';
                }
                summary[status] += 1;

                updateSummary();

            });





        }

        function updateImportedLinks( imdb_id )
        {
            let link = get_short_embed_link( imdb_id );
            links.push( link );

            $("#imported-links").text( links.join('\n') );

        }

        function updateSummary()
        {
            $('.num-of-success').text( summary.success );
            $('.num-of-exist').text( summary.exist );
            $('.num-of-failed').text( summary.failed );
        }

        function getQueriedIds()
        {
            let data = '';
            if(type === 'movies'){
                data = ajaxData.splice(0, 3);
                data = data.join(',');
            }else{
                data = ajaxData.shift();
            }
            return data;
        }

        function cleanIdsData()
        {
            let results = [];

            imdbIds.forEach(function (item, index) {
                results.push( item );
            });

            ajaxData = results;
        }

        function isQueryEmpty()
        {
            return ajaxData.length === 0;
        }

        function importMovie(  )
        {


            if(! isQueryEmpty()){

                $.ajax({
                    url : BASE_URL + '/ajax/import',
                    type: "GET",
                    headers: { 'X-Requested-With': 'XMLHttpRequest'},
                    data: {
                        'uniq_ids': getQueriedIds(),
                        'type' : type
                    },
                    dataType: "JSON",
                    success: function(data)
                    {

                        if(data.success) {
                            appendResults( data.data );
                        }else{
                            alert('something went wrong');
                        }

                    },
                    complete: function () {

                        importMovie();


                    },
                    error: function (jqXHR, textStatus, errorThrown)
                    {
                        alert('Error occurred');
                    }
                });

            }else{
                //done
                canceled();
            }



        }

        function canceled()
        {
            btn_loaded( importBtn );
            textarea.val( '' );
            textarea.removeAttr('disabled');
            isRunning = false;
        }

        function start()
        {
            isRunning = true;

            //lock button and textarea
            btn_loading( importBtn );
            textarea.attr('disabled', 'disabled');

            //update summary
            summary.queried = summary.init_queried = ajaxData.length;
            updateSummary();

            //start import movie
            importMovie();
        }

        $("#run-importer").on('click', function (){

            type = $('[name="type"]').val();
            textarea = $('#ids-list');
            importBtn = $(this);

            imdbIds = textarea.val().replace(/ /g, '');
            imdbIds = imdbIds.replace(/(\r\n\t\b|\n|\r|\t|\b)/gm,'').split(',');
            imdbIds = $.grep(imdbIds,function(n){ return n === 0 || n });


            if(imdbIds.length > 0) {
                cleanIdsData();
                start();
            }else{
                alert('Please enter imdb/ tmdb ids');
            }

        });


        $('#cancel-import-process').on('click', function (){

            if(ajaxData.length > 0) {
                let $this = $(this);
                btn_loading( $this );

                ajaxData = [];
                canceled();

                setTimeout(function (){
                    btn_loaded( $this );
                }, 2000)
            }

        });

    }

    function init_links_groups()
    {
        $(document).on('click', '.clone-st-group', function () {

            let clonedGroup = $(this).closest('.st-group').clone();
            let totalGroups = $('.st-group').length;
            let uniqId = totalGroups + 1;

            if(totalGroups > 0) {

                clonedGroup.find('.link').val('');
				clonedGroup.find('.link').removeAttr('readonly');
                clonedGroup.find('.link').attr('name', 'st_links['+ uniqId +'][url]');

                clonedGroup.find('.stream-priority').val('100');
                clonedGroup.find('.stream-priority').attr('name', 'st_links['+ uniqId +'][host_priority]');

                clonedGroup.find('.upnshare-video-id').val('');
                clonedGroup.find('.upnshare-video-id').attr('name', 'st_links['+ uniqId +'][upnshare_video_id]');

                clonedGroup.find('.stream-server-host').html('<i class="fa fa-server"></i> Host detected after saving');
                clonedGroup.find('.stream-server-badge')
                    .removeClass('is-healthy is-broken')
                    .addClass('is-unchecked')
                    .attr('title', 'Waiting for the first availability check')
                    .html('<i class="fa fa-clock-o"></i> Not checked');

                clonedGroup.find('input[type="hidden"]').remove();
                clonedGroup.find('.link-meta-info').remove();
                clonedGroup.find('label:first').text('Link ' + uniqId);

                $("#st-group-content").append( clonedGroup );

            }

        });

        $(document).on('click', '.clone-direct-dl-group', function () {

            let clonedGroup = $(this).closest('.direct-dl-group').clone();
            let totalGroups = $('.direct-dl-group').length;
            let uniqId = totalGroups + 1;

            if(clonedGroup.length > 0) {

                clonedGroup.find('.link').val('');
                clonedGroup.find('.link').attr('name', 'direct_dl_links['+ uniqId +'][url]');

                clonedGroup.find('.resolution').val('');
                clonedGroup.find('.resolution').attr('name', 'direct_dl_links['+ uniqId +'][resolution]');

                clonedGroup.find('.quality').val('');
                clonedGroup.find('.quality').attr('name', 'direct_dl_links['+ uniqId +'][quality]');

                clonedGroup.find('.size-val').val('');
                clonedGroup.find('.size-val').attr('name', 'direct_dl_links['+ uniqId +'][size_val]');

                clonedGroup.find('input[type="hidden"]').remove();
                clonedGroup.find('.link-meta-info').remove();
                clonedGroup.find('label:first').text('Link ' + uniqId);

                $("#direct-dl-group-content").append( clonedGroup );

            }
        });

        $(document).on('click', '.clone-torrent-dl-group', function () {

            let clonedGroup = $(this).closest('.torrent-dl-group').clone();
            let totalGroups = $('.torrent-dl-group').length;
            let uniqId = totalGroups + 1;

            if(clonedGroup.length > 0) {

                clonedGroup.find('.link').val('');
                clonedGroup.find('.link').attr('name', 'torrent_dl_links['+ uniqId +'][url]');

                clonedGroup.find('.resolution').val('');
                clonedGroup.find('.resolution').attr('name', 'torrent_dl_links['+ uniqId +'][resolution]');

                clonedGroup.find('.quality').val('');
                clonedGroup.find('.quality').attr('name', 'torrent_dl_links['+ uniqId +'][quality]');

                clonedGroup.find('.size-val').val('');
                clonedGroup.find('.size-val').attr('name', 'torrent_dl_links['+ uniqId +'][size_val]');

                clonedGroup.find('input[type="hidden"]').remove();
                clonedGroup.find('.link-meta-info').remove();
                clonedGroup.find('label:first').text('Link ' + uniqId);

                $("#torrent-dl-group-content").append( clonedGroup );

            }
        });
    }

    function init_autoload()
    {
        $("#load-tv-data").on('click', function (){

            let $this = $(this);

            let loadId = null;

            let imdb_id = $.trim( $('input[name="imdb_id"]').val() );
            let tmdb_id = $.trim( $('input[name="tmdb_id"]').val() );

            if(imdb_id) loadId = imdb_id;
            if(! imdb_id && tmdb_id) loadId = tmdb_id;


            if(loadId !== null){
                $.ajax({
                    url : BASE_URL + '/ajax/autoload/load_tv_data',
                    type: "GET",
                    headers: { 'X-Requested-With': 'XMLHttpRequest'},
                    data: {
                        'id' : loadId
                    },
                    dataType: "JSON",
                    beforeSend: function ()
                    {
                        btn_loading( $this );
                    },
                    success: function(data)
                    {

                        if(data.success) {

                            data = data.data;
                            let allowedInputFields = [
                                'title', 'imdb_id', 'tmdb_id', 'total_seasons', 'total_episodes', 'poster_url',
                                'banner_url', 'country', 'released_at', 'language', 'imdb_rate'
                            ];

                            appendInputData(data,  allowedInputFields );

                            let ff = 'option[value="'+data.status+'"]';
                            $(ff).prop('selected', true);

                            $('.poster-wrap').html( '<img src="' + data.poster_url + '" class="w-100 mb-2" alt="poster-image">' );
                            $('.banner-wrap').html( '<img src="' +  data.banner_url + '" class="w-100 mb-2" alt="banner-image">' );

                            update_genres( data.genres );
                            update_translations( data.translations );

                        }else{

                            alert( data.error );

                        }



                    },
                    error: function (jqXHR, textStatus, errorThrown)
                    {
                        alert('Error occurred');
                    },
                    complete: function ()
                    {
                        btn_loaded( $this );
                    }
                });
            }else{
                let msg = $('.autoload-msg').text();
                alert( msg );
            }

        });
        $("#load-episode-data").on('click', function () {

            let data = null;

            let imdbId = $.trim( $('input[name="imdb_id"]').val() );
            let seriesId = $.trim( $('select[name="series_id"]').val() );
            let season = $.trim( $('input[name="season"]').val() );
            let episode = $.trim( $('input[name="episode"]').val() );

            let $this = $(this);

            if(  seriesId  && season && episode ) {
                data = {
                    'series_id' : seriesId,
                    'season' : season,
                    'episode' : episode
                }
            }else if( imdbId ) {
                data = {
                    'imdb_id' : imdbId
                };
            }


            if(data !== null) {

                //attempt to load data
                $.ajax({
                    url : BASE_URL + '/ajax/autoload/load_episode_data',
                    type: "GET",
                    headers: { 'X-Requested-With': 'XMLHttpRequest'},
                    data: data,
                    dataType: "JSON",
                    beforeSend: function() {
                        btn_loading( $this );
                    },
                    success: function(data)
                    {


                        if(data.success) {

                            data = data.data;

                            let allowedInputFields = [
                                'title', 'imdb_id', 'tmdb_id', 'imdb_rate', 'duration', "season",
                                'episode', 'banner_url', 'released_at', 'trailer'
                            ];

                            for (let index = 0; index < allowedInputFields.length; ++index) {
                                let field = allowedInputFields[index];
                                let selector = 'input[name="'+ field +'"]';
                                $(selector).val( data[field] );
                            }

                            $('textarea[name="description"]').text( data.description );
                            $('.banner-wrap').html( '<img src="' +  data.banner_url + '" class="w-100 mb-2" alt="banner-image">' );

                            update_translations(data.translations);

                        }else{

                            alert( data.error );

                        }

                    },
                    error: function (jqXHR, textStatus, errorThrown)
                    {
                        alert('Error occurred');
                    },
                    complete: function() {
                        btn_loaded( $this );
                    },
                });

            }else{
                let msg = $('.autoload-msg').text();
                alert( msg );
            }






        });
        $("#load-movie-data").on('click', function () {

            let $this = $(this);

            let loadId = null;

            let imdb_id = $.trim( $('input[name="imdb_id"]').val() );
            let tmdb_id = $.trim( $('input[name="tmdb_id"]').val() );

            if(imdb_id) loadId = imdb_id;
            if(! imdb_id && tmdb_id) loadId = tmdb_id;

            if(loadId !== null){
                $.ajax({
                    url : BASE_URL + '/ajax/autoload/load_movie_data',
                    type: "GET",
                    headers: { 'X-Requested-With': 'XMLHttpRequest'},
                    data: {
                        'id' : loadId
                    },
                    dataType: "JSON",
                    beforeSend: function ()
                    {
                        btn_loading( $this );
                    },
                    success: function(data)
                    {

                        if(data.success) {

                            data = data.data;
                            let allowedInputFields= [
                                'title', 'imdb_id', 'tmdb_id', 'imdb_rate', 'duration', 'language',
                                'released_at', 'trailer', 'banner_url', 'country'
                            ];

                            for (let index = 0; index < allowedInputFields.length; ++index) {
                                let field = allowedInputFields[index];
                                let selector = 'input[name="'+ field +'"]';
                                $(selector).val( data[field] );
                            }

                            $('textarea[name="description"]').text( data.description );

                            $('.banner-wrap').html( '<img src="' +  data.banner_url + '" class="w-100 mb-2" alt="banner-image">' );

                            update_genres(data.genres);
                            update_translations(data.translations);

                        }else{

                            alert( data.error );

                        }



                    },
                    error: function (jqXHR, textStatus, errorThrown)
                    {
                        alert('Error occurred');
                    },
                    complete: function ()
                    {
                        btn_loaded( $this );
                    }
                });
            }else{
                let msg = $('.autoload-msg').text();
                alert( msg );
            }

        });

        function update_translations( translations )
        {

            if(translations !== null && translations.length > 0){

                translations.forEach(function (item, index) {

                    let titleSelector = '[name="translations['+ item['lang'] +'][title]"]';
                    let descSelector = '[name="translations['+ item['lang'] +'][description]"]';

                    if($(titleSelector).length > 0){

                        $(titleSelector).val( item['title'] );
                        $(descSelector).val( item['description'] );

                    }

                });

            }
        }

    }

    function init_discover()
    {
        let type = 'movies';
        let results = $("#results");
        let QueriedData = null;
        let loadbtn = null;
        let itemsSelectionPanel = $('#items-selected-panel');
        let selected = [];
        let totalPages = 0;

        $("#init-discover").on('click', function (){

            loadbtn = $(this);
            type = $(this).attr('data-type');

            //clear results
            results.html('');

            QueriedData = null;

            //discover
            discover();
        });

        $(".load-next-page").on('click',  function (){
            loadbtn = $(this);
            //discover
            discover();
        });

        $(".reset-selected-items").on('click', function (){
            selected = [];
            itemsSelectionPanel.hide();
            itemsSelectionPanel.find('.items-selected').text( 0 );
            $('.movie-item.new').removeClass('selected');
        });

        $(document).on('click', '.discover .select-all', function (){
            if($(this).attr('data-type') === 'select'){
                $(this).closest('.x_panel').find('.movie-item.new .cover:not(.movie-item.new.selected .cover)').trigger('click');
                $(this).attr('data-type', 'deselect');
            }else{
                $(this).closest('.x_panel').find('.movie-item.new .cover').trigger('click');
                $(this).attr('data-type', 'select');
            }

        });
        $(document).on('click', '.discover .movie-item.new .cover', function (){
            let movieItem = $(this).closest('.movie-item');
            movieItem.toggleClass('selected');

            let tmdb = movieItem.attr('data-tmdb');

            if(! movieItem.hasClass('selected')){
                let index = selected.indexOf( tmdb );
                if (index !== -1) {
                    selected.splice(index, 1);
                }
            }else{
              selected.push( tmdb );
            }

            if(selected.length > 0){
                itemsSelectionPanel.show();
                itemsSelectionPanel.find('.items-selected').text( selected.length );
                $('[name="ids"]').val( selected.join(',') );
            }else{
                itemsSelectionPanel.hide();
                itemsSelectionPanel.find('.items-selected').text( 0 );
                $('[name="ids"]').val('');
            }
        });


        function discover()
        {
            $.ajax({
                url : BASE_URL + '/ajax/discover',
                type: "GET",
                headers: { 'X-Requested-With': 'XMLHttpRequest'},
                data: getQueries(),
                dataType: "JSON",
                async: false,
                beforeSend: function ()
                {
                    btn_loading( loadbtn );
                },
                success: function( data )
                {

                    if(data.success) {
                        let innerData = data['data'];

                        updateResults( innerData.results );
                        updatePages( innerData.page, innerData.total_pages );

                    }else{



                    }

                },
                error: function (jqXHR, textStatus, errorThrown)
                {
                    alert('Error occurred');
                },
                complete: function ()
                {
                    let page = QueriedData.page - 1;

                    setTimeout(function (){
                        let panel_id = "#" + 'x_panel_' + page;
                        $(panel_id).show();
                        $('.next-page-btn-wrap').show();
                        btn_loaded( loadbtn );
                    }, 1500);
                }
            });
        }

        function getQueries()
        {
            if(QueriedData === null){
                let year = $('[name="year"]').val();
                let page = parseInt( $('[name="page"]').val() );
                let sort = $('[name="sort"]').val();
                let sort_dir = $('[name="sort_dir"]').val();
                let lang = $('[name="lang"]').val();
                let imported_filter = $('[name="imported_filter"]:checked').val();
                let genres = [];
                let status = '';
                let showType = '';

                if(type !== 'movies'){
                    status = $('[name="status"]').val();
                    showType = $('[name="type"]').val();
                }

                $("input:checkbox[name=\"genre\"]:checked").each(function(){
                    genres.push($(this).val());
                });

                genres = genres.join(',');

                QueriedData =  {
                    year: year,
                    status: status,
                    show_type: showType,
                    genres: genres,
                    lang: lang,
                    sort: sort,
                    sort_dir: sort_dir,
                    imported_filter: imported_filter,
                    page: page,
                    type: type
                }


            }

            return QueriedData;

        }

        function updateResults( results )
        {
            $("#results").append( results );
        }

        function updatePages(page, total_pages = 0)
        {
            $('[name="page"]').val(page);

            $('.total-pages').text( total_pages );
            $('.total-pages').closest('.input-group-append').show();

            totalPages = total_pages;

            if(! isFinished()){
                QueriedData.page += 1;
            }else{
                $('.load-next-page').hide();
            }
        }

        function isFinished()
        {
            return totalPages <= QueriedData.page;
        }


    }

    function init_tv_shows_selection()
    {
        $("#select-tv-show").on('change', function (){

            let seriesId = this.value;

            if(seriesId) {

                $.ajax({
                    url : BASE_URL + '/ajax/autoload/load_next_episode',
                    type: "GET",
                    headers: { 'X-Requested-With': 'XMLHttpRequest'},
                    data: {
                        'series_id' : seriesId
                    },
                    dataType: "JSON",
                    success: function(data)
                    {
                        //if success close modal and reload ajax table
                        if(data.success) {
                            let episodeData = data.data;
                            $('input[name="season"]').val( episodeData.nextSeason );
                            $('input[name="episode"]').val( episodeData.nextEpisode );
                        }

                    },
                    error: function (jqXHR, textStatus, errorThrown)
                    {
                        alert('Error occurred');
                    }
                });

            }


        });
    }

    function init_stream_poster()
    {
        let button = $('.stream-poster-fetch');
        let result = $('.stream-poster-result');

        if(button.length === 0 || result.length === 0){
            return;
        }

        button.on('click', function(){
            let movieId = parseInt(button.attr('data-movie-id'), 10) || 0;
            if(movieId < 1){
                return;
            }

            button.prop('disabled', true);
            button.find('.fa').removeClass('fa-image').addClass('fa-spinner fa-spin');
            result.empty();

            $.ajax({
                url: BASE_URL + '/ajax/stream-poster',
                type: 'GET',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                data: {movie_id: movieId},
                dataType: 'JSON',
                success: function(data){
                    if(! data.success || ! data.data || ! data.data.poster_url){
                        renderError(data.error || 'This host did not provide a usable image.');
                        return;
                    }

                    let posterUrl = data.data.poster_url;
                    $('input[name="banner_url"]').val(posterUrl).trigger('change');
                    $('.banner-wrap').empty().append(
                        $('<img>', {src: posterUrl, class: 'w-100 mb-2', alt: 'stream host banner preview'})
                    );
                    result.empty().append(
                        $('<div>', {class: 'stream-poster-result__success'}).append(
                            $('<i>', {class: 'fa fa-check-circle', 'aria-hidden': 'true'}),
                            document.createTextNode(' Image found via ' + (data.data.source || 'the stream host') + '. Save the video to use it as the banner.')
                        )
                    );
                },
                error: function(){
                    renderError('Could not reach the stream host. Please try again later.');
                },
                complete: function(){
                    button.prop('disabled', false);
                    button.find('.fa').removeClass('fa-spinner fa-spin').addClass('fa-image');
                }
            });
        });

        function renderError(message)
        {
            result.empty().append(
                $('<div>', {class: 'stream-poster-result__error'}).append(
                    $('<i>', {class: 'fa fa-exclamation-circle', 'aria-hidden': 'true'}),
                    document.createTextNode(' ' + message)
                )
            );
        }
    }

    function init_banner_image_controls()
    {
        let bannerInputs = $('input[name="banner_file"]');
        if(bannerInputs.length === 0){
            return;
        }

        function updateBannerActions(scope)
        {
            let hasInput = $.trim(scope.find('input[name="banner_file"]').val() || '') !== '';
            let hasCurrentBanner = scope.find('.banner-wrap img').length > 0;
            scope.find('[data-upload-banner-to-r2]').prop('disabled', ! hasInput);
            scope.find('[data-clear-banner-image]').prop('disabled', ! hasInput && ! hasCurrentBanner);
        }

        bannerInputs.each(function(){ updateBannerActions($(this).closest('.x_content')); });
        bannerInputs.on('input change', function(){ updateBannerActions($(this).closest('.x_content')); });

        $(document).on('click', '[data-clear-banner-image]', function(){
            let scope = $(this).closest('.x_content');
            scope.find('input[name="banner_file"]').val('').trigger('input').trigger('change');
            scope.find('[data-r2-banner-link]').val('');
            scope.find('input[name="remove_banner"]').val('1');
            scope.find('.banner-wrap').empty();
            updateBannerActions(scope);
        });

        $(document).on('click', '[data-upload-banner-to-r2]', function(event){
            let scope = $(this).closest('.x_content');
            let hasInput = $.trim(scope.find('input[name="banner_file"]').val() || '') !== '';
            if (! hasInput) {
                event.preventDefault();
            }
        });
    }

    function init_host_api_provider_structure()
    {
        let providerField = $('#host-api-provider');
        if(providerField.length === 0){
            return;
        }

        let fixedRoots = {
            earnvids: 'https://earnvidsapi.com/api',
            upnshare: 'https://upnshare.com/api/v1',
            cloudflare_r2: 'https://r2.cloudflarestorage.com'
        };

        function renderProviderStructure()
        {
            let provider = $.trim(providerField.val());
            $('[data-provider-note]').prop('hidden', function(){
                return $(this).data('provider-note') !== provider;
            });
            $('[data-provider-structure]').prop('hidden', function(){
                let supported = $(this).data('provider-structure');
                return supported !== provider && !(supported === 'other' && provider !== 'earnvids' && provider !== 'upnshare' && provider !== 'cloudflare_r2');
            });
            $('[data-r2-settings]').prop('hidden', provider !== 'cloudflare_r2');
            $('[data-video-api-token], [data-video-api-scopes], .host-api-test, #host-api-test-result').prop('hidden', provider === 'cloudflare_r2');

            if(fixedRoots[provider]){
                $('#host-api-base-url').val(fixedRoots[provider]);
            }
        }

        providerField.on('change', renderProviderStructure);
        renderProviderStructure();
    }

    function init_host_api_connection_test()
    {
        let button = $('#host-api-test');
        let result = $('#host-api-test-result');

        if(button.length === 0 || result.length === 0){
            return;
        }

        button.on('click', function(){
            let provider = $.trim($('#host-api-provider').val());
            let apiBaseUrl = $.trim($('#host-api-base-url').val());
            let token = $.trim($('#host-api-token').val());
            let apiId = parseInt($('.host-api-test').attr('data-api-id'), 10) || 0;

            if(token === '' && apiId === 0){
                renderHostApiTestError('Enter the API token before testing the connection.');
                return;
            }

            button.prop('disabled', true).addClass('is-loading');
            button.find('.fa').removeClass('fa-refresh').addClass('fa-spinner fa-spin');
            result.empty();

            $.ajax({
                url: BASE_URL + '/ajax/host-api-test',
                type: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                data: {
                    provider: provider,
                    api_base_url: apiBaseUrl,
                    api_token: token,
                    api_id: apiId
                },
                dataType: 'JSON',
                success: function(data){
                    if(data.success && data.data){
                        renderHostApiTestSuccess(data.data);
                        return;
                    }

                    renderHostApiTestError(data.error || 'The host did not return a valid API response.');
                },
                error: function(){
                    renderHostApiTestError('The connection test could not be completed. Please try again.');
                },
                complete: function(){
                    button.prop('disabled', false).removeClass('is-loading');
                    button.find('.fa').removeClass('fa-spinner fa-spin').addClass('fa-refresh');
                }
            });
        });

        function renderHostApiTestError(message)
        {
            result.empty().append(
                $('<div>', {class: 'host-api-test-result is-error'}).append(
                    $('<i>', {class: 'fa fa-exclamation-circle', 'aria-hidden': 'true'}),
                    $('<span>').text(message)
                )
            );
        }

        function renderHostApiTestSuccess(data)
        {
            let panel = $('<section>', {class: 'host-api-test-result is-success'});
            panel.append($('<div>', {class: 'host-api-test-result__summary'}).append(
                $('<i>', {class: 'fa fa-check-circle', 'aria-hidden': 'true'}),
                $('<div>').append(
                    $('<strong>').text('Connection verified'),
                    $('<span>').text(data.message || 'The host returned a valid API response.')
                ),
                $('<small>').text('HTTP ' + (data.http_status || 200) + ' · ' + (data.provider || 'host'))
            ));

            if(! data.sample){
                panel.append($('<p>', {class: 'host-api-test-result__empty'}).text('No video sample is currently available from this account.'));
                result.empty().append(panel);
                return;
            }

            let sample = data.sample;
            let fields = [
                ['Endpoint', data.endpoint],
                ['Title', sample.title],
                ['File name', sample.file_name],
                ['Video ID', sample.video_id],
                ['Playback status', sample.can_play],
                ['Duration', sample.duration],
                ['Uploaded', sample.uploaded_at],
                ['Views', sample.views],
                ['Player link', sample.player_url],
                ['Poster', sample.poster_url]
            ];
            let list = $('<dl>', {class: 'host-api-test-result__fields'});
            fields.forEach(function(field){
                list.append($('<dt>').text(field[0]));
                list.append($('<dd>').text(field[1] || 'Not supplied by this host response'));
            });

            panel.append(list);

            if(data.responses){
                Object.keys(data.responses).forEach(function(name){
                    let json = JSON.stringify(data.responses[name], null, 2);
                    let responsePanel = $('<details>', {class: 'host-api-test-result__response'});
                    let labels = {
                        file_info: 'File Info API response',
                        file_list: 'File List API response',
                        video_list: 'Video List API response',
                        video_info: 'Video Detail API response'
                    };
                    responsePanel.append($('<summary>').text(labels[name] || 'Provider API response'));
                    responsePanel.append($('<pre>').text(json || '{}'));
                    panel.append(responsePanel);
                });
            }

            result.empty().append(panel);
        }
    }

    function init_suggestion()
    {
        let search_thread = null;
        let host_search_thread = null;
        let term = '';
        let type = 'movie';
        let resultsContent = $("#suggest-results");
        let hostResultsContent = $("#host-api-results");

        $('.title-suggest').on('keyup', function (){

            if($('input[name="season"]').length === 0){

                let $this = $(this);
                type = $this.attr('data-type');

                clearTimeout( search_thread );
                clearTimeout( host_search_thread );
                cleanResults();
                cleanHostResults();

                search_thread = setTimeout(function(){
                    term = $this.val().trim();
                    if(term.length > 0){
                        load_results();
                    }
                }, 500);

                host_search_thread = setTimeout(function(){
                    let hostTerm = $this.val().trim();
                    if(hostTerm.length >= 3){
                        loadHostResults(hostTerm);
                    }
                }, 650);

            }
        });

        $(document).on('click', '#suggest-results .movie-item.new', function (){

            let tmdbId = $(this).attr('data-tmdb');
            $('input[name="tmdb_id"]').val( tmdbId );

            //clean results
            cleanResults();

            //run autoload
            if(type === 'movie'){
                $("#load-movie-data").click();
            }else if(type === 'tv'){
                $("#load-tv-data").click();
            }

        });

        $(document).on('click', '.host-video-result', function () {
            let item = $(this).data('hostVideo');
            if(! item){
                return;
            }

            $('input[name="title"]').val(item.title);

            if(item.poster_url){
                let bannerInput = $('input[name="banner_url"]');
                bannerInput.val(item.poster_url).trigger('change');
                $('.banner-wrap').empty().append(
                    $('<img>', {
                        src: item.poster_url,
                        class: 'w-100 mb-2',
                        alt: 'video poster preview'
                    })
                );
            }

            let streamInputs = $('input[name^="st_links"][name$="[url]"]').filter(':not([readonly])');
            let streamInput = streamInputs.filter(function () {
                return $.trim($(this).val()) === '';
            }).first();

            if(streamInput.length === 0){
                streamInput = streamInputs.first();
            }

            let streamWasFilled = false;
            if(streamInput.length > 0 && item.player_url){
                streamInput.val(item.player_url).trigger('change');
                streamWasFilled = true;

                // Keep the selected provider and file code with the link.
                // EarnVids direct URLs are requested later for the visitor's
                // IP address, so an expiring direct URL is never saved here.
                let fieldName = streamInput.attr('name') || '';
                let fieldMatch = fieldName.match(/^st_links\[(\d+)\]\[url\]$/);
                if(fieldMatch && item.api_id && item.file_code){
                    let streamGroup = streamInput.closest('.st-group');
                    let key = fieldMatch[1];
                    streamGroup.find('input[name="st_links[' + key + '][api_id]"], input[name="st_links[' + key + '][upnshare_video_id]"]').remove();
                    streamGroup.append($('<input>', {type: 'hidden', name: 'st_links[' + key + '][api_id]', value: item.api_id}));
                    streamGroup.append($('<input>', {type: 'hidden', name: 'st_links[' + key + '][upnshare_video_id]', value: item.file_code}));
                }
            }

            hostResultsContent.empty().append(
                $('<div>', {class: 'host-search-selected'}).append(
                    $('<i>', {class: 'fa fa-check-circle', 'aria-hidden': 'true'}),
                    document.createTextNode(streamWasFilled
                        ? ' Host video selected. Title, poster URL, and a stream link have been filled. Video ID is unchanged.'
                        : ' Host video selected. Title and poster URL have been filled. The host did not return a player URL, so Stream Link is unchanged.')
                )
            );
        });

        function load_results()
        {
            $.ajax({
                url : BASE_URL + '/ajax/suggest',
                type: "GET",
                headers: { 'X-Requested-With': 'XMLHttpRequest'},
                data: {
                    'title' : term,
                    'type'  : type
                },
                dataType: "JSON",
                beforeSend: function () {

                    // self.loading();

                },
                success: function( data )
                {

                    if(data.success){
                        let results = data.data.results;
                        addResults( results );
                    }

                },
                error: function (jqXHR, textStatus, errorThrown)
                {
                    alert('Error occurred');
                },
                complete: function () {
                    // self.loaded();
                }
            });
        }

        function addResults( results )
        {
            resultsContent.html( results );
        }

        function cleanResults()
        {
            resultsContent.html('');
        }

        function loadHostResults(hostTerm)
        {
            $.ajax({
                url : BASE_URL + '/ajax/host-video-search',
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest'},
                data: {'title': hostTerm},
                dataType: 'JSON',
                success: function(data)
                {
                    // Ignore a slow response for an earlier title after the
                    // administrator has continued typing.
                    if($.trim($('.title-suggest').val()) !== hostTerm){
                        return;
                    }

                    if(data.success && data.data){
                        addHostResults(data.data.items || [], data.data.configured_hosts || 0);
                    }
                },
                // Host results are optional. The existing form remains usable if
                // an external provider is unavailable.
                error: function () {}
            });
        }

        function addHostResults(items, configuredHosts)
        {
            cleanHostResults();

            if(configuredHosts < 1){
                return;
            }

            let section = $('<section>', {class: 'host-search-results'});
            section.append($('<div>', {class: 'host-search-results__heading'}).append(
                $('<span>').text('Video host results'),
                $('<small>').text('Choose a result to fill the title, poster URL, and stream link.')
            ));

            if(items.length === 0){
                section.append($('<p>', {class: 'host-search-results__empty'}).text('No playable files matched this title on your active video hosts.'));
                hostResultsContent.append(section);
                return;
            }

            let list = $('<div>', {class: 'host-search-results__list'});
            items.forEach(function(item){
                let card = $('<button>', {type: 'button', class: 'host-video-result'}).data('hostVideo', item);
                let preview = $('<div>', {class: 'host-video-result__preview'});

                if(item.poster_url){
                    preview.append($('<img>', {src: item.poster_url, alt: ''}));
                }else{
                    preview.append($('<i>', {class: 'fa fa-play-circle', 'aria-hidden': 'true'}));
                }

                let content = $('<div>', {class: 'host-video-result__content'});
                content.append($('<strong>').text(item.title));
                content.append($('<span>', {class: 'host-video-result__source'}).text(item.source + ' · ' + item.provider));
                content.append($('<span>', {class: 'host-video-result__link'}).text(item.player_url || 'Player URL is not available from this host response.'));

                card.append(preview, content, $('<i>', {class: 'fa fa-arrow-right host-video-result__arrow', 'aria-hidden': 'true'}));
                list.append(card);
            });

            section.append(list);
            hostResultsContent.append(section);
        }

        function cleanHostResults()
        {
            hostResultsContent.empty();
        }

    }

    function btn_loading( btn )
    {
        if(btn instanceof jQuery) {
            if(btn.find('.spinner-border').length > 0) {
                btn.attr('disabled', 'disabled');
                btn.find('.spinner-border').show();
            }
        }
    }

    function appendInputData( data, allowedInputFields )
    {
        for (let index = 0; index < allowedInputFields.length; ++index) {
            let field = allowedInputFields[index];
            let selector = 'input[name="'+ field +'"]';
            $(selector).val( data[field] );
        }
    }

    function btn_loaded( btn )
    {
        if(btn instanceof jQuery) {
            if(btn.find('.spinner-border').length > 0) {
                btn.removeAttr('disabled');
                btn.find('.spinner-border').hide();
            }
        }
    }

    function update_genres(genres)
    {

        if(genres.length > 0){
            let selectedGenres = [];
            console.log(genres);
            genres.forEach(function (item, index) {
                let genre = item.toLowerCase();

                let val = $('select[name="genres[]"] option').filter(function () { return $(this).html() == genre; }).val();
                console.log(selectedGenres);
                if(val) {
                    selectedGenres.push(val);
                }
            });
            $('#select-genres').val(selectedGenres);
            $('#select-genres').trigger('change');
        }
    }


    function init_data_list_datatable()
    {

        if (typeof ($.fn.DataTable) === 'undefined') { return; }

        $('#datatable').DataTable({
            order: [],
            pageLength: 25,
        });

        $('#movies-list-datatable').DataTable( {
            dom: '<"video-table-toolbar"<"video-table-toolbar__actions"B><"video-table-toolbar__filter">><"video-table-controls"<"video-table-controls__length"l><"video-table-controls__search"f>>rt<"video-table-footer"<"video-table-footer__info"i><"video-table-footer__pagination"p>>',
            buttons: [
                'colvis',
                {
                    extend: 'collection',
                    text: 'Export data',
                    buttons: [
                        {
                            extend: "csv",
                            className: "btn-sm",
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: "excel",
                            className: "btn-sm",
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: "pdfHtml5",
                            className: "btn-sm",
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                    ]
                },
                {
                    extend: "print",
                    className: "btn-sm",
                    exportOptions: {
                        columns: ':visible'
                    }
                },
            ],
            responsive: true,
            stateSave: false,
            processing: true,
            serverSide: true,
            ajax: {
                url: $('#movies-list-datatable').data('source'),
                data: function (data) {
                    data.filter = new URLSearchParams(window.location.search).get('filter') || '';
                }
            },
            order: [],
            pageLength: 25,
            columnDefs: [{ targets: [3, 7], orderable: false }],
            language: {
                processing: 'Loading videos…',
                search: '',
                searchPlaceholder: 'Search title or Video ID...',
                lengthMenu: 'Show _MENU_ per page',
                info: 'Showing _START_–_END_ of _TOTAL_ videos',
                infoEmpty: 'No videos found'
            }
        } );

        $('#links-list-datatable').DataTable( {
            dom: '<"link-table-toolbar"<"link-table-toolbar__left"lB><"link-table-toolbar__right"f>>rt<"link-table-footer"<"link-table-footer__info"i><"link-table-footer__pagination"p>>',
            buttons: [
                {
                    extend: 'collection',
                    text: 'Export data',
                    buttons: [
                        {
                            extend: "csv",
                            className: "btn-sm",
                            exportOptions: {
                                columns: [0,1,2,3,4]
                            }
                        },
                        {
                            extend: "excel",
                            className: "btn-sm",
                            exportOptions: {
                                columns: [0,1,2,3,4]
                            }
                        },
                        {
                            extend: "pdfHtml5",
                            className: "btn-sm",
                            exportOptions: {
                                columns: [0,1,2,3,4]
                            }
                        },
                    ]
                },
                {
                    extend: "print",
                    className: "btn-sm",
                    exportOptions: {
                        columns: [0,1,2,3,4]
                    }
                },
            ],
            responsive: true,
            stateSave: false,
            processing: true,
            serverSide: true,
            ajax: {
                url: $('#links-list-datatable').data('source'),
                data: function (data) {
                    data.filter = $('#links-list-datatable').data('filter') || '';
                }
            },
            order: [[5, 'desc']],
            pageLength: 25,
            autoWidth: false,
            columnDefs: [{ targets: 6, orderable: false }],
            language: {
                processing: 'Loading links…',
                search: '',
                searchPlaceholder: 'Search links...',
                lengthMenu: 'Show _MENU_ per page',
                info: 'Showing _START_–_END_ of _TOTAL_ links',
                infoEmpty: 'No links found'
            }
        } );

        $('#reported-links-datatable').DataTable({
            dom: '<"link-table-toolbar"<"link-table-toolbar__left"l><"link-table-toolbar__right"f>>rt<"link-table-footer"<"link-table-footer__info"i><"link-table-footer__pagination"p>>',
            responsive: true,
            stateSave: false,
            processing: true,
            serverSide: true,
            ajax: {
                url: $('#reported-links-datatable').data('source')
            },
            order: [[4, 'desc']],
            pageLength: 25,
            autoWidth: false,
            columnDefs: [{ targets: 6, orderable: false }],
            language: {
                processing: 'Loading reported links…',
                search: '',
                searchPlaceholder: 'Search reported links...',
                lengthMenu: 'Show _MENU_ per page',
                info: 'Showing _START_–_END_ of _TOTAL_ reported links',
                infoEmpty: 'No reported links found'
            }
        });

        $('#series-list-datatable').DataTable( {
            dom: '<"datatable-top-btn-list"B><"float-left"l><"float-right"f>rtip',
            buttons: [
                'colvis',
                {
                    extend: 'collection',
                    text: 'Export data',
                    buttons: [
                        {
                            extend: "csv",
                            className: "btn-sm",
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: "excel",
                            className: "btn-sm",
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: "pdfHtml5",
                            className: "btn-sm",
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                    ]
                },
                {
                    extend: "print",
                    className: "btn-sm",
                    exportOptions: {
                        columns: ':visible'
                    }
                },
            ],
            responsive: true,
            stateSave: true,
            columnDefs: [ {
                targets: [5,6,7,8,9,10,13,14],
                visible: false
            } ]
        } );

        let filter = $(".ve-results--filter").clone();
        filter.removeClass('d-none');

        if ($(".video-table-toolbar__filter").length) {
            $(".video-table-toolbar__filter").append(filter);
        } else {
            $(".datatable-top-btn-list").append(filter);
        }

    }

    function importMovie( movie_uniq_id )
    {
        $.ajax({
            url : BASE_URL + '/ajax/import',
            type: "GET",
            headers: { 'X-Requested-With': 'XMLHttpRequest'},
            data: {
                'imdb_id' : movie_uniq_id
            },
            dataType: "JSON",
            success: function(data)
            {

                if(data.success) {



                }

            },
            complete: function () {

            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                alert('Error occurred');
            }
        });
    }

})(jQuery);


function filter_movie_results(value) {
    let url = location.protocol + '//' + location.host + location.pathname;
    window.location.href = url + '?filter=' + value;
}

function get_short_embed_link( movieId )
{
    return SITE_URL + EMBED_SLUG + '/' + movieId;
}
