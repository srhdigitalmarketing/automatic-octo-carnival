(function () {
    'use strict';
    async function check(cell) {
        const badge = cell.querySelector('.provider-result');
        const detail = cell.querySelector('.provider-result-detail');
        const button = cell.querySelector('button');
        button.disabled = true;
        badge.textContent = 'Memeriksa…';
        badge.style.color = '#334155';
        try {
            const response = await fetch(cell.dataset.url, {credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}});
            if (!response.ok) throw new Error('Request failed');
            const result = await response.json();
            if (!['connected','disconnected','paused'].includes(result.state)) throw new Error('Invalid result');
            badge.textContent = result.label;
            badge.style.color = result.state === 'connected' ? '#087443' : result.state === 'disconnected' ? '#b42318' : '#475569';
            badge.style.fontWeight = '700';
            detail.textContent = result.message + (result.checked_at ? ' • ' + new Date(result.checked_at).toLocaleString() : '');
        } catch (error) {
            badge.textContent = 'Gagal memeriksa';
            detail.textContent = 'Periksa koneksi atau login ulang, kemudian coba lagi.';
        } finally { button.disabled = false; }
    }
    const cells = [...document.querySelectorAll('.provider-connection')];
    cells.forEach(cell => cell.querySelector('button').addEventListener('click', () => check(cell)));
    // Sequential checks avoid a burst of outbound requests; the page renders immediately.
    (async () => { for (const cell of cells) await check(cell); })();
})();
