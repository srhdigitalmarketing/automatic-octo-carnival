(function () {
 'use strict';
 function refreshStatus(scope) {
  (scope || document).querySelectorAll('.stream-health-status').forEach(panel => {
   const container = panel.closest('.st-group') || panel.closest('form');
   const input = container && container.querySelector('input[name$="[url]"], input[name="link"]');
   panel.hidden = !input || input.value !== panel.dataset.savedUrl;
  });
 }
 document.addEventListener('input', () => refreshStatus());
 document.addEventListener('change', () => refreshStatus());
 refreshStatus();
 document.addEventListener('click', async function (event) {
  refreshStatus();
  const button = event.target.closest('.stream-check-now');
  if (!button || button.disabled || button.closest('.stream-health-status')?.hidden) return;
  const group = button.closest('.st-group');
  const message = group.querySelector('.stream-check-message');
  const badge = group.querySelector('.stream-server-badge');
  button.disabled = true;
  message.textContent = 'Memeriksa URL yang sudah tersimpan…';
  try {
   const response = await fetch(button.dataset.url, {method:'POST',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}});
   const result = await response.json();
   if (!response.ok) throw new Error(result.message || 'Pemeriksaan gagal.');
   if (result.replacement_url) {
    group.querySelector('input[name$="[url]"]').value = result.replacement_url;
    const statusPanel = group.querySelector('.stream-health-status');
    if (statusPanel) { statusPanel.dataset.savedUrl = result.replacement_url;
     const host = group.querySelector('.stream-server-host');
     if (host) host.textContent = new URL(result.replacement_url).hostname;
    }
    const idField = group.querySelector('input[name$="[upnshare_video_id]"]');
    if (idField) idField.value = result.replacement_id;
    const apiField = group.querySelector('input[name$="[api_id]"]');
    if (apiField) apiField.value = result.api_id;
   }
   const labels = {reachable:'HTTP reachable',available:'Healthy',deleted:'Deleted',error:'Error',processing:'Processing',unknown:'Check failed'};
   badge.textContent = labels[result.status] || 'Check failed';
   badge.className = 'stream-server-badge ' + (result.status === 'available' ? 'is-healthy' : ['deleted','error'].includes(result.status) ? 'is-broken' : 'is-unchecked');
   badge.title = result.message;
   message.textContent = result.message;
  } catch (error) { message.textContent = error.message || 'Gagal memeriksa. Coba lagi.'; }
  finally { button.disabled = false; }
 });
})();
