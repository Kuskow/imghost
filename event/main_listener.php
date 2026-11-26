<?php
/**
 *
 * ImgHost extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Kuskow
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace kuskow\imghost\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
    protected $config;
    protected $template;
    protected $user;
    protected $language;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\language\language $language
    ) {
        $this->config = $config;
        $this->template = $template;
        $this->user = $user;
        $this->language = $language;
    }

    public static function getSubscribedEvents()
    {
        return [
            'core.user_setup'        => 'load_language_on_setup',
            'core.page_footer_after' => 'assign_template_vars',
        ];
    }

    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'kuskow/imghost',
            'lang_set' => 'imghost',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    public function assign_template_vars()
    {
        $receiver_url = trim((string) $this->config['kuskow_imghost_url']);
        $secret = (string) $this->config['kuskow_imghost_secret'];
        $is_paired = (int) $this->config['kuskow_imghost_paired'];

        // Active only for registered, non-bot users when paired and configured
        if (
            !$is_paired ||
            $receiver_url === '' ||
            $secret === '' ||
            !$this->user->data['is_registered'] ||
            $this->user->data['is_bot'] ||
            (int) $this->user->data['user_id'] <= 1
        ) {
            return;
        }

        $user_id = (int) $this->user->data['user_id'];
        $timestamp = time();
        $payload = $user_id . ':' . $timestamp;
        $signature = hash_hmac('sha256', $payload, $secret);
        $ticket = $payload . ':' . $signature;

        $this->template->assign_vars([
            'S_IMGHOST_ENABLED'   => true,
            'IMGHOST_RECEIVER'    => $receiver_url,
            'IMGHOST_TICKET'      => $ticket,
            'IMGHOST_MAX_DIM'     => (int) $this->config['kuskow_imghost_max_dim'],
            'L_IMGHOST_BTN_TITLE' => $this->language->lang('IMGHOST_UPLOAD_BUTTON'),
        ]);
    }
}
