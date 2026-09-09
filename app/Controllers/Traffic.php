<?php

namespace App\Controllers;

use App\Libraries\PopupAdSelector;

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

    /** Compatibility for previously opened players: no tracking or database writes. */
    public function embed()
    {
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
        return $this->response->setJSON(['ok' => true, 'tracking' => 'disabled']);
    }
}
