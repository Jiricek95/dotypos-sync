<?php
namespace DotyposUpdater;

class Updater {

    private $plugin_slug;
    private $version;
    private $cache_key;
    private $cache_allowed;
    private $github_repo;
    private $github_api_url;
    private $access_token;

    public function __construct($plugin_slug, $github_repo, $access_token, $version) {
        $this->plugin_slug   = $plugin_slug;
        $this->github_repo   = $github_repo;
        $this->github_api_url = "https://api.github.com/repos/{$github_repo}/releases/latest";
        $this->access_token  = $access_token;
        $this->version       = $version;
        $this->cache_key     = "dotypos_updater";
        $this->cache_allowed = false;

        add_filter('plugins_api', [$this, 'info'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'update']);
        add_action('upgrader_process_complete', [$this, 'purge'], 10, 2);
    }

    private function request() {
        $remote = get_transient($this->cache_key);

        if (false === $remote || !$this->cache_allowed) {
            $response = wp_remote_get($this->github_api_url, [
                'timeout' => 10,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'User-Agent'    => 'WordPress GitHub Updater',
                    'Accept'        => 'application/vnd.github.v3+json',
                ]
            ]);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }

            $remote = json_decode(wp_remote_retrieve_body($response));

            set_transient($this->cache_key, $remote, DAY_IN_SECONDS);
        }

        return $remote;
    }

    public function info($response, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
            return $response;
        }

        $remote = $this->request();
        if (!$remote) {
            return $response;
        }

        $response = new \stdClass();
        $response->name          = $this->plugin_slug;
        $response->slug          = $this->plugin_slug;
        $response->version       = $remote->tag_name;
        $response->author        = '<a href="https://github.com/">GitHub</a>';
        $response->homepage      = $remote->html_url;
        $response->download_link = $remote->zipball_url . '?access_token=' . $this->access_token;
        $response->last_updated  = $remote->published_at;
        $response->sections = [
            'description'  => !empty($remote->body) ? nl2br($remote->body) : 'No description available.',
            'changelog'    => 'Check the GitHub release page for changes.',
        ];

        return $response;
    }

    public function update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->request();
        if ($remote && version_compare($this->version, $remote->tag_name, '<')) {
            $response = new \stdClass();
            $response->slug        = $this->plugin_slug;
            $response->plugin      = "{$this->plugin_slug}/{$this->plugin_slug}.php";
            $response->new_version = $remote->tag_name;
            $response->package     = $remote->zipball_url . '?access_token=' . $this->access_token;

            $transient->response[$response->plugin] = $response;
        }

        return $transient;
    }

    public function purge($upgrader, $options) {
        if ($this->cache_allowed && $options['action'] === 'update' && $options['type'] === 'plugin') {
            delete_transient($this->cache_key);
        }
    }
}
