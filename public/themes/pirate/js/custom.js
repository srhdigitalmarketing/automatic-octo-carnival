


let lazyLoadInstance;

$(document).ready(function() {


    if (typeof (LazyLoad) !== 'undefined') {
        lazyLoadInstance = new LazyLoad({
            callback_error: (img) => {
                console.log(img);
            }
        });
    }

    search.init();
    Requests.init();
    QuickEmbed.init();


    // ========================== Get Movie By Imdb or Tmdb ID ==========================
    $(".get-movie").on('click', function (){
        let movieId = $("#movie-uniq-id").val().trim();
        if(movieId.length > 0){
            if(is_valid_movie_id( movieId )){
                set_embed_link( get_movie_embed_link( movieId ), movieId );
            }else{
                alert('Imdb or Tmdb id is invalid', 'alert-danger');
            }
        }else{
            alert('Imdb or Tmdb id is required', 'alert-danger');
        }
    });

    // ========================== Get Episode By Imdb or Tmdb ID ==========================
    $(".get-show").on('click', function (){
        let movieId = $("#movie-uniq-id").val().trim();
        if(movieId.length > 0){
            if(is_valid_movie_id( movieId )){
                set_embed_link( get_movie_embed_link( movieId ), movieId );
            }else{
                alert('Imdb or Tmdb id is invalid', 'alert-danger');
            }
        }else{
            alert('Imdb or Tmdb id is required', 'alert-danger');
        }
    });

    $(".tabs a").on('click', function (){

        let tabs = $(this).parent();

        //get active tab and close active content
        let activeContentId = "#" + tabs.find('.btn').attr('data-target');
        $(activeContentId).removeClass('active')

        //get current tab id and remove all active tabs
        let currentContentId = "#" + $(this).attr('data-target');
        tabs.find('a').removeClass('btn');
        tabs.find('a').removeClass('nav-link').addClass('nav-link');

        //attempt to active new tab and content
        $(this).addClass('btn');
        $(this).removeClass('nav-link');
        $(currentContentId).addClass('active');

    });

    $(".show-embed-codes").on('click', function (){

        let movieItem = $(this).closest('.movie-card');
        let title = movieItem.find(".title").text().trim();

        //embed links
        let embedLink_1 = movieItem.find('.embed--link--1').text().trim();
        let embedLink_2 = movieItem.find('.embed--link--2').text().trim();

        set_embed_links_group_data(embedLink_1, embedLink_2);
        $("#embed-links-modal .modal-title").text( title );
    });

    $('.report-dl-link').on('click', function (){

        let formData = $(this).closest('form').serialize();

        let btn = new Button( $(this) );


        $.ajax({
            url : BASE_URL + 'ajax/report_download_link',
            type: "GET",
            headers: { 'X-Requested-With': 'XMLHttpRequest'},
            data: formData,
            dataType: "JSON",
            beforeSend: function ()
            {
                btn.loading();
            },
            success: function(data)
            {
                if(data.success) {

                    setTimeout(function (){
                        //button loaded
                        btn.node.text('Reported');
                        btn.disable();
                        setTimeout(function (){
                            //close servers dropdown list
                            halfmoon.deactivateAllDropdownToggles();
                        }, 1000)
                    }, 501);

                }else{

                    alert( data.error, 'alert-danger' );

                }

            },
            complete: function (){

                setTimeout(function (){
                    //button loaded
                    btn.loaded();
                }, 500);

            },
            error: function (jqXHR, textStatus, error)
            {
                alert( 'something went wrong' );
            }
        });

    });

    $(".selection .dropdown-menu .dropdown-item").on('click', function (){
        let val = $(this).text();
        $(this).closest('.selection').find('.btn[data-toggle="dropdown"]').text(val);

        //close servers dropdown list
        halfmoon.deactivateAllDropdownToggles();
    });

    $("#season-select").on('change', function (){

        let episodeGroupId = "#sea-" + this.value + '--episodes';
        let episodeGroup = $(episodeGroupId);

        episodeGroup.find('option:selected').change();

        $(".episodes-select").hide();
        episodeGroup.show();
    });

    $(".episodes-select").on('change', function (){

        let season = $('#season-select option:selected').val();
        let episode = this.value;

        let series_imdb_id = $('.seasons-list').attr('data-series-imdb');
        let episode_imdb_id = $(this).find(':selected').attr('data-imdb');

        let embedLink_1 = get_short_embed_link( episode_imdb_id );
        let embedLink_2 = get_episode_embed_link( series_imdb_id, season, episode );

        set_embed_links_group_data(embedLink_1, embedLink_2);
        set_embed_link( embedLink_2, episode_imdb_id );

    });

    if($("#dl-timer").length === 1){

        $('#dl-timer').countdownTimer({

            seconds: timerSec,
            loop:false,

            callback: function () {

                let form = $('.link-out-form');
                //remove disabled attr in the btn
                let submitBtn  = form.find('.submit');
                let token = form.find(".token");

                submitBtn.attr('type', 'submit');
                submitBtn.removeAttr('disabled');

                form.find('.card-title').html( readyMsg );

                //set token
                let tokenVal = $('meta[data-name="token"]').attr('content');
                let tokenInput = '<input type="hidden" name="token" value="' + tokenVal + '">';

                token.html( tokenInput );
            }

        });

    }

    $("[data-target=\"search-modal\"]").on('click', function (){
        $("#search-input").focus();
    });



});


