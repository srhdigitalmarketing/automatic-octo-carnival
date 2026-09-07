<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Movie;
use App\Models\LinkModel;
use App\Models\Translations\MovieTranslationsModel;


class Movies extends BaseController
{

    protected $model;
    protected $translation;
    protected $mediaWarnings = [];

    public function __construct()
    {
        $this->model = new \App\Models\MovieModel();

        if(get_config('is_multi_lang')) {
            $this->translation = new MovieTranslationsModel();
        }

    }

    public function index()
    {

        $type = class_basename($this) != 'Episodes' ? 'movie' : 'episode';
        $isMovie = $type == 'movie';

        $title = $isMovie ? 'Videos' : 'Episodes';

        $filter = $this->request->getGet('filter');
        $allowedFilters = [
            'with_st_links',
            'without_st_links',
            'good_health_servers',
            'bad_health_network'
        ];

        // The table itself is loaded page-by-page through the DataTables AJAX
        // endpoint.  Only calculate the count here, so opening this page does
        // not fetch every video into PHP and the browser at once.
        $countModel = new \App\Models\MovieModel();

        if(in_array($filter, $allowedFilters)){

            $linkType = '';

            if($filter == 'with_st_links' || $filter == 'without_st_links')
                $linkType = 'stream';

            if($filter == 'with_st_links')
                $countModel->notEmptyLinks( $linkType );

            if($filter == 'without_st_links')
                $countModel->emptyLinks( $linkType );

            if($filter == 'good_health_servers')
                $countModel->withHealthyStreamLinks();

            if($filter == 'bad_health_network')
                $countModel->withUnhealthyStreamLinks();

        }

        $moviesCount = $countModel->where('type', $type)->countAllResults();



        if( $isMovie ){

            $topBtnGroup = create_top_btn_group([
                'admin/movies/new' => 'Add Video'
            ]);

        }else{

            $topBtnGroup = create_top_btn_group([
                'admin/episodes/new' => 'Add Episode'
            ]);

        }

        $title .= ' - ( ' . $moviesCount . ' )';

        $data = [
            'title' => $title,
            'filter' => $filter,
            'topBtnGroup' => $topBtnGroup
        ];
        return view('admin/movies/list', $data);
    }

    public function new()
    {
        $title = 'New Video';
        $translations = null;

        $topBtnGroup = create_top_btn_group([
            'admin/movies' => 'Back to videos'
        ]);

        $movie = new Movie();
        $movie->imdb_id = $this->request->getGet('imdb');
        $movie->type = 'movie';

        if( is_multi_languages_enabled() ){

            //Translations
            $translations = $this->translation->getDummyList();

        }

        $data = compact('title', 'movie', 'translations', 'topBtnGroup');
        return view('admin/movies/new', $data);
    }

    public function edit($id)
    {
        $title = 'Edit Video';
        $movie = $this->getMovie($id);
        $translations = null;

        if($movie->isEpisode()) {
            return redirect()->to("/admin/episodes/edit/{$id}");
        }

        $linkModel = new LinkModel();
        $streamLinks = $linkModel->findByMovieId( $id, 'stream' );

       if( is_multi_languages_enabled() ){

           //Translations
           $translations = $this->translation->findByMovieId( $id );

       }


        $topBtnGroup = create_top_btn_group([
            'admin/movies/new' => 'New Video',
            'admin/movies' => 'Back to Videos'
        ]);

        $data = compact('title', 'movie', 'streamLinks', 'translations', 'topBtnGroup');

        return view('admin/movies/edit', $data);
    }

    public function create()
    {
        $warningAlerts = [];

        if($this->request->getMethod() == 'post'){
            //create movie entity
            $movie = new Movie( $this->request->getPost() );

            $usesR2BannerStorage = \App\Libraries\CloudflareR2Storage::active() !== null;
            if(! is_media_download_to_server() && ! $usesR2BannerStorage){
                $movie->banner = $movie->banner_url;
            }


            //attempt to save data
            if($this->model->saveMovie( $movie )) {
                $movie = $this->getMovie( $this->model->getInsertID() );

                //add translations
                if( is_multi_languages_enabled() ){

                    $this->model->addTranslations(
                        $movie->id,
                        $this->request->getPost( 'translations' )
                    );

                }

                //save media files
                if( is_media_download_to_server() || $usesR2BannerStorage ){
                    $this->saveMediaFiles( $movie );
                }

                //save links
                $this->saveLinks( $movie );

                $warningAlerts = array_merge($this->mediaWarnings, $this->validator !== null ? $this->validator->getErrors() : []);

                return redirect()->to('/admin/movies/edit/' . $movie->id)
                                 ->with('warning', $warningAlerts)
                                 ->with('success', 'movie saved successfully');

            }else{
                return redirect()->back()
                                 ->with('errors', $this->model->errors())
                                 ->withInput();
            }
        }
    }

