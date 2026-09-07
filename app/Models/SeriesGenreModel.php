<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class SeriesGenreModel
 * @package App\Models
 * @author John Antonio
 */
class SeriesGenreModel extends Model
{
    protected $table = 'series_genre';
    protected $allowedFields = [ 'series_id', 'genre_id' ];
    protected $validationRules = [
        'series_id' => 'required|is_natural_no_zero|exist[series.id]',
        'genre_id' => 'required|is_natural_no_zero|exist[genres.id]'
    ];
    protected $returnType = 'App\Entities\Genre';

    /**
     * Get genres by series id
     * @param int|null $seriesId
     * @return array|null
     */
    public function getGenresBySeriesId(?int $seriesId): ?array
    {
        if(empty($seriesId))
            return null;

        $genres = [];

        $results = $this->join('genres', 'genres.id = series_genre.genre_id', 'LEFT')
                        ->where('series_id', $seriesId)
                        ->select('series_genre.series_id, series_genre.genre_id as id, genres.name')
                        ->findAll();

        if(! empty($results)){
            foreach ($results as $result) {
                $genres[$result->id] = $result;
            }
        }
        return $genres;
    }

}