const QuickEmbed = {

    type: 'movie',
    findingMethod: 'uniq_id',
    node: null,
    link: null,
    data: {
        title: null,
        uniqId: null,
        season: null,
        episode: null
    },
    init: function (){

        this.node = $("#quick-embed");

    },
    getMovie: function (){

        this.type = 'movie';

        this.resetData();

        if( this.loadData() ){

            this.link = '/' + EMBED_SLUG + '/movie';

            if(this.findingMethod === 'uniq_id'){
                if(is_imdb_id( this.data.uniqId )){
                    this.link += '?imdb=' + this.data.uniqId;
                }else{
                    this.link += '?tmdb=' + this.data.uniqId;
                }
            }else{
                this.link += '?title=' + this.data.title + '&year=' + this.data.year
            }

            set_embed_link( BASE_URL + this.link, this.data.uniqId );

        }

    },
    getEpisode: function (){

        this.type = 'series';

        this.resetData();

        if( this.loadData() ){

            this.link = '/' + EMBED_SLUG + '/series';

            if(this.findingMethod === 'uniq_id'){

                if(is_imdb_id( this.data.uniqId )){

                    this.link += '?imdb=' + this.data.uniqId;
                }else{
                    this.link += '?tmdb=' + this.data.uniqId;
                }



            }else{
                this.link += '?title=' + this.data.title + '&year=' + this.data.year
            }

            this.link +=   '&sea=' + this.data.season + '&epi=' + this.data.episode;

            set_embed_link( BASE_URL + this.link, this.data.uniqId );

        }

    },
    toggle: function ( element ){

        if($(element).attr('data-type') === 'by_uniq_id'){
            this.findingMethod = 'uniq_id';

            this.node.find('.find-by-uniq-id').show();
            this.node.find('.find-by-title').hide();

        }else{

            this.findingMethod = 'title';

            this.node.find('.find-by-uniq-id').hide();
            this.node.find('.find-by-title').show();
        }

    },
    loadData: function () {

        let success = false;

        if(this.findingMethod === 'uniq_id'){

            if(this.type === 'movie'){

                this.data.uniqId = this.node.find('.movie-uniq-id').val().trim();

            }else{

                this.data.uniqId = this.node.find('.series-uniq-id').val().trim();

            }


            if(this.data.uniqId.length > 0){

                if(is_valid_movie_id( this.data.uniqId )){

                    success = true;

                }else{

                    alert_danger('Imdb or Tmdb id is invalid');

                }

            }else{

                alert_danger('Imdb or Tmdb id is required');

            }


        }else{

            if(this.type === 'movie'){

                this.data.title = this.node.find('.movie-title').val().trim();
                this.data.year = this.node.find('.movie-year').val().trim();

                if(this.data.year !== '' && ! yearValidate( this.data.year )){
                    return;
                }

            }else{

                this.data.title = this.node.find('.series-title').val().trim();
                this.data.year = this.node.find('.series-year').val().trim();
                this.data.season = this.node.find('.season').val().trim();
                this.data.episode = this.node.find('.episode').val().trim();

                if(this.data.year !== '' && ! yearValidate( this.data.year )){
                    return;
                }

                if(this.data.season !== '' && this.data.season <= 0) {
                    alert_danger('Invalid Season');
                    return;
                }

                if(this.data.episode !== '' && this.data.episode <= 0) {
                    alert_danger('Invalid Episode');
                    return;
                }

            }

            if(this.data.title.length > 0){
                success = true;
            }else{
                alert_danger('Title is required');
            }

        }


        return success;

    },
    resetData: function (){

        this.data.title = null;
        this.data.uniqId = null;
        this.data.season = null;
        this.data.episode = null;

    }

}

