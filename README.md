# 🖼️ ImgHost — External Image Hoster for phpBB 3.3

A lightweight phpBB 3.3 extension (`kuskow/imghost`) that compresses images directly in the user's browser and uploads them straight to an external image host (or a separate directory on the same server) without loading the main forum server.

---

## Features

- **Client-Side Compression**: Automatically resizes images (default max `1024×1024` px) and converts them to JPEG via HTML5 `<canvas>`, stripping all EXIF metadata before upload.
- **Zero Traffic Load on Forum**: Browser uploads compressed files directly to the external receiver (`imghost_receiver.php`) in a single request.
- **Clipboard Paste Support**: Upload images instantly by pasting them (`Ctrl+V`) into the reply box, or via the toolbar upload button.
- **Post & Daily Quotas**: Configurable limit of images per post (default `5` — automatically appends a friendly notice in the message text when reached) and daily upload limit per user (default `500`).
- **One-Click TOFU Pairing & HMAC Security**: Automatically generates a 256-bit secret key upon installation, pairs once with `imghost_receiver.php` (`imghost_config.php`), and signs every page ticket with `HMAC-SHA256`.
- **Strict Server Firewall**: Receiver validates file size, pixel dimensions, true JPEG format, absence of EXIF headers (`0xFFE1`), and user daily quota, silently rejecting invalid requests (`HTTP 403`).

---

## Installation

### 1. Install the phpBB Extension
1. Download `kuskow_imghost_1.0.0.zip`.
2. Extract the `kuskow` folder into your forum's `ext/` directory (`ext/kuskow/imghost/composer.json`).
3. Go to **ACP → Customise → Manage extensions**, find **🖼️ ImgHost**, and click **Enable**.

### 2. Deploy the Image Receiver
1. Copy `ext/kuskow/imghost/receiver/imghost_receiver.php` to your external image hosting server (or into a subfolder on your main domain, e.g. `/img/imghost_receiver.php`).
2. Make sure the directory is writable by PHP so `imghost_receiver.php` can create `imghost_config.php` and monthly subdirectories (`YYYYMM/`).

### 3. Pair Forum and Receiver
1. Go to **ACP → Extensions → ImgHost → Settings**.
2. Verify or enter the full URL to `imghost_receiver.php` and adjust limits if needed.
3. Click **Submit**. The extension will automatically pair with the receiver and create `imghost_config.php`.
   - *Note:* Once paired, settings are locked in `imghost_config.php` against overwrites. To change settings later, delete `imghost_config.php` on the image host and refresh the ACP settings page.

---

## Release Description (for GitHub Releases)

### ImgHost 1.0.0 — Initial Release

A lightweight phpBB 3.3 extension (`kuskow/imghost`) for client-side image compression and direct uploading to an external image host without overloading the forum server.

#### What's Included
- Client-side resizing, JPEG conversion, and EXIF removal via `<canvas>`.
- Toolbar upload button and clipboard paste (`Ctrl+V`) support in `viewtopic`.
- Per-post image limit (with automatic in-post notice) and per-user daily upload quota.
- Automatic TOFU pairing (`imghost_config.php`) and `HMAC-SHA256` ticket verification.
- Standalone `imghost_receiver.php` included in `kuskow/imghost/receiver/`.

#### Quick Install
1. Download `kuskow_imghost_1.0.0.zip`.
2. Upload the `kuskow` folder to `ext/` (`ext/kuskow/imghost/composer.json`).
3. Enable **🖼️ ImgHost** in **ACP → Customise → Manage extensions**.
4. Upload `ext/kuskow/imghost/receiver/imghost_receiver.php` to your image host and click **Submit** in **ACP → Extensions → ImgHost**.

---
---

# 🖼️ ImgHost — Внешний фотохостинг для phpBB 3.3

Лёгкое расширение для phpBB 3.3 (`kuskow/imghost`), которое сжимает изображения прямо в браузере пользователя и загружает их напрямую на внешний фотохостинг (или в отдельную папку основного сервера) без нагрузки на трафик и процессор форума.

---

## Возможности