    public function update( $id )
    {
        if($this->request->getMethod() == 'post') {
            $movie = $this->getMovie($id);
            $previousBanner = $this->request->getPost('remove_banner') === '1' ? $movie->banner : null;
            if ($previousBanner !== null) {
                $movie->banner = null;
            }
            $updatedData = $this->request->getPost([
                'title',
                'description',
                'imdb_id',
                'duration',
                'series_id',
                'season',
                'imdb_rate',
                'episode',
                'released_at',
                'trailer',
                'country',
                'language',
                'status'
            ]);

            $usesR2BannerStorage = \App\Libraries\CloudflareR2Storage::active() !== null;
            if(! is_media_download_to_server() && ! $usesR2BannerStorage){
                if(! empty( $this->request->getPost('banner_url') )){
                    $movie->banner = $this->request->getPost('banner_url');
                }
            }

            $movie->fill( $updatedData );

            //attempt to save movie
            if(! $this->model->saveMovie($movie)) {
                return redirect()->back()
                    ->with('errors', $this->model->errors())
                    ->withInput();
            }

            // The helper removes a local upload, or an R2 object owned by this
            // application. It never deletes an arbitrary remote URL.
            if ($previousBanner !== null && $previousBanner !== '') {
                delete_banner($previousBanner);
            }

            // save translations
            if( is_multi_languages_enabled() ){

                $this->model->addTranslations(
                    $movie->id,
                    $this->request->getPost( 'translations' )
                );

            }

            // save media files
            if(is_media_download_to_server() || $usesR2BannerStorage){
                $this->saveMediaFiles( $movie );
            }

            // save links
            $this->saveLinks( $movie );

            $warningAlerts = array_merge($this->mediaWarnings, $this->validator !== null ? $this->validator->getErrors() : []);

            return redirect()->back()
                             ->with('warning', $warningAlerts)
                             ->with('success', 'Movie updated successfully.');
        }
    }

    public function delete( $id )
    {
        $movie = $this->getMovie( $id );

        if($this->model->delete( $movie->id )) {

            $redirect = $movie->isEpisode() ? '/episodes' : '/movies';

            return redirect()->to("/admin/{$redirect}")
                             ->with('success', "{$movie->title} movie deleted successfully" );
        }else{
            return redirect()->back()
                ->with('errors', "{$movie->title} movie unable to deleted" );
        }

    }


    protected function saveMediaFiles(\App\Entities\Movie $movie)
    {
        $imageValidationRules = [];

        $bannerFile = $this->request->getFile('banner_file');

        if(! $bannerFile->isValid()) $bannerFile = null;

        if($bannerFile !== null) {
            $imageValidationRules['banner_file'] =[
                'label' => 'banner image',
                'rules' => 'uploaded[banner_file]'
                    . '|is_image[banner_file]'
                    . '|mime_in[banner_file,image/jpg,image/jpeg,image/png,image/webp]'
                    . '|max_size[banner_file,4096]'
            ];
        }

        if(! empty($imageValidationRules) ) {

            $this->validate( $imageValidationRules );

            if($this->validator->hasError('banner_file'))
                $bannerFile = null;

        }

        if($bannerFile !== null){
            $previousBanner = $movie->banner;
            $r2 = \App\Libraries\CloudflareR2Storage::active();
            $storedInR2 = $r2 !== null;

            if ($storedInR2) {
                try {
                    $movie->banner = $r2->uploadBanner($bannerFile);
                } catch (\Throwable $exception) {
                    log_message('error', 'Cloudflare R2 banner upload failed: {message}', ['message' => $exception->getMessage()]);
                    $this->mediaWarnings[] = 'Banner could not be published from Cloudflare R2. Enable the bucket public URL or configure a public custom domain, then try again.';
                    return;
                }
            } else {
                // Keep existing local storage as a fallback when R2 is not configured.
                $movie->addBanner( $bannerFile );
            }

            if($movie->hasChanged()) {
                if (! $this->model->skipValidation(true)->save($movie)) {
                    $this->mediaWarnings[] = 'Banner could not be saved after upload. Please try again.';
                    return;
                }
            }
            if ($storedInR2 && ! empty($previousBanner) && $previousBanner !== $movie->banner) {
                delete_banner($previousBanner);
            }
        }

    }

    protected function saveLinks(\App\Entities\Movie $movie)
    {
        $linkGroups = [
            'st_links' => 'stream',
            'direct_dl_links' => 'direct_download',
            'torrent_dl_links' => 'torrent_download',
        ];

        foreach ($linkGroups as $inputName => $type) {
            $links = $this->request->getPost($inputName);
            if (is_array($links)) {
                $this->model->addLinks($movie->id, $links, $type);
            }
        }

    }



    protected function getMovie($id) : \App\Entities\Movie
    {
        $movie = $this->model->getMovie($id);
        if($movie == null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid movie Id ' . $id);
        }
        return $movie;
    }



}