const search = {

    results: null,
    search_thread: null,
    results_content: null,
    input: null,
    term: null,

    init: function (){
        this.input = $('#search-input');
        this.results_content = $('#search-results');
    },
    find: function (){

        let self = this;

        clearTimeout( self.search_thread );
        self.reset();

        self.search_thread = setTimeout(function(){
            self.term = self.input.val().trim();
            if(self.term.length > 2){
                self.load();
            }else if(self.term.length > 0){
                self.noResults();
            }else{
                self.hasResults();
            }
        }, 500);
    },
    load: function (){
        let self = this;

        $.ajax({
            url : BASE_URL + 'search',
            type: "GET",
            headers: { 'X-Requested-With': 'XMLHttpRequest'},
            data: {
                'term' : self.term
            },
            dataType: "HTML",
            success: function( data )
            {
                let isFound = false;
                if(data !== ''){

                    if(data.indexOf('movie-card') !== -1){

                        self.addResults( data );
                        lazyLoadInstance.update();
                        isFound = true;
                    }

                }

                if(! isFound){
                    self.noResults();
                }

            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                alert('Error occurred');
            }
        });

    },
    noResults: function (){
        $(".results-not-found").show();
    },
    hasResults: function(){
        $(".results-not-found").hide();
    },
    addResults: function ( data ){
        this.results_content.html( data );
    },
    clearResults: function (){
        this.results_content.html('');
    },
    reset: function (){
        this.hasResults();
        this.clearResults();
    }

}

