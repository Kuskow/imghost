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

class main_info
{
    public function module()
    {
        return [
            'filename' => '\kuskow\imghost\acp\main_module',
            'title'    => 'ACP_IMGHOST_TITLE',
            'modes'    => [
                'settings' => [
                    'title' => 'ACP_IMGHOST_SETTINGS',
                    'auth'  => 'ext_kuskow/imghost && acl_a_board',
                    'cat'   => ['ACP_IMGHOST_TITLE'],
                ],
            ],
        ];
    }
}
