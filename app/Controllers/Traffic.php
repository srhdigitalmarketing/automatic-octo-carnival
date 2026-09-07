<?php

namespace App\Controllers;

use App\Libraries\PopupAdSelector;
use App\Libraries\MysqlAnalytics;

class Traffic extends BaseController
{
    public function popup_ad()
    {
        try {
            $id = (new PopupAdSelector())->selectId();
            return $this->response->setJSON(['id' => $id]);
        } catch (\Throwable $exception) {
            log_message('warning', 'Popup ad selection failed: {message}', ['message' => $exception->getMessage()]);
            return $this->response->setStatusCode(503)->setJSON(['id' => null]);
        }
    }

    public function embed()
    {
        // This endpoint is only used by the background player analytics request.
        // If a browser submits to it normally (for example after an accidental
        // form submission), return the visitor to the previous page instead of
        // rendering a raw JSON response such as {"ok":false}.
        if (! $this->request->isAJAX()) {
            return redirect()->back();
        }

        $visitorKey = trim((string) $this->request->getPost('visitor_key'));

        if (! preg_match('/^[a-zA-Z0-9_-]{16,64}$/', $visitorKey)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON(['ok' => false]);
        }

        try {
            (new MysqlAnalytics())->record(
                $visitorKey,
                MysqlAnalytics::platform((string) $this->request->getUserAgent()),
                $this->request->getPost('record_impression') === '1',
                $this->request->getPost('event') === 'play'
            );
        } catch (\Throwable $exception) {
            log_message('error', 'MySQL analytics request failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return $this->response
                ->setStatusCode(503)
                ->setJSON(['ok' => false]);
        }

        return $this->response->setJSON(['ok' => true]);
    }
}
