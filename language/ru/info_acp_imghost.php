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
    'ACP_IMGHOST_SETTINGS'          => 'Настройки фотохостинга',
    'ACP_IMGHOST_EXPLAIN'           => 'Настройка сжатия изображений в браузере и прямой загрузки на сторонний фотохостинг. После привязки к приёмнику параметры блокируются.',

    'ACP_IMGHOST_LOCKED_TITLE'      => 'Приёмник привязан, настройки зафиксированы',
	'ACP_IMGHOST_LOCKED_EXPLAIN'	=> 'Параметры записаны в файл <code>imghost_config.php</code> на сервере фотохостинга и защищены от перезаписи. Чтобы изменить их, удалите файл <code>imghost_config.php</code> на фотохостинге и обновите эту страницу.',

    'ACP_IMGHOST_URL'               => 'URL приёмника',
    'ACP_IMGHOST_URL_EXPLAIN'       => 'Полный HTTPS-адрес файла <code>imghost_receiver.php</code> на стороннем сервере.',
    'ACP_IMGHOST_MAX_POST'          => 'Максимум фото в одном сообщении',
    'ACP_IMGHOST_MAX_POST_EXPLAIN'  => 'Сколько фотографий пользователь может вставить в одно сообщение (по умолчанию: 5).',
    'ACP_IMGHOST_MAX_DAILY'         => 'Лимит фото на пользователя в сутки',
    'ACP_IMGHOST_MAX_DAILY_EXPLAIN' => 'Суточная квота загрузок на одного пользователя, контролируемая приёмником (по умолчанию: 500).',
    'ACP_IMGHOST_MAX_DIM'           => 'Максимальный размер стороны (px)',
    'ACP_IMGHOST_MAX_DIM_EXPLAIN'   => 'Максимальная ширина и высота изображения в пикселях после сжатия (по умолчанию: 1024).',
    'ACP_IMGHOST_MAX_KB'            => 'Максимальный вес файла (КБ)',
    'ACP_IMGHOST_MAX_KB_EXPLAIN'    => 'Максимальный размер сжатого JPEG-файла в килобайтах (по умолчанию: 1536).',

    'ACP_IMGHOST_SAVED'             => 'Приёмник успешно привязан, параметры записаны и зафиксированы.',
    'ACP_IMGHOST_UNLOCKED'          => 'Файл конфигурации на фотохостинге отсутствует. Поля разблокированы для редактирования.',
    'ACP_IMGHOST_STILL_LOCKED'      => 'Файл <code>imghost_config.php</code> всё ещё существует на фотохостинге. Настройки остаются заблокированными.',
    'ACP_IMGHOST_ERR_LOCKED'        => 'Изменение запрещено: на фотохостинге уже существует файл конфигурации.',
    'ACP_IMGHOST_ERR_URL'           => 'Укажите корректный URL-адрес файла imghost_receiver.php.',
    'ACP_IMGHOST_PAIR_FAILED'       => 'Ошибка привязки к приёмнику: %s',
    'ACP_IMGHOST_CONN_ERROR'        => 'Ошибка соединения с приёмником: %s',
]);
