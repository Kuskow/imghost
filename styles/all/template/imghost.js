/**
 * ImgHost Client Script v1.0.0 by Kuskow
 * Compresses images via Canvas (strips EXIF) and uploads directly to external receiver.
 */
(function() {
    'use strict';

    const cfgEl = document.getElementById('kuskow-imghost-cfg');
    if (!cfgEl) return;

    const cfg = {
        receiverUrl: cfgEl.dataset.url,
        ticket: cfgEl.dataset.ticket,
        maxDim: parseInt(cfgEl.dataset.maxDim, 10) || 1024,
        btnTitle: cfgEl.dataset.btnTitle || ''
    };
    if (!cfg.receiverUrl || !cfg.ticket) return;
    
    let globalFileInput = null;
    const ORIGINAL_ICON = '<i class="icon fa-cloud-upload fa-fw" aria-hidden="true"></i>';
    const QUALITY = 0.83;

    function getOrCreateFileInput() {
        if (globalFileInput) return globalFileInput;
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.id = 'kuskow-imghost-input';
        fileInput.multiple = true;
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);
        globalFileInput = fileInput;
        return fileInput;
    }

    function insertAtCursor(textarea, textToInsert) {
        const startPos = textarea.selectionStart;
        const endPos = textarea.selectionEnd;
        const textBefore = textarea.value.substring(0, startPos);
        const textAfter = textarea.value.substring(endPos, textarea.value.length);

        textarea.value = textBefore + textToInsert + textAfter;
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = startPos + textToInsert.length;
    }

    function compressImageClientSide(file) {
        return new Promise((resolve, reject) => {
            const objectUrl = URL.createObjectURL(file);
            const img = new Image();

            img.onload = function() {
                URL.revokeObjectURL(objectUrl);

                let width = img.width;
                let height = img.height;

                const ratio = Math.min(cfg.maxDim / width, cfg.maxDim / height);
                if (ratio < 1) {
                    width = Math.round(width * ratio);
                    height = Math.round(height * ratio);
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');

                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, width, height);
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(function(blob) {
                    if (!blob) {
                        reject(new Error('Canvas toBlob failed'));
                        return;
                    }
                    const origName = file.name || 'pasted_image.png';
                    const lastDot = origName.lastIndexOf('.');
                    const baseName = lastDot !== -1 ? origName.substring(0, lastDot) : origName;
                    const newFileName = baseName + '.jpg';

                    resolve(new File([blob], newFileName, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    }));
                }, 'image/jpeg', QUALITY);
            };

            img.onerror = function() {
                URL.revokeObjectURL(objectUrl);
                reject(new Error('Image load failed'));
            };

            img.src = objectUrl;
        });
    }

    function uploadAndInsertImages(files, textarea) {
        if (!files || files.length === 0) return;
    
        const compressionPromises = Array.from(files).map(file => compressImageClientSide(file));
    
        Promise.all(compressionPromises)
            .then(compressedFiles => {
                const formData = new FormData();
                formData.append('ticket', cfg.ticket);
                compressedFiles.forEach(file => {
                    formData.append('images[]', file);
                });
    
                return fetch(cfg.receiverUrl, {
                    method: 'POST',
                    body: formData
                });
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                if (data.links && data.links.length > 0) {
                    const startPos = textarea.selectionStart;
                    const hasTextBefore = textarea.value.substring(0, startPos).trim().length > 0;
    
                    let bbcodeInsert = hasTextBefore ? '\r\n\r\n' : '';
                    data.links.forEach((link, index) => {
                        bbcodeInsert += `[img]${link}[/img]`;
                        if (index < data.links.length - 1) {
                            bbcodeInsert += '\r\n\r\n';
                        }
                    });
                    bbcodeInsert += '\r\n\r\n';
    
                    insertAtCursor(textarea, bbcodeInsert);
                }
            })
            .catch(error => {
                console.error('ImgHost upload error:', error.message);
            })
            .finally(() => {
                if (globalFileInput) globalFileInput.value = '';
            });
    }

    function injectUploadButton(imgButton, parentForm, textarea) {
        if (!imgButton || !textarea || parentForm.querySelector('.kuskow-imghost-trigger')) return;

        const newButton = document.createElement('button');
        newButton.type = 'button';
        newButton.className = 'button button-icon-only kuskow-imghost-trigger';
        newButton.title = cfg.btnTitle;
        newButton.innerHTML = ORIGINAL_ICON;
        newButton.style.marginLeft = '4px';

        imgButton.parentNode.insertBefore(newButton, imgButton.nextSibling);

        newButton.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();

            const fileInput = getOrCreateFileInput();
            fileInput.onchange = function() {
                if (!fileInput.files || fileInput.files.length === 0) return;
                uploadAndInsertImages(fileInput.files, textarea);
            };
            fileInput.click();
        };

        textarea.addEventListener('paste', function(e) {
            if (!e.clipboardData || !e.clipboardData.files || e.clipboardData.files.length === 0) return;

            const pastedFiles = [];
            for (let i = 0; i < e.clipboardData.files.length; i++) {
                const file = e.clipboardData.files[i];
                if (file.type.indexOf('image/') !== -1) {
                    pastedFiles.push(file);
                }
            }

            if (pastedFiles.length > 0) {
                e.preventDefault();
                uploadAndInsertImages(pastedFiles, textarea);
            }
        });
    }

    function autoScanAndInject() {
        const imgButtons = document.querySelectorAll('[name="addbbcode16"], .bbcode-img');
        if (imgButtons.length > 0) {
            imgButtons.forEach(imgBtn => {
                const parentForm = imgBtn.closest('form') || imgBtn.closest('.quickreply') || imgBtn.closest('#qr_postform') || document;
                const textarea = parentForm.querySelector('textarea');
                if (textarea) {
                    injectUploadButton(imgBtn, parentForm, textarea);
                }
            });
        }
    }

    autoScanAndInject();
    const observer = new MutationObserver(() => autoScanAndInject());
    observer.observe(document.body, { childList: true, subtree: true });
})();
