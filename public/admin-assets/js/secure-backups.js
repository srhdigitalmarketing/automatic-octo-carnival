(() => {
    const root = document.getElementById('secure-backups');
    if (!root) return;
    const get = id => document.getElementById(id);
    const list = get('secure-list'), panel = get('secure-progress'), status = get('secure-status');
    const spinner = get('secure-spinner'), bar = get('secure-bar'), confirmation = get('secure-confirm');
    let busy = false, plan = null;

    function controls() {
        root.querySelectorAll('button,input,select').forEach(el => el.disabled = busy);
        const execute = get('secure-restore-execute');
        if (execute) execute.disabled = busy || !plan || get('secure-restore-confirmation').value !== 'RESTORE';
    }
    function closeConfirmation() {
        plan = null;
        if (confirmation) { confirmation.hidden = true; get('secure-restore-confirmation').value = ''; }
        controls();
    }
    function render(entries) {
        list.replaceChildren();
        if (!entries.length) {
            const td = list.insertRow().insertCell(); td.colSpan = 5;
            td.className = 'text-center text-muted py-4'; td.textContent = 'Belum ada backup.'; return;
        }
        entries.forEach(item => {
            const tr = list.insertRow();
            [item.name, item.kind, new Date(item.created_at).toLocaleString('id-ID'), (item.size/1048576).toFixed(2)+' MB']
                .forEach(value => tr.insertCell().textContent = value);
            if (item.remote_uploads) {
                const note = document.createElement('small'); note.className = 'd-block text-success mt-1';
                note.textContent = 'Terkirim: '+Object.values(item.remote_uploads).map(x => x.provider+' ('+new Date(x.sent_at).toLocaleString('id-ID')+')').join(', ');
                tr.cells[0].append(note);
                Object.values(item.remote_uploads).forEach(x => {
                    const ref = document.createElement('small'); ref.className = 'd-block text-muted';
                    ref.textContent = x.provider+': '+(x.provider === 'drive' ? x.reference : x.name); tr.cells[0].append(ref);
                });
            }
            const cell = tr.insertCell(), link = document.createElement('a');
            link.href = root.dataset.download+'?id='+encodeURIComponent(item.id);
            link.className = 'btn btn-sm btn-outline-primary'; link.textContent = 'Download'; cell.append(link);
            [['remote-send', 'Kirim', 'primary'], ['restore-preview', 'Restore', 'warning'], ['delete', 'Hapus', 'danger']].forEach(([action, label, color]) => {
                const button = document.createElement('button'); button.type = 'button';
                button.className = 'btn btn-sm btn-outline-'+color; button.textContent = label;
                button.dataset.secureAction = action; button.dataset.id = item.id; cell.append(button);
            });
        });
    }
    async function run(action, id) {
        if (busy) return;
        if (action === 'delete' && !confirm('Hapus arsip backup ini? Data website tidak berubah.')) return;
        const data = new FormData();
        data.append('token', root.dataset.token); data.append('action', action);
        data.append('scope', get('secure-scope').value);
        if (id) data.append('id', id);
        data.append('destination', get('secure-destination')?.value || 'local');
        if (action === 'remote-save') {
            const form = get('secure-remote-'+id);
            if (!form || !form.reportValidity()) return;
            data.append('provider', id);
            for (const [key, value] of new FormData(form)) data.append(key, value);
        }
        if (['remote-send','remote-download'].includes(action) && data.get('destination') === 'local') {
            panel.hidden = false; spinner.hidden = true;
            status.textContent = 'Pilih FTP, Google Drive atau S3 pada Tujuan Backup terlebih dahulu.';
            bar.className = 'progress-bar bg-warning'; return;
        }
        if (action === 'remote-download') {
            const reference = get('secure-remote-reference').value.trim();
            if (!reference) { get('secure-remote-reference').focus(); return; }
            data.append('reference', reference);
        }
        if (action === 'restore') {
            if (!plan || plan.id !== id || get('secure-restore-confirmation').value !== 'RESTORE') return;
            data.append('nonce', plan.nonce); data.append('confirmation', 'RESTORE');
        } else closeConfirmation();
        if (action === 'upload') {
            const file = get('secure-file').files[0]; if (!file) return;
            data.append('backup_file', file);
        }
        busy = true; controls(); panel.hidden = false; spinner.hidden = false;
        status.textContent = action === 'remote-save' ? 'Menyimpan pengaturan tujuan backup...' :
            action === 'remote-download' ? 'Mengunduh dan memeriksa arsip remote...' :
            action === 'remote-send' ? 'Mengirim arsip ke tujuan backup terpilih...' :
            ['files','database'].includes(action) && data.get('destination') !== 'local' ? 'Membuat backup lokal, lalu mengirim ke tujuan terpilih...' :
            action === 'restore' ? 'Membuat backup pengaman, lalu memulihkan data…' :
            action === 'restore-preview' ? 'Memeriksa arsip dan tujuan restore…' :
            action === 'files' && data.get('scope') === 'full' ? 'Membuat backup database dan files, lalu mengemas Full Backup...' :
            action === 'upload' ? 'Mengunggah backup…' : 'Memproses backup…';
        bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
        try {
            const response = await fetch(root.dataset.url, {method:'POST', credentials:'same-origin', body:data, headers:{'X-Requested-With':'XMLHttpRequest'}});
            let result;
            try { result = await response.json(); }
            catch (error) { throw Error(action === 'restore' ? 'Respons restore terputus. Proses mungkin masih berjalan. Periksa website dan backup pengaman sebelum mencoba kembali.' : 'Respons server tidak valid. Periksa batas ukuran upload atau timeout aaPanel.'); }
            if (!response.ok) { if (Array.isArray(result.entries)) render(result.entries); throw Error(result.message || 'Proses gagal'); }
            if (action === 'restore-preview') {
                plan = {...result.plan, nonce:result.nonce};
                get('secure-restore-name').textContent = plan.name;
                get('secure-restore-target').textContent = plan.target;
                get('secure-restore-detail').textContent = plan.detail;
                confirmation.hidden = false;
                get('secure-restore-confirmation').focus();
            } else {
                render(result.entries);
                if (action === 'upload') get('secure-upload').reset();
                if (action === 'remote-save') {
                    const form = get('secure-remote-'+id), clear = form.querySelector('[name=clear_session_token]');
                    form.querySelectorAll('input[type=password]').forEach(input => {
                        const saved = (input.value !== '' || input.placeholder.startsWith('Tersimpan')) && !(input.name === 'session_token' && clear?.checked);
                        input.value = ''; input.placeholder = saved ? 'Tersimpan - kosongkan untuk mempertahankan' : '';
                    });
                    if (clear) clear.checked = false;
                }
            }
            status.textContent = result.message; bar.className = 'progress-bar bg-success';
        } catch (error) {
            status.textContent = action === 'restore' && error instanceof TypeError ?
                'Koneksi terputus. Proses restore mungkin masih berjalan. Periksa kondisi website sebelum mengulang.' : error.message;
            bar.className = 'progress-bar bg-danger';
        } finally {
            busy = false; spinner.hidden = true;
            if (action === 'restore') closeConfirmation();
            controls();
            if (action === 'restore-preview' && plan) get('secure-restore-confirmation').focus();
        }
    }
    root.addEventListener('click', event => {
        const button = event.target.closest('[data-secure-action]');
        if (button) run(button.dataset.secureAction, button.dataset.id);
    });
    get('secure-upload').addEventListener('submit', event => { event.preventDefault(); run('upload'); });
    root.querySelectorAll('[data-remote-provider]').forEach(form => form.addEventListener('submit', event => { event.preventDefault(); run('remote-save', form.dataset.remoteProvider); }));
    if (confirmation) {
        get('secure-restore-confirmation').addEventListener('input', controls);
        get('secure-restore-cancel').addEventListener('click', closeConfirmation);
        get('secure-restore-execute').addEventListener('click', () => { if (plan) run('restore', plan.id); });
    }
    window.addEventListener('beforeunload', event => { if (busy) { event.preventDefault(); event.returnValue = ''; } });
    render(JSON.parse(get('secure-initial').textContent)); controls();
})();
