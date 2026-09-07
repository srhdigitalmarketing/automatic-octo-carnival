<?php

if (! function_exists('validate_gcaptcha')) {
    function validate_gcaptcha($response)
    {
        if (! is_string($response) || $response === '') { return false; }
        try {
            $client = \Config\Services::curlrequest([
                'timeout' => 10, 'connect_timeout' => 5,
                'http_errors' => false, 'verify' => true,
            ], null, null, false);
            $result = $client->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => ['secret' => get_config('gcaptcha_secret_key'), 'response' => $response],
            ]);
            $data = json_decode($result->getBody(), true);
            return $result->getStatusCode() === 200 && is_array($data) && ($data['success'] ?? false) === true;
        } catch (\Throwable $error) {
            log_message('warning', 'Captcha verification unavailable.');
            return false;
        }
    }
}
