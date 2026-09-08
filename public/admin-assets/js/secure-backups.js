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
            const cell = tr.insertCell(), link = document.createElement('a');
            link.href = root.dataset.download+'?id='+encodeURIComponent(item.id);
            link.className = 'btn btn-sm btn-outline-primary'; link.textContent = 'Download'; cell.append(link);
            [['restore-preview', 'Restore', 'warning'], ['delete', 'Hapus', 'danger']].forEach(([action, label, color]) => {
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
        if (action === 'restore') {
            if (!plan || plan.id !== id || get('secure-restore-confirmation').value !== 'RESTORE') return;
            data.append('nonce', plan.nonce); data.append('confirmation', 'RESTORE');
        } else closeConfirmation();
        if (action === 'upload') {
            const file = get('secure-file').files[0]; if (!file) return;
            data.append('backup_file', file);
        }
        busy = true; controls(); panel.hidden = false; spinner.hidden = false;
        status.textContent = action === 'restore' ? 'Membuat backup pengaman, lalu memulihkan data…' :
            action === 'restore-preview' ? 'Memeriksa arsip dan tujuan restore…' :
            action === 'upload' ? 'Mengunggah backup…' : 'Memproses backup…';
        bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
        try {
            const response = await fetch(root.dataset.url, {method:'POST', credentials:'same-origin', body:data, headers:{'X-Requested-With':'XMLHttpRequest'}});
            let result;
            try { result = await response.json(); }
            catch (error) { throw Error(action === 'restore' ? 'Respons restore terputus. Proses mungkin masih berjalan. Periksa website dan backup pengaman sebelum mencoba kembali.' : 'Respons server tidak valid. Periksa batas ukuran upload atau timeout aaPanel.'); }
            if (!response.ok) throw Error(result.message || 'Proses gagal');
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
    if (confirmation) {
        get('secure-restore-confirmation').addEventListener('input', controls);
        get('secure-restore-cancel').addEventListener('click', closeConfirmation);
        get('secure-restore-execute').addEventListener('click', () => { if (plan) run('restore', plan.id); });
    }
    window.addEventListener('beforeunload', event => { if (busy) { event.preventDefault(); event.returnValue = ''; } });
    render(JSON.parse(get('secure-initial').textContent)); controls();
})();
