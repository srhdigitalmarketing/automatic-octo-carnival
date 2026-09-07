<?php

namespace App\Models;

use App\Entities\Translation;
use App\Models\Translations\GenreTranslationsModel;
use CodeIgniter\Model;

/**
 * Class GenreModel
 * @package App\Models
 * @author John Antonio
 */
class GenreModel extends Model
{
    protected $table = 'genres';
    protected $allowedFields = [ 'name' ];
    protected $validationRules  = [
        'name' => 'required|alpha_numeric_punct|is_unique[genres.name]'
    ];
    protected $useSoftDeletes = true;
    protected $returnType = 'App\Entities\Genre';
    protected $beforeDelete = ['softDelActiveGenres'];
    protected $afterFind  = ['translateLanguage'];


    protected function translateLanguage(array $data): array
    {
        if(! is_multi_languages_enabled()){
            return $data;
        }

        if(! has_translate_permit())
            return $data;

        $language = current_language();
        if(empty( $language ) || $language == 'en')
            return $data;


        $genres = $data['data'];
        $isFirst = $data['method'] == 'first';
        if($isFirst) {
            $genres = [ $genres ];
        }

        $translationModel = new GenreTranslationsModel();

        foreach ($genres as $k => $genre) {
            if($genre instanceof \App\Entities\Genre){
                $translate = $translationModel->findByGenreId($genre->id, $language);
                if($translate !== null){

                    if(! empty( $translate->name ))
                        $genre->name = $translate->name;

                }
            }else{
                $translate = $translationModel->findByGenreId($genre['id'], $language);
                if($translate !== null){

                    if(! empty( $translate->name ))
                        $genres[$k]['name'] = $translate->name;

                }
            }

        }

        $data['data'] = $isFirst ? $genres[0] : $genres;
        return $data;
    }

    /**
     * Get all genres with movies and shows counts
     * @return array
     */
    public function getAllGenresWithCounts(): array
    {

        $genres = [];

        $movieGenres =  $this->join('movie_genre', 'movie_genre.genre_id = genres.id', 'LEFT')
                             ->select('genres.*')
                             ->selectCount('movie_genre.movie_id', 'num_of_movies')
                             ->groupBy('genres.name')
                             ->orderBy('genres.name')
                             ->findAll();

        $seriesGenres =  $this->join('series_genre', 'series_genre.genre_id = genres.id', 'LEFT')
                              ->select('genres.*')
                              ->selectCount('series_genre.series_id', 'num_of_series')
                              ->groupBy('genres.name')
                              ->orderBy('genres.name')
                              ->findAll();

        if(! empty($movieGenres)){

            foreach ($movieGenres as $val) {
                $genres[$val->name] = $val;
            }

            foreach ($seriesGenres as $val) {
                if(array_key_exists($val->name, $genres)){
                    $genres[$val->name]->num_of_series = $val->num_of_series;
                }
            }

        }

        return $genres;
    }

    public function addTranslations(int $genreId, ?array $translations = null)
    {

        if(empty( $translations )){

            return false;

        }


        $translationsModel = new GenreTranslationsModel();
        $existTranslations = $translationsModel->findByGenreId( $genreId );


        foreach ($translations as $langCode => $translation) {

            $data = [
                'genre_id' => (string) $genreId,
                'lang' => strtolower($langCode),
                'name' => $translation
            ];

            if(array_key_exists( $langCode, $existTranslations )){

                $tmpTranslation = $existTranslations[$langCode];
                $tmpTranslation->fill( $data );

            }else{

                $tmpTranslation = new Translation( $data );

            }

            if($tmpTranslation->hasChanged()){
                try{

                    //we does not care about saving errors
                    $translationsModel->save( $tmpTranslation );
                }catch (\ReflectionException $e){}
            }

        }

        return true;
    }


    /**
     * Check current genre has movies
     * @param int $genreId
     * @return bool
     */
    public function hasMovies(int $genreId): bool
    {
        $movieGenre = new MovieGenreModel();
        $results = $movieGenre->where('genre_id', $genreId)
                              ->first();
        return ! empty($results);
    }

    /**
     * Check current genre has shows
     * @param int $genreId
     * @return bool
     */
    public function hasSeries(int $genreId): bool
    {
        $movieGenre = new SeriesGenreModel();
        $results = $movieGenre->where('genre_id', $genreId)
                              ->first();
        return ! empty($results);
    }

    /**
     * Get genre by name
     * @param string $genre
     * @return array|object|null
     */
    public function getGenreByName(string $genre)
    {
        return $this->where('name', strtolower($genre))
                    ->first();
    }

    /**
     * Restore deleted genre
     * @param int $genreId
     * @return bool
     */
    public function restore(int $genreId): bool
    {
        try{
            return $this->protect(false)
                        ->update($genreId, ['deleted_at' => null]);
        }catch (\ReflectionException $e) {}
    }


    /**
     * Soft delete active genres
     * @param array $data
     * @return array
     */
    protected function softDelActiveGenres(array $data): array
    {
        $genreId = array_shift($data['id']);
        if(!$this->hasMovies( $genreId ) && !$this->hasSeries( $genreId )){
            $this->useSoftDeletes = false;
        }
        return $data;
    }



}