const Requests = {

    enabled: false,
    selected: [],
    search_thread: null,
    results_content: null,
    content_loader: null,
    input: null,
    term: null,
    type: 'movie',

    init: function (){
        this.input = $('.movie-suggest-input');
        this.results_content = $('#suggest-results');
        this.content_loader = $('.content-loading');
    },
    find: function (){

        let self = this;

        clearTimeout( self.search_thread );
        self.reset();

        self.search_thread = setTimeout(function(){
            self.term = self.input.val().trim();
            if(self.term.length > 0){
                self.load();
            }else{
                self.hasResults();
            }
        }, 500);
    },
    select: function ( element ){
        let $this = $(element);
        let movieItem = $this.closest('.movie-card');
        let tmdbId = movieItem.attr('data-tmdb');

        if(! this.isExistInQuery( tmdbId )){

            if(this.selected.length < 3) {

                this.selected.push( tmdbId );

                movieItem.addClass('selected');
                $this.text('selected');

                this.updateSelection();

            }else{

                alert('The selected query is full', 'alert-secondary');

            }


        }

    },
    unselect: function ( element ) {

        let $this = $(element);
        let tmdbId = $this.closest('li').attr('id');

        if(this.isExistInQuery( tmdbId )){

            //remove fro query
            this.removeFromQuery( tmdbId );

            this.updateSelection();
        }

    },
    isExistInQuery: function ( item ) {
        let index = this.selected.indexOf(item);
        if (index !== -1) {
            return true;
        }
        return false;
    },
    removeFromQuery: function ( item ) {
        let index = this.selected.indexOf(item);
        if (index !== -1) {
            let movie = this.getMovieItemById( item );

            if(movie !== null){
                movie.removeClass('selected');
                movie.find('.select-btn').text('select');
            }
            $('li#' + item).remove();
            $('.input-' + item).remove();

            this.selected.splice(index, 1);
        }
    },
    updateSelection: function () {

        //hide results not found
        $('.selected-not-found:visible').hide();
        if(! this.enabled) {
            this.requestEnabled();
        }

        let itemHtml = '<li>' +
            '<span class="title cut-text"></span>' +
            '<a href="javascript:void(0)" onclick="Requests.unselect(this)" class="float-right">' +
            '<i class="fa fa-times" aria-hidden="true"></i>' +
            '</a>' +
            '</li>';

        let item = $(itemHtml);
        let tmdb = this.selected.slice(-1)[0];

        if($('li#'+ tmdb).length === 0){

            let movieItem = this.getMovieItemById( tmdb );
            if(movieItem !== null){

                //set selected item title
                let title = movieItem.find('.title').text();
                item.find('.title').text( title );
                //set selected item id
                item.attr('id', tmdb);
                $('.selected-movies').append( item );


                let titleInput = $('<input type="text">');
                titleInput.addClass('input-' + tmdb);
                titleInput.attr('name', 'items['+ tmdb +'][title]');
                titleInput.val( title );

                let typeInput = $('<input type="text">');
                typeInput.addClass('input-' + tmdb);
                typeInput.attr('name', 'items['+ tmdb +'][type]');
                typeInput.val( this.type );

                $('.selection-input').append( titleInput );
                $('.selection-input').append( typeInput );

            }

        }

        //show selected movies, if is it hidden
        $('.selected-movies:hidden').show();

        if(this.selected.length === 0){
            $('.selected-not-found:hidden').show();
            $('.selected-movies:visible').hide();
            //disable request
            this.requestDisabled();
        }

    },
    getMovieItemById: function ( id ){
        let element = $('.movie-card[data-tmdb="'+ id +'"]');
        if(element.length > 0){
            return element;
        }

        return null;
    },
    requestDisabled: function (){
        this.enabled = false;
        $("#request-submit").attr('disabled', 'disabled');
    },
    requestEnabled: function () {
        this.enabled = true;
        $("#request-submit").removeAttr('disabled');
    },
    load: function (){
        let self = this;

        $.ajax({
            url : BASE_URL + 'ajax/get_suggest',
            type: "GET",
            headers: { 'X-Requested-With': 'XMLHttpRequest'},
            data: {
                'title' : self.term,
                'type'  : self.type
            },
            dataType: "JSON",
            beforeSend: function () {

                self.loading();

            },
            success: function( data )
            {

                let isFound = false;
                if(data.success){
                    let results = data.data.results;
                    if(results.indexOf('movie-card') !== -1){

                        self.addResults( results );
                        isFound = true;

                    }
                }

                if(! isFound){
                    self.noResults();
                }

            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                alert('Error occurred');
            },
            complete: function () {
                self.loaded();
            }
        });

    },
    noResults: function (){
        setTimeout(function () {
            $(".results-not-found").fadeIn();
        }, 500);
    },
    hasResults: function(){
        $(".results-not-found").fadeOut(500);
    },
    addResults: function ( data ){
        let self = this;
        this.results_content.html( data );

        this.selected.forEach(function (value, index){

            let movie = self.getMovieItemById( value );
            if(movie !== null){
                movie.addClass('selected');
                movie.find('.select-btn').text('selected');
            }

        });

        lazyLoadInstance.update();
    },
    clearResults: function (){
        this.results_content.html('');
    },
    loading: function () {
        this.results_content.fadeOut();
        this.content_loader.fadeIn();
    },
    loaded: function () {
        let self = this;
        self.content_loader.fadeOut(500);
        setTimeout(function () {
            self.results_content.fadeIn();
        }, 500)

    },
    reset: function (){
        this.hasResults();
        this.clearResults();
    },
    changeType: function (t) {
        this.type = t;
        this.input = $('.' + t +'-suggest-input');
        this.clearResults();
    }
}


