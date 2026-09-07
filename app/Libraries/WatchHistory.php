<?php

namespace App\Libraries;


class WatchHistory
{
    protected $keyVal = null;
    protected $keyName = 'watched_key';
    protected $data = [];
    protected $limit = 0;
    protected $expire = 60 * 60 * 24 * 30; //30 days


    public function __construct()
    {
        helper('cookie');
        $this->limit  = get_config('watch_history_limit');
        $this->init();
    }

    public function add( $val )
    {
        //remove old val if is it exist
        $this->remove( $val );

        //check limit
        if(count( $this->data ) == $this->limit){
            array_pop($this->data);
        }

        //add val to data list
        array_unshift($this->data, $val);

        return $this;
    }

    public function has( $val ): bool
    {
        //check val exist
        return in_array($val, $this->data);
    }

    public function get()
    {
        return $this->data;
    }

    public function remove( $val )
    {
        //remove val if is it exist
        if($this->has( $val )){
            $valKey = array_search($val, $this->data);
            unset( $this->data[$valKey] );
        }
        return $this;
    }

    public function save()
    {
        if(! empty($this->data) && is_array( $this->data )){
            $key = UniqToken::create( $this->data );

            //update key
            $this->keyVal = $key;

            //save
            set_cookie($this->keyName, $key, $this->expire);
        }
    }

    protected function init()
    {
        $key = get_cookie( $this->keyName );

        if(! empty( $key )){
            $results = UniqToken::decode( $key );
            if(! empty( $results ) && is_array( $results )){
                $this->data = $results;
                $this->keyVal = $key;
            }
        }
    }

}