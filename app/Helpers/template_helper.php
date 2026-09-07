<?php


if(! function_exists('theme_path'))
{
    function theme_path($path = ''): string
    {
        if(! empty($path)){
            $path = ltrim($path, '/');
            $path = '/' . $path;
        }

        return  'themes/' .  default_theme_name() . $path;
    }
}

if(! function_exists('theme_assets'))
{
    function theme_assets($path = ''): string
    {
        if(! empty($path)){
            $path = ltrim($path, '/');
            $path = '/' . $path;
        }

        $file =  'themes/' .  default_theme_name() . $path;
        return site_url( $file );
    }
}

if(! function_exists('admin_assets'))
{
    function admin_assets($path = ''): string
    {
        if(! empty($path)){
            $path = ltrim($path, '/');
            $path = '/' . $path;
        }

        $file =  'admin-assets/' . $path;
        return site_url( $file );
    }
}

if(! function_exists('getResolutionFormatOptions'))
{
    function getResolutionFormatOptions()
    {
        $options = [ '' => '' ];
        $resolutionFormats = config('Settings')->download_resolution_formats;

        if(! empty($resolutionFormats)) {
            foreach ($resolutionFormats as $resolution) {
                $options[$resolution] = $resolution;
            }
        }

        return $options;
    }
}

if(! function_exists('getQualityFormatOptions'))
{
    function getQualityFormatOptions()
    {
        $options = [ '' => '' ];
        $qualityFormats = config('Settings')->download_quality_formats;

        if(! empty($qualityFormats)) {
            foreach ($qualityFormats as $quality) {
                $options[$quality] = $quality;
            }
        }

        return $options;
    }
}

if(! function_exists( 'format_links_status' ))
{
    function format_links_status($status)
    {
        $status = ! $status ? 'Active' : 'Broken';
        $html = '';

        if($status == 'Broken'){
            $html = '<span class="text-danger"> ' . $status . ' </span>';
        }else{
            $html = '<span class="text-success"> ' . $status . ' </span>';
        }
        return $html;
    }
}


if(! function_exists( 'the_movie_item' ) )
{
    function the_movie_item($movie, $imdbBased = false, $lazyLoading = true)
    {
        include view_path( theme_path("__partials/movie-item") );
    }
}

if(! function_exists( 'the_req_movie_item' ) )
{
    function the_req_movie_item(array $data )
    {
        include view_path( theme_path("__partials/req-movie-item") );
    }
}

if(! function_exists( 'the_admin_discover_page' ) )
{
    function the_admin_discover_page( $results, $page = 1, $total_page = 0 )
    {
        include view_path( "/admin/discover/x_panels/movies-page" );
    }
}


if(! function_exists( 'the_admin_suggest_page' ) )
{
    function the_admin_suggest_page( $results)
    {
        include view_path( "/admin/movies/form_x_panels/suggest" );
    }
}

if(! function_exists( 'the_embed_player' ) )
{
    function the_embed_player($activeMovie)
    {
        include view_path( theme_path('__partials/embed-player') );
    }
}

if(! function_exists( 'the_math_captcha' ) )
{
    function the_math_captcha($isCenter = false)
    {
        include view_path( theme_path('__partials/math-captcha') );
    }
}

if(! function_exists( 'the_embed_links_group' ) )
{
    function the_embed_links_group($activeMovie = null)
    {
        include view_path( theme_path("__partials/embed-links-group") );
    }
}



if(! function_exists( 'the_movie_meta_info' ) )
{
    function the_movie_meta_info($activeMovie = null)
    {
        include view_path( theme_path("__partials/movie-meta-info") );
    }
}

if(! function_exists( 'the_episode_label' ))
{
    function the_episode_label($season, $episode)
    {
        $sea = sprintf("%02d", $season);
        $epi = sprintf("%02d", $episode);

        return "S{$sea} : E{$epi}";
    }
}

if(! function_exists( 'get_genres_links' ))
{
    function get_genres_links( $genres , $type = 'movies')
    {
        $links = [];
        foreach ($genres as $genre) {
            $links[] = anchor("/library/{$type}?genres={$genre->name}", ucwords($genre->name), [
                'class' => ''
            ]);
        }
        return ! empty($links) ? implode(', ', $links) : '';
    }
}

