<?php

// =============== EMBED FORM ===============
return [

    'finding_label' => 'Find By',
    'tab_select_with_ids' => 'IMDB & TMDB Id',
    'tab_select_with_title' => 'Title & Year',

    'tabs' => [
        'movie_label' => 'Movie',
        'series_label' => 'TV Show',
        'api_label' => 'API',
    ],

    // =============== FIND BY IMDB or TMDB Ids ===============
    'ids_tab_content' => [

        //MOVIES TAB CONTENT
        'movie' => [
            'title' =>
                'Try 
                %s IMDB %s or 
                %s TMDB %s
                id To Get Movie',
            'form' => [
                'input_imdb' => [
                    'label' => 'Enter IMDB or TMDB id',
                    'placeholder' => 'Ex: tt6468322'
                ],
                'submit_btn_txt' => 'Get it'
            ]
        ],

        //TV SHOWS TAB CONTENT
        'series' => [
            'title' => 'Try %s IMDB %s or %s TMDB %s id To Get TV Show',
            'form' => [
                'input_imdb' => [
                    'label' => 'Enter IMDB or TMDB id',
                    'placeholder' => 'Ex: tt6468322'
                ],
                'input_season' => [
                    'label' => 'Season',
                    'placeholder' => 'Ex: 1'
                ],
                'input_episode' => [
                    'label' => 'Episode',
                    'placeholder' => 'Ex: 1'
                ],
                'submit_btn_txt' => 'Get it'
            ]
        ]
    ],
    'title_tab_content' => [

        //MOVIES TAB CONTENT
        'movie' => [
            'title' =>
                'Try 
                %s Title %s or 
                %s Year %s
                id To Get Movie',
            'form' => [
                'input_title' => [
                    'label' => 'Enter Title',
                    'placeholder' => 'The Batman'
                ],
                'input_year' => [
                    'label' => 'Year',
                    'placeholder' => 'Ex: 2009'
                ]
            ]
        ],

        //TV SHOWS TAB CONTENT
        'series' => [
            'title' =>
                'Try 
                %s Title %s or 
                %s Year %s
                id To Get TV Show',
            'form' => [
                'input_title' => [
                    'label' => 'Enter Title',
                    'placeholder' => 'The Flash'
                ],
                'input_year' => [
                    'label' => 'Year',
                    'placeholder' => 'Ex: 2016'
                ]
            ]
        ],

    ],
    //API TAB CONTENT
    'api_tab_content' => [
        'movie_title' => '%s Movie %s API',
        'series_title' => '%s TV Show %s API',
        'learn_more_btn_txt' => 'Learn more'
    ],

    'form_version' => 'Quick Embed Form'


];