//ads blocker
const AdblockDetector = {
    init: function (){
        this.detect().done(function(adsEnabled){

            if (! adsEnabled) {
                if($("#ad-block-detected-modal").length > 0){
                    $("#ad-block-detected-modal").addClass('show');
                }
            }
        });
    },
    detect: function() {
        let dfd = new $.Deferred();
        let adsEnabled = false;
        let $dummy = $('<div class="ads-box">&nbsp;</div>').appendTo('body');

        setTimeout(function () {
            if ($dummy.height() > 0) {
                adsEnabled = true;
            }
            $dummy.remove();

            dfd.resolve(adsEnabled);
        }, 100);

        return dfd;
    }
};

function set_embed_links_group_data(link1 , link2 = '')
{
    let links_group = $('#direct-links-content');
    let iframe_code_group = $('#embed-code-content');

    //setup iframe code
    let iframeCode = iframe_code_group.find('.embed-code').val();
    iframeCode = iframeCode.replace(/src=(".*?")/, 'src="' + link2 +'"');

    links_group.find('.embed-link-1').val( link1 );
    links_group.find('.embed-link-2').val( link2 );

    iframe_code_group.find('.embed-code').val( iframeCode );
}

function yearValidate(year) {

    let text = /^[0-9]+$/;
    if (year !== 0) {

        if ((year !== "") && (!text.test(year))) {
            alert_danger('Please Enter Numeric Values Only');
            return false;
        }

        if (year.length !== 4) {
            alert_danger("Year is not proper");
            return false;
        }

        let current_year = new Date().getFullYear();
        if((year < 1900) || (year > current_year))
        {
            alert_danger("Year should be in range 1900 to current year");
            return false;
        }

        return true;

    }

}

function get_short_embed_link( movieId )
{
    return BASE_URL + EMBED_SLUG + '/' + movieId;
}
function get_episode_embed_link( movieId , season = 1, episode = 1 )
{
    let link = BASE_URL + EMBED_SLUG + '/series' ;

    link += is_imdb_id( movieId ) ? '?imdb=' : '?tmdb=';
    link += movieId;

    link += '&sea=' + season + '&epi=' + episode;

    return link;
}

function get_view_link( movieId )
{
    return BASE_URL + VIEW_SLUG + '/' + movieId;
}

function get_movie_embed_link( movieId )
{
    let link = BASE_URL + EMBED_SLUG + '/movie';
    link += is_imdb_id( movieId ) ? '?imdb=' : '?tmdb=';
    link += movieId;

    return link;
}

function set_embed_link( embedLink, movie_id ='' )
{
    let downloadLink = embedLink.replace("embed", "download");
    let player = $("#embed-player");

    player.find(".ve-iframe").prop('src', embedLink);
    player.find(".ve-download--btn").prop('href', downloadLink);

    if(movie_id === '' || movie_id === null) {
        movie_id = 'N/A';
    }
    console.log(movie_id);
    player.find(".active-movie-id").text( movie_id );
}

function is_valid_movie_id( movieId )
{
    let pattern = /^[tt]{0,2}[0-9]+$/i;
    return !!movieId.match(pattern);
}

function is_imdb_id( movieId )
{
    let pattern = /^tt[0-9]+$/i;
    return !!movieId.match(pattern);
}

function is_tmdb_id( movieId )
{
    let pattern = /^[0-9]+$/i;
    return !!movieId.match(pattern);
}

