(function () {
    'use strict';
    const panel = document.getElementById('banner-migration-panel');
    if (!panel) return;
    const start = document.getElementById('banner-migration-start'), stop = document.getElementById('banner-migration-stop');
    const status = document.getElementById('banner-migration-status');
    const progress = document.getElementById('banner-migration-progress'), log = document.getElementById('banner-migration-log');
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
        running = true; stopping = false; start.disabled = true; stop.disabled = false;
        log.replaceChildren(); progress.value = 0;
        let processed = 0, success = 0, skipped = 0, failed = 0;
        function summary() { return 'Diproses ' + processed + ' • Berhasil ' + success + ' • Dilewati ' + skipped + ' • Gagal ' + failed; }
        try {
            status.textContent = 'Memeriksa referensi banner lokal…';
            const init = await request({action:'start'});
            progress.max = Math.max(1, init.total);
            while (!stopping && processed < init.total) {
                status.textContent = 'Mengunggah banner ke R2… ' + summary();
                const result = await request({action:'next',token:init.token});
                if (result.done) break;
                processed++;
                if (result.state === 'success') success++; else if (result.state === 'skipped') skipped++; else failed++;
                progress.value = processed;
                const row = document.createElement('li');
                row.textContent = result.title + ' — ' + result.message;
                row.style.color = result.state === 'success' ? '#167347' : result.state === 'failed' ? '#b42318' : '#475467';
                log.prepend(row); if(log.children.length > 200) log.lastChild.remove();
            }
            status.textContent = (stopping ? 'Dihentikan. ' : 'Selesai. ') + summary();
        } catch (error) { status.textContent = 'Proses berhenti: ' + error.message + ' ' + summary(); }
        finally { running = false; start.disabled = false; stop.disabled = true; }
    });
})();