if(! function_exists( 'format_seasons_list' ))
{
    function format_seasons_list($activeMovie,  $seasons )
    {
        $seasonsOptions = [];
        $episodesList = '';
        $seasonsList = '';

        foreach ($seasons as $season) {

            if(empty( $season->episodes ))
                continue;

            $seasonSelected = $season->season == $activeMovie->season;
            $selectedEpisode = 0;

            $seasonsOptions[$season->season] = "Season {$season->season}";

            $episodesOptions = '';
            foreach ($season->episodes as $episode) {

                $episodeLabel = "Episode {$episode->episode} : {$episode->title}";

                $selected = '';
                $episodeSelected = $episode->episode == $activeMovie->episode;
                if($seasonSelected && $episodeSelected) {
                    $selected = 'selected="selected"';
                    $selectedEpisode = $episode->episode;
                }

                $episodesOptions .= "<option value=\"{$episode->episode}\" data-imdb=\"{$episode->imdb_id}\"  {$selected}>";
                $episodesOptions .= $episodeLabel;
                $episodesOptions .= '</option>';

            }

            $hidden = ! $selectedEpisode ? 'display:none' : '';


            $episodesList .= '<select class="form-control episodes-select" id="sea-'.$season->season.'--episodes" style="'.$hidden.'">' . $episodesOptions . '</select>';



        }

        $seasonsList .= form_dropdown([
            'id' => 'season-select',
            'class' => 'form-control',
            'style' => 'max-width: 15rem',
            'options' => $seasonsOptions,
            'selected' => $activeMovie->season
        ]);

        return "{$seasonsList} {$episodesList}";

    }



}

if(! function_exists('has_display_banner_ad'))
{
    function has_display_banner_ad(string $identity,  array $ads): bool
    {
        if(isset( $ads[$identity] )){

            $ad = $ads[$identity];
            return  ! empty( $ad->ad_code );
        }

        return false;
    }
}

if(! function_exists('display_banner_ad'))
{
    function display_banner_ad(string $identity,  array $ads, $type = '728')
    {
        if(isset( $ads[$identity] )){

            $ad = $ads[$identity];
            $adCode = ! empty($ad->ad_code) ? base64_decode( $ad->ad_code ) : '' ;
            if(! empty($adCode)){

                $html  = '<div class="content">';
                $html .= '<div class="ve-ad--wrap ve-ad--'. $type .'">';
                $html .= $adCode;
                $html .= '</div>';
                $html .= '</div>';

                return $html;

            }

        }

        return '';

    }
}

