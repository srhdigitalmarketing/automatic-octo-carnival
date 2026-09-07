<?php
namespace App\Libraries;

/** Read-only VidHide/EarnVids File Info client, using the endpoint in VidHide's documentation. */
class VidHideClient
{
    private $token;
    private $transport;
    public function __construct(string $token, ?callable $transport = null)
    {
        $this->token = trim($token);
        $this->transport = $transport;
    }

    public function videoStatus(string $id): array
    {
        $unknown = ['status'=>'unknown', 'message'=>'VidHide check failed'];
        if ($this->token === '' || !preg_match('/^[A-Za-z0-9_-]{3,128}$/', $id)) {
            return ['status'=>'unknown','message'=>'VidHide token or video ID is missing/invalid'];
        }
        try {
            if ($this->transport) {
                $response = ($this->transport)($id);
            } else {
                $http = \Config\Services::curlrequest([
                    'timeout'=>8, 'connect_timeout'=>5, 'http_errors'=>false,
                    'allow_redirects'=>false, 'verify'=>true,
                ], null, null, false)->get('https://earnvidsapi.com/api/file/info', [
                    'query'=>['key'=>$this->token, 'file_code'=>$id],
                ]);
                $response = ['http'=>(int)$http->getStatusCode(), 'body'=>json_decode((string)$http->getBody(), true)];
            }
        } catch (\Throwable $error) {
            // URLs include the API key; never log the exception or response body.
            return ['status'=>'unknown','message'=>'VidHide connection failed'];
        }
        $body = $response['body'] ?? null;
        $httpStatus = (int)($response['http'] ?? 0);
        $apiStatus = is_array($body) ? (int)($body['status'] ?? 0) : 0;
        if (in_array($httpStatus, [404,410,522], true) || ($httpStatus === 200 && in_array($apiStatus, [404,410,522], true))) {
            return ['status'=>'unknown','skip_playback'=>true,
                'message'=>'VidHide/EarnVids check failed (' . ($httpStatus === 200 ? 'API ' . $apiStatus : 'HTTP ' . $httpStatus) . ')'];
        }
        if (($response['http'] ?? 0) !== 200 || !is_array($body) || (int)($body['status'] ?? 0) !== 200 || !is_array($body['result'] ?? null)) {
            return $unknown;
        }
        foreach ($body['result'] as $file) {
            if (!is_array($file) || (string)($file['file_code'] ?? '') !== $id) { continue; }
            $status = (int)($file['status'] ?? 0);
            if ($status === 404 || $status === 410) {
                return ['status'=>'deleted','message'=>'VidHide reports this file as not found in the configured account (' . $status . ')' ];
            }
            if ($status === 522) { return ['status'=>'unknown','skip_playback'=>true,'message'=>'VidHide/EarnVids file check failed (522)']; }
            if ($status !== 200) { return $unknown; }
            if (!in_array($file['canplay'] ?? null, [0,1,'0','1',true,false], true)) { return $unknown; }
            $canPlay = (bool)$file['canplay'];
            return $canPlay
                ? ['status'=>'available','message'=>'VidHide reports the video as playable']
                : ['status'=>'error','message'=>'VidHide reports that this video cannot be played'];
        }
        return $unknown;
    }
}