function libraryFilter(t = '', reset = false)
{
    let filter = $('#filter');

    //filter genres
   if(t === 'genres'){

       let genres = [];

       if(! reset){
           filter.find('.genre:checked').each(function(){
               genres.push( $(this).val() );
           });
       }

       return insertParam('genres', genres.join(','));
   }

   //filter quality
   if(t === 'quality'){

       let qualities = [];

       if(! reset){
           filter.find('.quality:checked').each(function(){
               qualities.push( $(this).val() );
           });
       }

       return insertParam('quality', qualities.join(','));
   }

   //filter year
    if(t === 'year'){

        let year = '';

        if(! reset){
           year = filter.find('[name=\'year\']:checked').val();
        }

        return insertParam('year', year);
    }

    //only parent shows year
    if(t === 'parent-shows'){

        let parentShows = 0;

        if($('[name=\'only-parents-shows\']').is(":checked")){
            parentShows = 1;
        }

        return insertParam('parents', parentShows);
    }

    //filter country
    if(t === 'country'){

        let country = '';

        if(! reset){
            country = filter.find('[name=\'country\']:checked').val();
        }

        return insertParam('country', country);
    }

    //filter language
    if(t === 'lang'){

        let lang = '';

        if(! reset){
            lang = filter.find('[name=\'lang\']:checked').val();
        }

        return insertParam('lang', lang);
    }

    //filter imdb_rate
    if(t === 'imdb_rate'){

        let imdbRate = '';

        if(! reset){
            imdbRate = filter.find('[name=\'imdb_rate\']').val();
        }

        return insertParam('imdb_rate', imdbRate);
    }



}

function librarySort()
{
    let sortBy = $('[name=\'sort_by\']:checked').val();

    return insertParam('sort_by', sortBy);
}

function sortDirChanged(dir = 'asc')
{
    return insertParam('sort_dir', dir);
}

function Button(obj) {
    this.isLoading = false;
    this.node = obj;
    this.text = obj.text();
    this.loader = '<div class="spinner-border mr-5" role="status">' +
        '</div>';
    this.loading = function(){
        if(! this.isLoading){

            this.disable(true);
            this.enableLoader(true);
            this.node.html(this.loader + this.text);
            this.isLoading = true;

        }
    }
    this.loaded = function(){
        if(this.isLoading){
            this.disable(false);
            this.enableLoader(false);
            this.node.html(this.text);
            this.isLoading = false;
        }
    }
    this.disable = function(t = true){
        if(t){
            if(! this.node.is('button')){
                if(! this.node.hasClass('disabled')){
                    this.node.addClass('disabled');
                }
            }else{
                this.node.attr('disabled','disabled');
            }
        }else{
            if(! this.node.is('button')){
                this.node.removeClass('disabled');
            }else{
                this.node.removeAttr('disabled');
            }
        }
    }
    this.enableLoader = function (t = true) {
        if(t){
            if(! this.node.hasClass('btn-loading')){
                this.node.addClass('btn-loading')
            }
        }else{
            this.node.removeClass('btn-loading');
        }
    }
}


function btn_loading( btn )
{
    if(btn instanceof jQuery) {

        btn.attr('disabled', 'disabled');
    }
}

function btn_loaded( btn )
{
    if(btn instanceof jQuery) {
        btn.removeClass('btn-loading')
        btn.attr('disabled', 'disabled');
    }
}

function copyToClipboard( element, t = '' ) {

    let value = t === '' ? $(element).val() : $(element).text();
    let $temp = $("<input>");
    $("body").append($temp);
    $temp.val( value ).select();
    document.execCommand("copy");
    $temp.remove();

    alert("Copied to Clipboard");
}

function alert(msg, alertType = 'alert-primary')
{
    halfmoon.initStickyAlert({
        title: msg,
        alertType: alertType,
        hasDismissButton: false,
        timeShown: 1500
    });
}

function alert_danger(msg)
{
   alert(msg, 'alert-danger');
}

function alert_success(msg)
{
    alert(msg, 'alert-success');
}

function alert_warning(msg)
{
    alert(msg, 'alert-secondary');
}

function recaptchaCallback( response )
{
    GCaptcha.token = response;
}


function insertParam(key, value) {
    key = encodeURIComponent(key);
    value = encodeURIComponent(value);

    let kvp = document.location.search.substr(1).split('&');
    let i=0;

    for(; i<kvp.length; i++){
        if (kvp[i].startsWith(key + '=')) {
            let pair = kvp[i].split('=');
            pair[1] = value;
            kvp[i] = pair.join('=');
            break;
        }
    }

    if(i >= kvp.length){
        kvp[kvp.length] = [key,value].join('=');
    }

    // reload page with new params
    document.location.search = kvp.join('&');
}