<?php

namespace App\Libraries;


class Email
{

    protected $template = null;
    protected $data = [];
    protected $bodyContent = null;
    public $base;

    public function __construct()
    {
        $this->base = \Config\Services::email();

        if(! empty(smtp_config('host'))){

            $config = [
                'SMTPHost' => smtp_config('host'),
                'SMTPUser' => smtp_config('user'),
                'SMTPPass' => smtp_config('pass'),
                'SMTPPort' => smtp_config('port'),
                'protocol' => 'smtp'
            ];
            $this->base->initialize($config);
        }

        //default sender mail
        $this->base->setFrom(get_config('email_address'), site_name());
        $this->base->setReplyTo('');
    }

    /**
     * Set Email Template
     * @param $template
     * @return $this
     */
    public function setTemplate( $template ): Email
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Set data for mail
     * @param array $data
     * @return $this
     */
    public function setData(array $data): Email
    {
        $this->data = $data;
        return $this;
    }

    public function load($setBody = true): bool
    {
        $templatePath = view_path('/email/' . $this->template);
        if($templatePath !== null){

            ob_start();

            $data = $this->data;
            include_once $templatePath;

            $this->bodyContent = ob_get_clean();

            if($setBody){
                $this->base->setMessage( $this->bodyContent );
            }
            return true;
        }

        return false;
    }

    public function isReady(): bool
    {
        return ! empty( get_config('email_address') );
    }

    public function getBodyContent()
    {
        return $this->bodyContent;
    }




}