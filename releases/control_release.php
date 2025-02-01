<?php

if (!class_exists('Dotypos_GitHub_Updater')) {
    class Dotypos_GitHub_Updater {
        private $plugin_slug;
        private $github_repo;
        private $github_api_url;
        private $access_token;

        public function __construct($plugin_slug, $github_repo, $access_token) {
            $this->plugin_slug = plugin_basename($plugin_slug);
            $this->github_repo = $github_repo;
            $this->github_api_url = "https://api.github.com/repos/$github_repo/releases/latest";
            $this->access_token = $access_token;

            // Hook na kontrolu aktualizací
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);

            // Hook pro zobrazení informací o pluginu
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        }

        public function check_for_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }

            $latest_release = $this->get_latest_release();

            if (!$latest_release) {
                return $transient;
            }

            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_slug);
            $current_version = $plugin_data['Version'];
            $latest_version = $latest_release['tag_name'];

            if (version_compare($current_version, $latest_version, '<')) {
                $transient->response[$this->plugin_slug] = (object) [
                    'slug'        => $this->plugin_slug,
                    'new_version' => $latest_version,
                    'url'         => $latest_release['html_url'],
                    'package'     => $latest_release['zipball_url'] . '?access_token=' . $this->access_token,
                ];
            }

            return $transient;
        }

        public function plugin_info($res, $action, $args) {
            if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
                return $res;
            }

            $latest_release = $this->get_latest_release();
            if (!$latest_release) {
                return $res;
            }

            $res = (object) [
                'name'          => 'Dotypos Sync Plugin',
                'slug'          => $this->plugin_slug,
                'version'       => $latest_release['tag_name'],
                'author'        => '<a href="https://liskajiri.cz/">Jiří Liška</a>',
                'homepage'      => $latest_release['html_url'],
                'download_link' => $latest_release['zipball_url'] . '?access_token=' . $this->access_token,
                'sections'      => [
                    'description' => $latest_release['body'],
                ],
            ];

            return $res;
        }

        private function get_latest_release() {
            $args = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'User-Agent'    => 'WordPress GitHub Updater',
                ],
            ];

            $response = wp_remote_get($this->github_api_url, $args);

            if (is_wp_error($response)) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            $release = json_decode($body, true);

            return isset($release['tag_name']) ? $release : false;
        }
    }

    new Dotypos_GitHub_Updater(
        plugin_basename(__FILE__), // Slug pluginu
        'Jiricek95/dotypos-sync', // Repo GitHub
        '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' // GitHub Token
    );
}
