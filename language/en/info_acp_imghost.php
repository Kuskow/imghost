<?php
/**
 *
 * ImgHost extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Kuskow
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = [];
}

$lang = array_merge($lang, [
    'ACP_IMGHOST_TITLE'             => 'ImgHost',
    'ACP_IMGHOST_SETTINGS'          => 'Image Hoster Settings',
    'ACP_IMGHOST_EXPLAIN'           => 'Configure direct client-side image compression and upload to your external image hoster. Settings are locked once paired with the receiver.',

    'ACP_IMGHOST_LOCKED_TITLE'      => 'Receiver Paired & Settings Locked',
	'ACP_IMGHOST_LOCKED_EXPLAIN'	=> 'Settings are saved in <code>imghost_config.php</code> on the image host server and are write-protected. To change them, delete <code>imghost_config.php</code> on the image host and refresh this page.',

    'ACP_IMGHOST_URL'               => 'Receiver URL',
    'ACP_IMGHOST_URL_EXPLAIN'       => 'Full HTTPS URL to <code>imghost_receiver.php</code> on your external image hoster.',
    'ACP_IMGHOST_MAX_POST'          => 'Max images per message',
    'ACP_IMGHOST_MAX_POST_EXPLAIN'  => 'Maximum number of photos a user can insert into a single message (default: 5).',
    'ACP_IMGHOST_MAX_DAILY'         => 'Max images per user / day',
    'ACP_IMGHOST_MAX_DAILY_EXPLAIN' => 'Daily upload quota per registered user enforced by the receiver (default: 500).',
    'ACP_IMGHOST_MAX_DIM'           => 'Max image dimension (px)',
    'ACP_IMGHOST_MAX_DIM_EXPLAIN'   => 'Maximum width and height in pixels after client-side resize (default: 1024).',
    'ACP_IMGHOST_MAX_KB'            => 'Max file size (KB)',
    'ACP_IMGHOST_MAX_KB_EXPLAIN'    => 'Maximum compressed JPEG file size in kilobytes accepted by the receiver (default: 1536).',

    'ACP_IMGHOST_SAVED'             => 'ImgHost has been paired with the receiver and settings are now locked.',
    'ACP_IMGHOST_UNLOCKED'          => 'Config file on the receiver is absent. Settings are now unlocked for editing.',
    'ACP_IMGHOST_STILL_LOCKED'      => 'The file <code>imghost_config.php</code> still exists on the receiver. Settings remain locked.',
    'ACP_IMGHOST_ERR_LOCKED'        => 'Settings are locked because the receiver already has a configuration file.',
    'ACP_IMGHOST_ERR_URL'           => 'Please enter a valid URL to imghost_receiver.php.',
    'ACP_IMGHOST_PAIR_FAILED'       => 'Failed to pair with receiver: %s',
    'ACP_IMGHOST_CONN_ERROR'        => 'Connection error: %s',
]);
