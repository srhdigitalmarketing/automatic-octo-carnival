<?php

namespace App\Controllers\Admin\Settings;


use App\Models\LinkModel;
use CodeIgniter\Model;

class Servers extends BaseSettings
{

    public function index()
    {
        $title = 'Servers Settings';

        $linksModel = new LinkModel();
        $streamHealthAvailable = $linksModel->supportsStreamHealthFields();
        $links = $linksModel->select($streamHealthAvailable ? 'link, type, host_priority' : 'link, type')->findAll();

        $servers = (array) get_config('renamed_servers');
        $serverLinkCounts = [];
        $serverStreamLinkCounts = [];
        $serverPriorities = [];

        if(! empty($links)){

            foreach ($links as $link) {
                $host = $link->getHost();
                if(! empty($host)){
                    if(! isset( $servers[$host] )){
                        $servers[$host] = '';
                    }
                    $serverLinkCounts[$host] = ($serverLinkCounts[$host] ?? 0) + 1;

                    if ($link->type === 'stream') {
                        $serverStreamLinkCounts[$host] = ($serverStreamLinkCounts[$host] ?? 0) + 1;

                        // Existing installations can contain different values for
                        // one host. Showing the highest value is the safest default
                        // until this screen applies one global host preference.
                        $priority = $streamHealthAvailable ? (int) ($link->host_priority ?? 100) : 100;
                        $serverPriorities[$host] = isset($serverPriorities[$host])
                            ? max($serverPriorities[$host], $priority)
                            : $priority;
                    }
                }
            }

        }

        $serverOptions = [];
        if(! empty( $servers )){
            foreach ($servers as $key => $val) {
                if(! empty($val)){
                    $serverOptions[$val] = $val;
                }else{
                    $serverOptions[$key] = $key;
                }
            }
        }


        return view('admin/settings/servers', compact(
            'title',
            'servers',
            'serverOptions',
            'serverLinkCounts',
            'serverStreamLinkCounts',
            'serverPriorities',
            'streamHealthAvailable'
        ));
    }

    /**
     * Remove a host configuration and every stored link that points to it.
     * A host is derived from links, so deleting only its label would cause it
     * to be listed again the next time the settings page is opened.
     */
    public function delete()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->back();
        }

        $host = trim((string) $this->request->getPost('host'));
        if ($host === '') {
            return redirect()->back()->with('errors', 'Server host is required.');
        }

        $linksModel = new LinkModel();
        $links = $linksModel->select('id, link')->findAll();
        $linkIds = [];

        foreach ($links as $link) {
            if ($link->getHost() === $host) {
                $linkIds[] = $link->id;
            }
        }

        if ($linkIds !== []) {
            $linksModel->whereIn('id', $linkIds)->delete();
        }

        $servers = (array) get_config('renamed_servers');
        $deletedName = ! empty($servers[$host]) ? $servers[$host] : $host;
        unset($servers[$host]);

        $defaultServer = (string) get_config('default_server');
        if ($defaultServer === $host || $defaultServer === $deletedName) {
            $defaultServer = '';
        }

        return $this->save([
            'renamed_servers' => json_encode($servers),
            'default_server' => $defaultServer,
        ]);
    }


    public function update()
    {


        if($this->request->getMethod() == 'post'){

            if($this->validate([
                'default_server' => 'permit_empty|max_length[120]'
            ])){

                $submittedServers = $this->request->getPost('renamed_servers');
                $submittedPriorities = $this->request->getPost('host_priorities');
                $servers = [];

                if (is_array($submittedServers)) {
                    foreach ($submittedServers as $host => $label) {
                        $host = trim((string) $host);
                        if ($host === '') {
                            continue;
                        }

                        // A server record is only a display-name mapping. Empty values
                        // deliberately keep the original host visible to visitors.
                        $servers[$host] = mb_substr(trim(strip_tags((string) $label)), 0, 80);
                    }
                }

                $hostPriorities = [];
                if (is_array($submittedPriorities)) {
                    foreach ($submittedPriorities as $host => $priority) {
                        $host = trim((string) $host);
                        if ($host === '') {
                            continue;
                        }

                        $priority = filter_var(trim((string) $priority), FILTER_VALIDATE_INT);
                        if ($priority === false || $priority < 0 || $priority > 65535) {
                            return redirect()->back()
                                ->with('errors', 'Host priority must be a whole number from 0 to 65535.')
                                ->withInput();
                        }

                        $hostPriorities[$host] = $priority;
                    }
                }

                if ($hostPriorities !== [] && ! (new LinkModel())->supportsStreamHealthFields()) {
                    return redirect()->back()
                        ->with('errors', 'Run php spark migrate before applying host priorities.')
                        ->withInput();
                }

                $default_server = trim((string) $this->request->getPost('default_server'));
                $currentServers = (array) get_config('renamed_servers');

                // Keep the default selection attached to the same host when its
                // display name is edited or removed in this submission.
                foreach ($currentServers as $host => $label) {
                    $currentName = $label !== '' ? $label : $host;
                    if ($default_server === $currentName && array_key_exists($host, $servers)) {
                        $default_server = $servers[$host] !== '' ? $servers[$host] : $host;
                        break;
                    }
                }

                $serverNames = array_map(static function ($label, $host) {
                    return $label !== '' ? $label : $host;
                }, $servers, array_keys($servers));

                if ($default_server !== '' && ! in_array($default_server, $serverNames, true)) {
                    $default_server = '';
                }

                $servers = json_encode($servers);

                $data = [
                    'renamed_servers' => $servers,
                    'default_server' => $default_server
                ];

                if ($hostPriorities !== []) {
                    $this->applyHostPriorities($hostPriorities);
                }

                return $this->save( $data );

            }

        }


        return redirect()->back();

    }

    /** Apply one priority to every stream link belonging to a configured host. */
    private function applyHostPriorities(array $hostPriorities): void
    {
        $linksModel = new LinkModel();
        $links = $linksModel->select('id, link')
            ->where('type', 'stream')
            ->findAll();
        $updates = [];

        foreach ($links as $link) {
            $host = $link->getHost();
            if (array_key_exists($host, $hostPriorities)) {
                $updates[] = [
                    'id' => (int) $link->id,
                    'host_priority' => $hostPriorities[$host],
                ];
            }
        }

        if ($updates !== []) {
            db_connect()->table('links')->updateBatch($updates, 'id');
        }
    }

}
