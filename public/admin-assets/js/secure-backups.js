(() => {
    const root = document.getElementById('secure-backups');
    if (!root) return;
    const get = id => document.getElementById(id);
    const list = get('secure-list'), panel = get('secure-progress'), status = get('secure-status');
    const spinner = get('secure-spinner'), bar = get('secure-bar'), confirmation = get('secure-confirm');
    let busy = false, plan = null, remoteCursor = '';
    const source = get('secure-restore-provider'), archives = get('secure-remote-reference');
    function resetRemote(message) {
        if (!source) return;
        remoteCursor = ''; archives.replaceChildren(new Option('Pilih arsip backup', ''));
        get('secure-remote-more').hidden = true; get('secure-remote-message').textContent = message;
    }

    function controls() {
        root.querySelectorAll('button,input,select').forEach(el => el.disabled = busy);
        if (get('secure-remote-restore')) get('secure-remote-restore').disabled = busy || !source?.value || !archives.value;
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
        let previewId = null, refreshRemote = false;
        const data = new FormData();
        data.append('token', root.dataset.token); data.append('action', action);
        data.append('scope', get('secure-scope').value);
        if (id) data.append('id', id);
        data.append('destination', get('secure-destination')?.value || 'local');
        if (['remote-list','remote-download'].includes(action) && source) {
            if (!source.value) { resetRemote('Simpan pengaturan remote terlebih dahulu.'); controls(); return; }
            data.set('destination', source.value);
        }
        if (action === 'remote-list') {
            if (id === 'more' && remoteCursor) data.append('cursor', remoteCursor);
            else resetRemote('Memuat daftar arsip...');
        }
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
            action === 'remote-list' ? 'Membaca daftar arsip remote...' :
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
            if (action === 'remote-list') {
                const items = result.remote.items;
                const seen = new Set(Array.from(archives.options, x => x.value));
                items.forEach(item => { if (!seen.has(item.reference)) {
                    const size = item.size == null ? '' : ' ('+(Number(item.size)/1048576).toFixed(2)+' MB)';
                    archives.add(new Option(item.name+size, item.reference)); seen.add(item.reference);
                }});
                remoteCursor = result.remote.cursor || ''; get('secure-remote-more').hidden = !remoteCursor;
                get('secure-remote-message').textContent = (archives.options.length-1)+' arsip tersedia.'+(remoteCursor ? ' Klik Muat berikutnya untuk arsip lainnya.' : '');
            } else if (action === 'restore-preview') {
                plan = {...result.plan, nonce:result.nonce};
                get('secure-restore-name').textContent = plan.name;
                get('secure-restore-target').textContent = plan.target;
                get('secure-restore-detail').textContent = plan.detail;
                confirmation.hidden = false;
                get('secure-restore-confirmation').focus();
            } else {
                render(result.entries);
                if (action === 'upload') get('secure-upload').reset();
                if (action === 'remote-download') previewId = result.imported_id;
                if (action === 'remote-save') {
                    const form = get('secure-remote-'+id), clear = form.querySelector('[name=clear_session_token]');
                    form.querySelectorAll('input[type=password]').forEach(input => {
                        const saved = (input.value !== '' || input.placeholder.startsWith('Tersimpan')) && !(input.name === 'session_token' && clear?.checked);
                        input.value = ''; input.placeholder = saved ? 'Tersimpan - kosongkan untuk mempertahankan' : '';
                    });
                    if (clear) clear.checked = false;
                    if (source) {
                        if (!Array.from(source.options).some(x => x.value === id)) source.add(new Option({ftp:'FTP / FTPS',drive:'Google Drive',s3:'S3 / R2 / B2'}[id],id));
                        source.value = id; refreshRemote = true;
                    }
                }
            }
            status.textContent = result.message; bar.className = 'progress-bar bg-success';
        } catch (error) {
            status.textContent = action === 'restore' && error instanceof TypeError ?
                'Koneksi terputus. Proses restore mungkin masih berjalan. Periksa kondisi website sebelum mengulang.' : error.message;
            bar.className = 'progress-bar bg-danger';
            if (action === 'remote-list' && source) get('secure-remote-message').textContent = error.message;
        } finally {
            busy = false; spinner.hidden = true;
            if (action === 'restore') closeConfirmation();
            controls();
            if (action === 'restore-preview' && plan) get('secure-restore-confirmation').focus();
        }
        if (previewId) await run('restore-preview', previewId);
        else if (refreshRemote) await run('remote-list');
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
    if (source) {
        source.addEventListener('change', () => { resetRemote('Memuat daftar arsip...'); controls(); run('remote-list'); });
        archives.addEventListener('change', controls);
        if (source.value) run('remote-list');
    }
})();
