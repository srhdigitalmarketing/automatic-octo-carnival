<?php

namespace App\Entities;


class Link extends \CodeIgniter\Entity\Entity
{

    public function getHost($rewrite = false)
    {
        $host = 'unknown';
        if(! empty($this->link)){
            $host = str_ireplace('www.', '', parse_url($this->link, PHP_URL_HOST));
        }

        //rewrite server names
        if($rewrite){
            $renamedServers = get_config('renamed_servers');
            if(! empty( $renamedServers[$host] )){
                $host = $renamedServers[$host];
            }
        }

        return $host;
    }


    public function getEncLink(): string
    {
        $link =  link_slug() . '/' . encode_id( $this->id );

        return site_url( $link );
    }

    public function countReports(): int
    {
        return (int) $this->reports_not_working + (int) $this->reports_wrong_link;
    }

    public function isApiBased(): bool
    {
        return ! empty($this->api_id);
    }


}