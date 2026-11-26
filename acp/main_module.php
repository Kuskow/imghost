<?php
/**
 *
 * ImgHost extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Kuskow
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace kuskow\imghost\acp;

class main_module
{
    public $page_title;
    public $tpl_name;
    public $u_action;

    public function main($id, $mode)
    {
        global $phpbb_container;

        $language = $phpbb_container->get('language');
        $request  = $phpbb_container->get('request');
        $config   = $phpbb_container->get('config');
        $template = $phpbb_container->get('template');

        $language->add_lang('info_acp_imghost', 'kuskow/imghost');

        $this->tpl_name = 'imghost_acp';
        $this->page_title = $language->lang('ACP_IMGHOST_TITLE');

        add_form_key('kuskow_imghost_acp');

        $errors = [];
        $is_paired = (int) $config['kuskow_imghost_paired'] === 1;

        if ($request->is_set_post('submit')) {
            if (!check_form_key('kuskow_imghost_acp')) {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }

            if ($is_paired) {
                $errors[] = $language->lang('ACP_IMGHOST_ERR_LOCKED');
            } else {
                $url       = trim($request->variable('kuskow_imghost_url', ''));
                $max_daily = max(1, min(10000, $request->variable('kuskow_imghost_max_daily', 500)));
                $max_dim   = max(256, min(4096, $request->variable('kuskow_imghost_max_dim', 1024)));
                $max_kb    = max(100, min(10240, $request->variable('kuskow_imghost_max_kb', 1536)));

                if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[] = $language->lang('ACP_IMGHOST_ERR_URL');
                }

                if (empty($errors)) {
                    $forum_url = generate_board_url();
                    $secret = (string) $config['kuskow_imghost_secret'];

                    $pair_res = $this->query_receiver($url, [
                        'action'    => 'pair',
                        'secret'    => $secret,
                        'forum_url' => $forum_url,
                        'max_daily' => $max_daily,
                        'max_dim'   => $max_dim,
                        'max_kb'    => $max_kb,
                    ]);

                    if ($pair_res['ok'] && !empty($pair_res['data']['paired'])) {
                        $config->set('kuskow_imghost_url', $url);
                        $config->set('kuskow_imghost_max_daily', $max_daily);
                        $config->set('kuskow_imghost_max_dim', $max_dim);
                        $config->set('kuskow_imghost_max_kb', $max_kb);
                        $config->set('kuskow_imghost_paired', 1);

                        trigger_error($language->lang('ACP_IMGHOST_SAVED') . adm_back_link($this->u_action));
                    } else {
                        $reason = $pair_res['data']['error'] ?? $pair_res['error'];
                        $errors[] = $language->lang('ACP_IMGHOST_PAIR_FAILED', $reason);
                    }
                }
            }
        }

        $default_url = trim((string) $config['kuskow_imghost_url']);
        if ($default_url === '' && !empty($config['server_name'])) {
            $host = preg_replace('/^www\./i', '', trim((string) $config['server_name']));
            $default_url = 'https://img.' . $host . '/imghost_receiver.php';
        }

        $template->assign_vars([
            'S_ERROR'                  => !empty($errors),
            'ERROR_MSG'                => implode('<br />', $errors),
            'S_IMGHOST_PAIRED'         => $is_paired,
            'KUSKOW_IMGHOST_URL'       => $default_url,
            'KUSKOW_IMGHOST_MAX_DAILY' => (int) $config['kuskow_imghost_max_daily'],
            'KUSKOW_IMGHOST_MAX_DIM'   => (int) $config['kuskow_imghost_max_dim'],
            'KUSKOW_IMGHOST_MAX_KB'    => (int) $config['kuskow_imghost_max_kb'],
            'U_ACTION'                 => $this->u_action,
        ]);
    }

    protected function query_receiver($url, array $post_fields)
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'cURL extension is not enabled in PHP'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        if (defined('CURL_HTTP_VERSION_2_0')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        }

        $response = curl_exec($ch);
        $curl_err = curl_error($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'error' => $curl_err ?: 'Connection failed'];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'Invalid JSON response (HTTP ' . $http_code . ')'];
        }

        return [
            'ok'   => ($http_code === 200),
            'code' => $http_code,
            'data' => $decoded,
            'error'=> $decoded['error'] ?? ('HTTP ' . $http_code),
        ];
    }
}
