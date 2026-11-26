<?php
/**
 *
 * ImgHost extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Kuskow
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace kuskow\imghost\migrations;

class imghost_install extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['kuskow_imghost_secret']) && $this->config['kuskow_imghost_secret'] !== '';
    }

    public static function depends_on()
    {
        return ['\phpbb\db\migration\data\v330\v330'];
    }

    public function update_data()
    {
        $secret = bin2hex(random_bytes(32));

        return [
            ['config.add', ['kuskow_imghost_secret', $secret]],
            ['config.add', ['kuskow_imghost_url', '']],
            ['config.add', ['kuskow_imghost_paired', 0]],
            ['config.add', ['kuskow_imghost_max_daily', 500]],
            ['config.add', ['kuskow_imghost_max_dim', 1024]],
            ['config.add', ['kuskow_imghost_max_kb', 1536]],

            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_IMGHOST_TITLE'
            ]],
            ['module.add', [
                'acp',
                'ACP_IMGHOST_TITLE',
                [
                    'module_basename' => '\kuskow\imghost\acp\main_module',
                    'modes'           => ['settings'],
                ],
            ]],
        ];
    }
}
