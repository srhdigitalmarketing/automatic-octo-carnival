<?php

namespace App\Models;


use CodeIgniter\Model;

/**
 * Class RequestsModel
 * @package App\Models
 * @author John Antonio
 */
class RequestSubscriptionModel extends Model
{
    protected $table            = 'requests_subscription';
    protected $returnType       = 'App\Entities\Request';
    protected $allowedFields    = ['request_id', 'email'];

    protected $validationRules = [
        'request_id' => 'required|exist[requests.id]',
        'email' => 'required|valid_email'
    ];


    public function getSubscription(int $reqId, $email )
    {
        return $this->where('request_id', $reqId)
                    ->where('email', $email)
                    ->first();
    }

    public function getAllSubscriptionByReqId( $reqId ): array
    {
        $results = [];
        if(! empty( $reqId )){
            $results = $this->where('request_id', $reqId)
                            ->findAll();
        }

        return $results;
    }

    public function subscribe(int $reqId, $email )
    {
        $data = [
            'request_id' => $reqId,
            'email' => $email
        ];

        return $this->insert( $data );
    }

    public function sendMail(int $reqId, $data = [])
    {
        $emailList = $this->asArray()
                          ->select('email')
                          ->getAllSubscriptionByReqId( $reqId );

        if(! empty( $emailList )){

            $emailList = extract_array_data($emailList, 'email');
            $email = new \App\Libraries\Email();

            if($email->isReady()){

                if($email->setTemplate('movie-request')
                         ->setData( $data )
                         ->load()){

                    $subject = $data['title'] ?? 'Movie Request';
                    $subject = lang('General.watch') . ' ' . $subject;

                    $email->base->setBCC( $emailList );
                    $email->base->setSubject( $subject );
                    $email->base->send();

                }

            }

        }

    }

}
