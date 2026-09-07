<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class MovieGenreModel
 * @package App\Models
 * @author John Antonio
 */
class MovieGenreModel extends Model
{
    protected $table = 'movie_genre';
    protected $allowedFields = [ 'movie_id', 'genre_id' ];
    protected $validationRules = [
        'movie_id' => 'required|is_natural_no_zero|exist[movies.id]',
        'genre_id' => 'required|is_natural_no_zero|exist[genres.id]'
    ];
    protected $returnType = 'App\Entities\Genre';


    /**
     * Get all genres by movie id
     * @param int|null $movieId
     * @return array|null
     */
    public function getGenresByMovieId(?int $movieId ): ?array
    {
        if(empty($movieId))
            return null;

        $genres = [];

        $results = $this->join('genres', 'genres.id = movie_genre.genre_id', 'LEFT')
                        ->where('movie_id', $movieId)
                        ->select('movie_genre.movie_id, movie_genre.genre_id as id, genres.name')
                        ->findAll();

        if(! empty($results)){
            foreach ($results as $result) {
                $genres[$result->id] = $result;
            }
        }
        return $genres;
    }

    /**
     * Find by movie id
     * @param int $movie_id
     * @return array
     */
    public function findByMovieId(int $movie_id): array
    {
        if(empty($movie_id))
            return [];

        return $this->where('movie_id', $movie_id)
                    ->findAll();

    }

}