if(! function_exists('display_managed_popup_ads'))
{
    function display_managed_popup_ads(array $units): string
    {
        $config = [];
        foreach ($units as $unit) {
            $code = trim((string) ($unit['ad_code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $config[] = [
                'id' => (int) ($unit['id'] ?? 0),
                'code' => $code,
            ];
        }

        if (empty($config)) {
            return '';
        }

        $encodedConfig = base64_encode(json_encode($config));

        $selectionUrl = json_encode(site_url('/traffic/popup-ad'));

        return '<script>(function(){'
            . 'var encoded="' . $encodedConfig . '";var units=[];'
            . 'try{units=JSON.parse(atob(encoded));}catch(e){return;}'
            . 'if(!units.length){return;}'
            . 'var today=(new Date()).toISOString().slice(0,10),key="streamapi:embed-popup:"+today;'
            . 'var byId=function(id){for(var i=0;i<units.length;i++){if(String(units[i].id)===String(id)){return units[i];}}return null;};'
            . 'var run=function(selected){if(!selected){return;}try{localStorage.setItem(key,String(selected.id||"selected"));}catch(e){}'
            . 'var holder=document.createElement("div");holder.style.display="none";holder.innerHTML=selected.code;'
            . 'var scripts=holder.querySelectorAll("script");Array.prototype.forEach.call(scripts,function(source){var script=document.createElement("script");'
            . 'Array.prototype.forEach.call(source.attributes,function(attribute){script.setAttribute(attribute.name,attribute.value);});'
            . 'if(source.src){script.src=source.src;}else{script.text=source.text||source.textContent||"";}source.parentNode.removeChild(source);document.head.appendChild(script);});'
            . 'while(holder.firstChild){document.body.appendChild(holder.firstChild);}};'
            . 'var saved=null;try{saved=localStorage.getItem(key);}catch(e){}if(saved&&byId(saved)){run(byId(saved));return;}'
            . 'var fallback=function(){run(units[Math.floor(Math.random()*units.length)]);};'
            . 'if(!window.fetch){fallback();return;}fetch(' . $selectionUrl . ',{credentials:"same-origin"})'
            . '.then(function(response){return response.ok?response.json():null;}).then(function(data){var selected=data&&byId(data.id);selected?run(selected):fallback();}).catch(fallback);'
            . '})();</script>';
    }
}

if(! function_exists('display_pop_ad'))
{
    function display_pop_ad(array $ads, array $managedUnits = []): string
    {
        if (! empty($managedUnits)) {
            return display_managed_popup_ads($managedUnits);
        }

        foreach ($ads as $ad) {
            if($ad->type == 'popad' && ! empty($ad->ad_code)){
                return base64_decode($ad->ad_code);
            }
        }

        return '';
    }
}


if(! function_exists( 'create_stream_servers_list' ))
{
    function create_stream_servers_list( $links )
    {
        $serversList = [];
        if(! empty($links)){

            foreach ($links as $key => $link) {

                $host = $link->getHost( true );

                //encoded id
                $encId = encode_id( $link->id );

                // Keep the model order. LinkModel orders this list by host
                // priority and last use, which is the basis of host rotation.
                $serversList[$encId] = $host;

            }
        }

        return $serversList;
    }
}


if(! function_exists('get_page_title'))
{
    function get_page_title( $title )
    {
        if(! empty($title)){

            $title .= ' - ' . site_name();

        }

        return $title;
    }
}

if(! function_exists('site_logo'))
{
    function site_logo(): string
    {
        $logoName = get_config('site_logo');
        return site_url('/uploads/' . $logoName);
    }
}

if(! function_exists('site_favicon'))
{
    function site_favicon(): string
    {
        $logoName = get_config('site_favicon');
        return site_url('/uploads/' . $logoName);
    }
}

if(! function_exists('has_site_logo'))
{
    function has_site_logo()
    {
        $logoName = get_config('site_logo');
        if(is_file( FCPATH . 'uploads/' . $logoName )){
            return true;
        }
        return false;
    }
}

if(! function_exists('has_site_favicon'))
{
    function has_site_favicon()
    {
        $logoName = get_config('site_favicon');
        if(is_file( FCPATH . 'uploads/' . $logoName )){
            return true;
        }
        return false;
    }
}

if(! function_exists('site_name'))
{
    function site_name(): string
    {
        return get_config('site_name');
    }
}

if(! function_exists('display_country_list'))
{
    function display_country_list( $countries ): string
    {
        if(! empty( $countries )){

            $results = [];

            foreach ($countries as $country) {
                $results[] = anchor(
                                library_url( [ 'country' => $country ] ),
                                $country
                            );
            }

            return implode(', ' , $results);
        }

        return '';
    }
}

if(! function_exists('display_language_list'))
{
    function display_language_list( $languages ): string
    {
        if(! empty( $languages )){

            $results = [];

            foreach ($languages as $lang) {
                $results[] = anchor(
                    library_url( [ 'lang' => $lang ] ),
                    ucwords( $lang )
                );
            }

            return implode(', ' , $results);
        }

        return '';
    }
}


if(! function_exists('display_alerts'))
{
    function display_alerts( ): string
    {
        $alert = '';
        if( session()->has('success') ){
            $alert = ' <div class="alert alert-success">' . esc( session()->get('success') ) . '</div>';
        }else if( session()->has('error') ) {
            $alert = ' <div class="alert alert-danger">' . esc( session()->get('error') ) . '</div>';
        }else if( session()->has('info') ){
            $alert = ' <div class="alert alert-primary">' . esc( session()->get('info') ) . '</div>';
        }

        if(! empty( $alert )){
            $alert = '<div class="content errors-content mt-0">' . $alert . '</div>';
        }

        return $alert;
    }
}

if(! function_exists('get_footer_menus'))
{
    function get_footer_menus(): array
    {
        $pageModel = new \App\Models\PagesModel();
        $pages = $pageModel->select('id, title, slug')
                           ->publicPages()
                           ->findAll();

        return ! empty( $pages ) ? $pages : [];
    }
}