- **Сжатие на стороне клиента**: автоматическое уменьшение габаритов (по умолчанию до `1024×1024` px) и конвертация в JPEG через HTML5 `<canvas>` с удалением всех EXIF-метаданных до отправки.
- **Нулевая нагрузка на трафик форума**: браузер отправляет сжатые файлы напрямую на приёмник (`imghost_receiver.php`) одним запросом.
- **Вставка из буфера обмена**: загрузка картинок по `Ctrl+V` прямо в поле ответа или через кнопку на панели BB-кодов.
- **Лимиты на сообщение и сутки**: настраиваемый лимит фото в одном сообщении (по умолчанию `5` — при достижении автоматически добавляет в текст поста уведомление о продолжении в следующем сообщении) и суточная квота на пользователя (по умолчанию `500` фото).
- **Автопривязка в один клик и защита HMAC**: автоматическая генерация 256-битного секретного ключа при установке, однократная привязка к приёмнику (`imghost_config.php`) и криптографическая подпись билетов `HMAC-SHA256`.
- **Жёсткий файрвол приёмника**: проверка веса файла, габаритов в пикселях, реального формата JPEG, отсутствия EXIF-заголовков (`0xFFE1`) и суточного лимита пользователя с молчаливым сбросом (`HTTP 403`) при любых нарушениях.

---

## Установка

### 1. Установка расширения в phpBB
1. Скачайте архив `kuskow_imghost_1.0.0.zip`.
2. Загрузите папку `kuskow` в директорию `ext/` вашего форума (`ext/kuskow/imghost/composer.json`).
3. Перейдите в **АСР → Персонализация → Управление расширениями**, найдите **🖼️ ImgHost** и нажмите **Включить**.

### 2. Размещение приёмника изображений
1. Скопируйте файл `ext/kuskow/imghost/receiver/imghost_receiver.php` на внешний фотохостинг (или в отдельную папку на основном домене, например `/img/imghost_receiver.php`).
2. Убедитесь, что папка доступна PHP на запись для создания `imghost_config.php` и ежемесячных подпапок (`ГГГГММ/`).

### 3. Привязка форума и приёмника
1. Перейдите в **АСР → Расширения → ImgHost → Настройки**.
2. Проверьте или укажите полный URL к `imghost_receiver.php` и при необходимости скорректируйте лимиты.
3. Нажмите **Отправить**. Расширение автоматически выполнит привязку и создаст файл `imghost_config.php` на сервере хостинга.
   - *Примечание:* После привязки настройки в `imghost_config.php` защищены от перезаписи. Чтобы изменить параметры позже, удалите файл `imghost_config.php` на фотохостинге и обновите страницу настроек в АСР.

---

## Описание для страницы релиза на GitHub (Releases)

### ImgHost 1.0.0 — Первый релиз

Лёгкое расширение для phpBB 3.3 (`kuskow/imghost`) для сжатия изображений на стороне клиента и прямой загрузки на внешний фотохостинг без нагрузки на сервер форума.

#### Что входит в релиз
- Сжатие, конвертация в JPEG и очистка EXIF через `<canvas>` в браузере.
- Кнопка загрузки на панели инструментов и вставка из буфера обмена (`Ctrl+V`) при просмотре тем (`viewtopic`).
- Лимит фото на одно сообщение (с автоматической вставкой подсказки в текст) и суточный лимит на пользователя.
- Автоматическая привязка (`imghost_config.php`) и проверка билетов по `HMAC-SHA256`.
- Скрипт приёмника `imghost_receiver.php` в комплекте (`kuskow/imghost/receiver/`).

#### Быстрая установка
1. Скачайте архив `kuskow_imghost_1.0.0.zip`.
2. Загрузите папку `kuskow` в директорию `ext/` (`ext/kuskow/imghost/composer.json`).
3. Включите **🖼️ ImgHost** в **АСР → Персонализация → Управление расширениями**.
4. Загрузите `ext/kuskow/imghost/receiver/imghost_receiver.php` на фотохостинг и нажмите **Отправить** в **АСР → Расширения → ImgHost**.
