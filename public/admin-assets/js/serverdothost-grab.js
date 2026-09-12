(function () {
    'use strict';
    const panel = document.getElementById('serverdothost-grab-panel');
    if (!panel) return;
    const api = document.getElementById('serverdothost-grab-api');
    const start = document.getElementById('serverdothost-grab-start');
    const stop = document.getElementById('serverdothost-grab-stop');
    const status = document.getElementById('serverdothost-grab-status');
    const progress = document.getElementById('serverdothost-grab-progress');
    const log = document.getElementById('serverdothost-grab-log');
    let running = false, stopping = false;
    async function request(data) {
        const response = await fetch(panel.dataset.url, {method:'POST', credentials:'same-origin',
            headers:{'X-Requested-With':'XMLHttpRequest'}, body:new URLSearchParams(data)});
        let result;
        try { result = await response.json(); }
        catch (error) { throw new Error('Respons tidak valid. Periksa koneksi dan sesi login admin.'); }
        if (!response.ok || result.error) throw new Error(result.error || 'Permintaan gagal.');
        return result;
    }
    function updateProgress(percent) {
        progress.style.width = percent + '%';
        progress.textContent = percent + '%';
        progress.setAttribute('aria-valuenow', String(percent));
    }
    stop.addEventListener('click', function () {
        stopping = true; stop.disabled = true;
        status.textContent = 'Menunggu permintaan saat ini selesai…';
    });
    start.addEventListener('click', async function () {
        if (running) return;
        if (!api.value) { status.textContent = 'Tambahkan ServerDotHost aktif terlebih dahulu.'; return; }
        running = true; stopping = false;
        start.disabled = true; api.disabled = true; stop.disabled = false;
        log.replaceChildren(); updateProgress(0);
        progress.classList.add('progress-bar-striped', 'progress-bar-animated');
        let processed = 0, success = 0, skipped = 0, total = 0;
        const summary = () => 'Diproses ' + processed + '/' + total + ' • Ditambahkan ' + success + ' • Dilewati ' + skipped;
        try {
            status.textContent = 'Menyiapkan daftar video…';
            const init = await request({action:'start', api_id:api.value});
            total = init.total;
            let done = total === 0;
            while (!stopping && !done) {
                const result = await request({action:'next', token:init.token});
                processed = result.processed; success = result.success; skipped = result.skipped; done = result.done;
                updateProgress(total ? Math.min(100, Math.round(processed * 100 / total)) : 100);
                if (result.state) {
                    const row = document.createElement('li');
                    row.textContent = result.title + ' — ' + result.message;
                    row.style.color = result.state === 'success' ? '#167347' : '#475467';
                    log.prepend(row);
                    if (log.children.length > 200) log.lastChild.remove();
                }
                status.textContent = (stopping ? 'Menghentikan proses… ' : (result.title ? result.title + ' — ' + result.message + ' ' : '')) + summary();
                if (!done && !stopping) await new Promise(resolve => setTimeout(resolve, 300));
            }
            if (stopping && !done) await request({action:'stop', token:init.token});
            if (!stopping) updateProgress(100);
            status.textContent = (stopping ? 'Dihentikan. ' : 'Selesai. ') + summary();
        } catch (error) {
            status.textContent = 'Proses berhenti: ' + error.message + ' ' + summary();
        } finally {
            progress.classList.remove('progress-bar-striped', 'progress-bar-animated');
            running = false; start.disabled = !api.value; api.disabled = false; stop.disabled = true;
        }
    });
})();
