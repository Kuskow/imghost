# ImgHost — Direct Image Hosting Integration for phpBB 3.3

English | Русский

## English

**ImgHost** is a lightweight, zero-overhead phpBB 3.3 extension that lets registered users upload images directly from their browser to your own self-hosted external image server, automatically resizing photos on the client side and inserting `[img]...[/img]` BBCode tags into the post editor. For full documentation and features, please refer to the repository files.

### Requirements & Installation

- **Main forum:** phpBB 3.3.0+ and PHP 7.4+ (`curl`, `json`).
- **Self-hosted image server:** External hosting or VPS with PHP 7.4+ and the **`Imagick`** PHP extension enabled.
- **Setup:** Extract `kuskow/` to `ext/`, enable **🖼 ImgHost** in ACP, deploy `receiver/imghost_receiver.php` to your image host, and pair them in **ACP → Extensions → ImgHost → Settings**.

### License

GNU General Public License v2.0

---

## Русский

**ImgHost** — лёгкое расширение для phpBB 3.3, позволяющее пользователям загружать изображения напрямую из браузера на собственный внешний фотохостинг с клиентским сжатием и автоматической вставкой BB-кодов `[img]...[/img]`. Полное описание возможностей доступно в исходном коде репозитория.

### Требования и установка

- **Основной форум:** phpBB 3.3.0+ и PHP 7.4+ (`curl`, `json`).
- **Собственный фотохостинг:** Отдельный хостинг или VPS с PHP 7.4+ и включённым расширением **`Imagick`**.
- **Установка:** Распакуйте `kuskow/` в `ext/`, включите **🖼 ImgHost** в АСР, разместите `receiver/imghost_receiver.php` на сервере фотохостинга и выполните привязку в **АСР → Расширения → ImgHost → Настройки**.

### Лицензия

GNU General Public License v2.0
