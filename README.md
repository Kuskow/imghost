# ImgHost — Direct Image Hosting Integration for phpBB 3.3

[English](#english) | [Русский](#русский)

---

## English

**ImgHost** is a lightweight, zero-overhead phpBB 3.3 extension that lets registered users upload images directly from their browser to a separate external image host (such as an inexpensive shared hosting account with ample disk space), automatically resizing photos on the client side and inserting `[img]...[/img]` BBCode tags into the post editor.

### Key Features
* **Zero Load on the Main Forum Server:** Images are compressed in the user's browser via HTML5 `<canvas>` and uploaded directly to the external receiver script without passing through the phpBB server.
* **Automatic Client-Side Resizing & EXIF Stripping:** Photos are scaled down to the configured maximum dimension (e.g., `1024 px`), converted to JPEG, and stripped of all EXIF metadata before upload.
* **Clipboard Paste Support (`Ctrl+V`):** Users can paste screenshots or copied images directly into the message textarea for instant upload.
* **Universal Editor Detection:** Automatically adds the upload button next to the standard `[img]` BBCode button wherever BBCode images are enabled (`S_BBCODE_IMG`), including quick reply, full post editor, and private messages / Messenger extensions.
* **One-Click TOFU Pairing & Lock:** Enter the receiver URL and limits in the phpBB ACP and click **Submit**. The extension pairs with the remote host over HTTPS, writes `imghost_config.php` on the image host, and locks the ACP fields into a read-only status screen to prevent accidental overwrites.
* **Multi-Layer Security:**
  * Time-limited HMAC-SHA256 upload tickets generated per user session (`user_id` + expiration timestamp + signature).
  * Per-user daily upload quota enforced on the receiver side.
  * Strict server-side validation of file size, pixel dimensions, valid JPEG format, and absence of EXIF markers (`0xFFE1`), silently rejecting invalid requests with `HTTP 403`.

### Requirements
* phpBB `3.3.0` or higher
* PHP `7.4` or higher (with `curl` and `json` extensions enabled)
* A separate hosting account or subdomain (e.g., `https://img.example.com/`) with PHP `7.4+` and `GD` extension enabled

### Installation

#### 1. Install the phpBB Extension
1. Download the release archive `kuskow_imghost_1.0.0.zip` and extract the `kuskow/` folder into your forum's `ext/` directory so the path is `ext/kuskow/imghost/`.
2. Go to **ACP → Customise → Manage extensions**.
3. Find **🖼️ ImgHost** in the disabled extensions list and click **Enable**.

#### 2. Deploy the Receiver Script on the Image Host
1. Copy `ext/kuskow/imghost/receiver/imghost_receiver.php` to the root directory (or a subdirectory) of your external image host (for example, `https://img.example.com/imghost_receiver.php`).
2. Ensure PHP has write permissions in that directory so it can create `imghost_config.php` and monthly image folders (`YYYYMM/`).

#### 3. Pair Forum & Image Host
1. In phpBB, open **ACP → Extensions → ImgHost → Settings**.
2. Verify or enter the **Receiver URL** (pre-filled automatically as `https://img.<your-domain>/imghost_receiver.php`), **Daily upload limit per user**, **Maximum image dimension (px)**, and **Maximum file size (KB)**.
3. Click **Submit**. Once paired, `imghost_config.php` is created on the image host and the ACP settings screen switches to locked informational mode.
4. *(Optional)* To change settings later, delete `imghost_config.php` from the image host via FTP/File Manager and refresh the ImgHost ACP page.

### License
[GNU General Public License v2.0](https://opensource.org/licenses/GPL-2.0)

---

## Русский

**ImgHost** — лёгкое расширение для phpBB 3.3, которое позволяет зарегистрированным пользователям загружать фотографии напрямую из браузера на отдельный внешний фотохостинг (например, на недорогой виртуальный хостинг с большим диском), автоматически сжимая изображения на стороне клиента и вставляя BB-коды `[img]...[/img]` в текст сообщения.

### Основные возможности
* **Нулевая нагрузка на основной сервер форума:** фотографии сжимаются в браузере пользователя через HTML5 `<canvas>` и отправляются напрямую на внешний скрипт-приёмник, минуя сервер phpBB.
* **Сжатие и очистка EXIF на стороне клиента:** изображения автоматически уменьшаются до заданного максимального размера (например, `1024 px`), перекодируются в JPEG и очищаются от всех EXIF-метаданных ещё до отправки.
* **Вставка из буфера обмена (`Ctrl+V`):** скриншоты и скопированные изображения можно вставлять прямо в поле ввода текста.
* **Универсальная работа во всех редакторах:** кнопка загрузки автоматически появляется рядом со штатной кнопкой `[img]` везде, где разрешена вставка изображений (`S_BBCODE_IMG`) — в быстром ответе, полном редакторе сообщений и личных сообщениях (включая расширение `Messenger`).
* **Безопасная однократная привязка (TOFU):** достаточно указать URL приёмника и лимиты в АСР phpBB и нажать **«Отправить»**. Расширение свяжется с приёмником, создаст на фотохостинге файл `imghost_config.php` и переведёт экран настроек в информационный режим с заблокированными полями.
* **Многоуровневая защита:**
  * Подписанные одноразовые билеты HMAC-SHA256 с привязкой к `user_id` и сроком действия.
  * Суточная квота загрузок на каждого пользователя на стороне приёмника.
  * Жёсткая проверка веса файла, габаритов в пикселях, формата JPEG и отсутствия EXIF-маркеров (`0xFFE1`) с тихим сбросом невалидных запросов (`HTTP 403`).

### Требования
* phpBB `3.3.0` или выше
* PHP `7.4` или выше (с поддержкой `curl` и `json`)
* Отдельный виртуальный хостинг или поддомен (например, `https://img.example.com/`) с PHP `7.4+` и библиотекой `GD`

### Установка

#### 1. Установка расширения на форум phpBB
1. Скачайте релизный архив `kuskow_imghost_1.0.0.zip` и распакуйте папку `kuskow/` в директорию `ext/` вашего форума (должен получиться путь `ext/kuskow/imghost/`).
2. Перейдите в **АСР → Персонализация → Управление расширениями**.
3. Найдите **🖼️ ImgHost** в списке отключённых расширений и нажмите **Включить**.

#### 2. Размещение скрипта-приёмника на фотохостинге
1. Скопируйте файл `ext/kuskow/imghost/receiver/imghost_receiver.php` в корневую папку (или подпапку) вашего внешнего фотохостинга (например, `https://img.example.com/imghost_receiver.php`).
2. Убедитесь, что у PHP есть права на запись в эту директорию для создания файла `imghost_config.php` и папок по месяцам (`YYYYMM/`).

#### 3. Привязка форума и фотохостинга
1. В админке форума откройте **АСР → Расширения → ImgHost → Настройки**.
2. Проверьте или укажите **URL приёмника** (подставляется автоматически в виде `https://img.<ваш-домен>/imghost_receiver.php`), **Суточный лимит на пользователя**, **Максимальный размер стороны (px)** и **Максимальный размер файла (КБ)**.
3. Нажмите **Отправить**. На фотохостинге создастся файл `imghost_config.php`, а экран настроек в АСР перейдёт в защищённый информационный режим.
4. *(При необходимости)* Чтобы изменить параметры позже, удалите файл `imghost_config.php` на сервере фотохостинга и обновите страницу настроек ImgHost в АСР.

---

### GitHub Release Description (`ImgHost 1.0.0`)

```markdown
## ImgHost 1.0.0 — Initial Release

**ImgHost** is a phpBB 3.3 extension for direct client-side image resizing and uploading to a separate external image host without loading the main forum server.

### Highlights
- Client-side `<canvas>` resizing, JPEG conversion, and automatic EXIF removal.
- Direct browser-to-image-host upload with `Ctrl+V` clipboard paste support.
- Works anywhere BBCode images are enabled (`S_BBCODE_IMG`), including quick reply, full post editor, and PMs.
- One-click TOFU pairing with read-only ACP lock (`imghost_config.php`).
- HMAC-SHA256 signed upload tickets, per-user daily quotas, and strict server-side image validation.

### Installation
Extract the `kuskow/` directory into your forum's `ext/` folder, enable **🖼️ ImgHost** in the ACP, upload `ext/kuskow/imghost/receiver/imghost_receiver.php` to your image host, and pair them in **ACP → Extensions → ImgHost → Settings**.

---

## ImgHost 1.0.0 — Первый релиз

**ImgHost** — расширение для phpBB 3.3 для сжатия фотографий в браузере и их прямой загрузки на отдельный внешний фотохостинг без нагрузки на сервер форума.

### Что реализовано
- Сжатие изображений на стороне клиента через `<canvas>`, конвертация в JPEG и автоматическое удаление EXIF-метаданных.
- Прямая загрузка на внешний фотохостинг, включая вставку картинок из буфера обмена (`Ctrl+V`).
- Универсальная работа везде, где разрешены BB-коды изображений (`S_BBCODE_IMG`): в быстром ответе, полном редакторе и личных сообщениях (включая `Messenger`).
- Однократная привязка к приёмнику в один клик с защитой от перезаписи (`imghost_config.php`) и информационным экраном в АСР.
- Подпись запросов через HMAC-SHA256, суточные квоты на каждого пользователя и строгая валидация изображений на стороне приёмника.

### Установка
Распакуйте папку `kuskow/` в директорию `ext/` форума, включите **🖼️ ImgHost** в АСР, загрузите `ext/kuskow/imghost/receiver/imghost_receiver.php` на фотохостинг и выполните привязку в **АСР → Расширения → ImgHost → Настройки**.
```
