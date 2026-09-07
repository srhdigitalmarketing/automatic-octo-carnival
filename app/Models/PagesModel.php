<?php

namespace App\Models;


use App\Entities\Translation;
use App\Models\Translations\PageTranslationsModel;
use CodeIgniter\Model;

/**
 * Class Pages
 * @package App\Models
 * @author John Antonio
 */
class PagesModel extends Model
{
    protected $table            = 'pages';
    protected $returnType       = 'App\Entities\Page';
    protected $allowedFields    = ['title', 'content', 'slug', 'meta_keywords', 'meta_description',  'status'];
    protected $useTimestamps = true;

    // Validation
    protected $validationRules      = [
        'title' => 'required|max_length[255]',
        'slug' => 'required|alpha_dash|max_length[128]|is_unique[pages.slug]',
        'status' => 'permit_empty|in_list[public,draft]'
    ];


    protected $validationMessages = [
        'slug' => [
            'is_unique' => 'page slug is already exist'
        ]
    ];

    protected $afterFind = ['translateLanguage'];

    protected function translateLanguage(array $data): array
    {

        if(! is_multi_languages_enabled()){
            return $data;
        }

        $language = current_language();

        if(! has_translate_permit())
            return $data;


        if(empty( $language ) || $language == 'en')
            return $data;



        $pages = $data['data'];
        $isFirst = $data['method'] == 'first';
        if($isFirst) {
            $pages = [ $pages ];
        }

        $TranslationModel = new PageTranslationsModel();

        foreach ($pages as $page) {
            if($page instanceof \App\Entities\Page){

                $translate = $TranslationModel->findByPageId($page->id, $language);
                if($translate !== null){

                    if(! empty( $translate->title ))
                        $page->title = $translate->title;


                    if(! empty( $translate->content ))
                        $page->content = base64_decode( $translate->content );

                }

            }
        }

        $data['data'] = $isFirst ? $pages[0] : $pages;

        return $data;
    }



    public function addTranslations(int $pageId, ?array $translations): bool
    {

        if(empty( $translations )){

            return false;

        }

        $translationsModel = new PageTranslationsModel();
        $existTranslations = $translationsModel->findByPageId( $pageId );


        foreach ($translations as $langCode => $translation) {

            $data = [
                'page_id' => (string) $pageId,
                'lang' => strtolower($langCode),
                'title' => $translation['title'],
                'content' => base64_encode( $translation['content'] )
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


    public function getPageBySlug($slug)
    {
        return $this->where('slug', $slug)
                    ->first();
    }

    public function publicPages(): PagesModel
    {
        $this->where('status', 'public');
        return $this;
    }



}
