(function () {
    'use strict';
    const panel = document.getElementById('auto-grab-panel');
    if (!panel) return;
    const start = document.getElementById('auto-grab-start'), stop = document.getElementById('auto-grab-stop');
    const api = document.getElementById('auto-grab-api'), status = document.getElementById('auto-grab-status');
    const progress = document.getElementById('auto-grab-progress'), log = document.getElementById('auto-grab-log');
    let running = false, stopping = false;
    async function request(data) {
        const response = await fetch(panel.dataset.url, {method:'POST', credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}, body:new URLSearchParams(data)});
        const result = await response.json();
        if (!response.ok || result.error) throw new Error(result.error || 'Permintaan gagal.');
        return result;
    }
    stop.addEventListener('click', function () { stopping = true; stop.disabled = true; status.textContent = 'Menunggu video saat ini selesai…'; });
    start.addEventListener('click', async function () {
        if (running) return;
        if (!api.value) { status.textContent = 'Tambahkan API VOD aktif terlebih dahulu.'; return; }
        running = true; stopping = false; start.disabled = true; api.disabled = true; stop.disabled = false;
        log.replaceChildren(); progress.value = 0;
        let cursor = 0, processed = 0, success = 0, skipped = 0, failed = 0;
        function summary() { return 'Diproses ' + processed + ' • Berhasil ' + success + ' • Dilewati ' + skipped + ' • Gagal ' + failed; }
        try {
            status.textContent = 'Menyiapkan video No Image…';
            const init = await request({action:'start',api_id:api.value});
            progress.max = Math.max(1, init.total);
            while (!stopping && processed < init.total) {
                status.textContent = 'Memproses video berikutnya… ' + summary();
                const result = await request({action:'next',api_id:api.value,cursor:cursor,max_id:init.max_id});
                if (result.done) break;
                if (!result.id || result.id <= cursor) throw new Error('Cursor proses tidak valid.');
                cursor = result.id; processed++;
                if (result.state === 'success') success++; else if (result.state === 'skipped') skipped++; else failed++;
                progress.value = processed;
                const row = document.createElement('li');
                row.textContent = '#' + result.id + ' ' + result.title + ' — ' + result.message;
                row.style.color = result.state === 'success' ? '#167347' : result.state === 'failed' ? '#b42318' : '#475467';
                log.prepend(row); if(log.children.length > 200) log.lastChild.remove();
            }
            status.textContent = (stopping ? 'Dihentikan. ' : 'Selesai. ') + summary();
        } catch (error) { status.textContent = 'Proses berhenti: ' + error.message + ' ' + summary(); }
        finally { running = false; start.disabled = false; api.disabled = false; stop.disabled = true; }
    });
})();
