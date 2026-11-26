# ImgHost for phpBB 3.3

A lightweight phpBB 3.3 extension (`kuskow/imghost`) that compresses images directly in the user's browser (via HTML5 Canvas, stripping EXIF metadata) and uploads them straight to an external image hoster using HMAC-signed tickets — without routing heavy image traffic through the forum server.

---

## Features

- **Zero image traffic on forum server:** Images are resized and compressed in the browser and sent directly to the external receiver.
- **Trust-On-First-Use (TOFU) Auto-Pairing:** Generates a 256-bit secret key upon extension installation and automatically writes configuration to `.imghost_config.php` on the receiver when saved in ACP.
- **Tamper-Proof Lock:** Once `imghost_config.php` is created on the external server, settings cannot be overwritten remotely until the file is manually deleted from the receiver.
- **Strict Server Firewall:** The receiver silently rejects (`HTTP 403`, empty body) any batch that exceeds pixel dimensions, file size, is not a valid JPEG, or still contains EXIF metadata (`0xFFE1`).
- **Per-Post & Daily Quotas:** Limits images per message (default: `5`) with an automatic in-post notice (*"More photos can be inserted in the next message"*), plus a daily per-user quota (default: `500`).
- **Clipboard Paste Support:** Paste screenshots or copied images directly into the message editor (`Ctrl+V`).

---

## Installation

### Step 1: External Image Hoster Setup
1. Upload `receiver/imghost_receiver.php` and `receiver/.htaccess` to your image hoster directory (e.g., `https://img.example.com/`).
2. Ensure PHP has write permissions to that directory so it can create `imghost_config.php` and monthly `YYYYMM/` folders.
3. Edit `receiver/.htaccess` and replace `example\.com` with your actual forum domain to block third-party hotlinking.

### Step 2: phpBB Extension Setup
1. Upload the `kuskow` folder from the archive into `ext/` (`ext/kuskow/imghost/composer.json`).
2. Go to **ACP → Customise → Manage extensions** and enable **ImgHost**.
3. Go to **ACP → Extensions → ImgHost → Image Hoster Settings**.
4. Enter the full URL to `imghost_receiver.php`, adjust limits if needed, and click **Submit**.
5. The forum will automatically pair with the receiver and lock the configuration.

---

## Русская инструкция (Установка)

### Шаг 1: Настройка стороннего фотохостинга
1. Загрузите файлы `receiver/imghost_receiver.php` и `receiver/.htaccess` на сервер фотохостинга (например, `https://img.example.com/`).
2. Убедитесь, что у PHP есть права на запись в эту директорию (для создания `imghost_config.php` и папок вида `ГГГГММ`).
3. Откройте `receiver/.htaccess` и замените `example\.com` на домен вашего форума для защиты от вставки картинок на чужие сайты.

### Шаг 2: Установка расширения в phpBB 3.3
1. Скопируйте папку `kuskow` из архива в директорию `ext/` вашего форума (`ext/kuskow/imghost/composer.json`).
2. В Администраторском разделе перейдите в **Персонализация → Управление расширениями** и включите **ImgHost**.
3. Перейдите в **Расширения → ImgHost → Настройки фотохостинга**.
4. Укажите полный HTTPS-адрес файла `imghost_receiver.php`, задайте лимиты и нажмите **Отправить**.
5. Форум автоматически привяжется к приёмнику, запишет `imghost_config.php` и зафиксирует настройки.
