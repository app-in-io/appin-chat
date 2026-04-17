(function () {
    'use strict';

    var i18n = (window.AppInChatSettings && window.AppInChatSettings.i18n) || { remove: 'Remove' };

    function bindRemove(btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var target = btn.dataset.target;
            var input = document.getElementById(target);
            var preview = document.getElementById(target + '-preview');
            if (input) {
                input.value = '';
            }
            if (preview) {
                preview.innerHTML = '';
            }
            btn.remove();
        });
    }

    document.querySelectorAll('input[type="color"]').forEach(function (picker) {
        var textInput = picker.nextElementSibling;
        if (!textInput || !textInput.dataset.colorText) {
            return;
        }

        picker.addEventListener('input', function () {
            textInput.value = picker.value;
        });
        textInput.addEventListener('input', function () {
            if (/^#[0-9a-fA-F]{6}$/.test(textInput.value)) {
                picker.value = textInput.value;
            } else if (textInput.value === '') {
                picker.value = picker.dataset.default || '#000000';
            }
        });
    });

    document.querySelectorAll('.appin-upload-image').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) {
                return;
            }
            var target = btn.dataset.target;
            var frame = wp.media({ multiple: false, library: { type: 'image' } });
            frame.on('select', function () {
                var url = frame.state().get('selection').first().toJSON().url;
                var input = document.getElementById(target);
                var preview = document.getElementById(target + '-preview');
                if (input) {
                    input.value = url;
                }
                if (preview) {
                    var img = document.createElement('img');
                    img.src = url;
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '80px';
                    img.style.display = 'block';
                    preview.innerHTML = '';
                    preview.appendChild(img);
                }
                var removeBtn = btn.nextElementSibling;
                if (removeBtn && removeBtn.classList.contains('appin-remove-image')) {
                    removeBtn.style.display = '';
                } else {
                    var rm = document.createElement('button');
                    rm.type = 'button';
                    rm.className = 'button appin-remove-image';
                    rm.dataset.target = target;
                    rm.textContent = i18n.remove;
                    btn.after(rm);
                    bindRemove(rm);
                }
            });
            frame.open();
        });
    });

    document.querySelectorAll('.appin-remove-image').forEach(bindRemove);
})();
