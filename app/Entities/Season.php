<?php

namespace App\Entities;

use App\Models\SeriesModel;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Model;


class Season extends \CodeIgniter\Entity\Entity
{

    public $series = null;


    public function series()
    {
        if($this->series === null){

            $this->series = new Series();

            $seriesModel = new SeriesModel();
            $series = $seriesModel->where('id', $this->series_id)
                                  ->first();
            if(! empty($series)){
                $this->series = $series;
            }

        }

        return  $this->series;

    }

    public function episodes()
    {
        return is_array($this->episodes) ? $this->episodes : [];
    }

    public function hasEpisodes(): bool
    {
        return ! empty($this->episodes);
    }


}