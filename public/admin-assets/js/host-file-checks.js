(function () {
    'use strict';
    document.querySelectorAll('.host-file-check-control').forEach(function (form) {
        const toggle = form.querySelector('[type="checkbox"]');
        const label = form.querySelector('.host-file-check-label');
        const message = form.querySelector('.host-file-check-message');
        const save = form.querySelector('.host-file-check-save');
        let saving = false;
        save.hidden = true;
        async function persist(event) {
            if (event) event.preventDefault();
            if (saving || toggle.disabled) return;
            saving = true;
            const before = form.dataset.saved === '1';
            const desired = toggle.checked;
            toggle.disabled = true;
            message.textContent = 'Menyimpan…';
            message.classList.remove('text-danger');
            const abort = new AbortController();
            const timeout = setTimeout(function () { abort.abort(); }, 15000);
            try {
                const response = await fetch(form.action, {method:'POST', credentials:'same-origin', signal:abort.signal,
                    headers:{'X-Requested-With':'XMLHttpRequest'},
                    body:new URLSearchParams({api_id:form.querySelector('[name="api_id"]').value, enabled:desired ? '1' : '0'})});
                const result = await response.json();
                if (!response.ok || result.error || typeof result.enabled !== 'boolean') throw new Error('Save failed');
                toggle.checked = result.enabled;
                form.dataset.saved = result.enabled ? '1' : '0';
                message.textContent = result.message;
            } catch (error) {
                toggle.checked = before;
                message.textContent = 'Penyimpanan belum terkonfirmasi. Muat ulang halaman untuk melihat status terbaru, lalu coba lagi.';
                message.classList.add('text-danger');
            } finally {
                clearTimeout(timeout);
                label.textContent = toggle.checked ? 'aktif' : 'nonaktif';
                toggle.disabled = false;
                saving = false;
            }
        }
        toggle.addEventListener('change', persist);
        form.addEventListener('submit', persist);
    });
})();
