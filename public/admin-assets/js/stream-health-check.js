(function () {
 'use strict';
 document.addEventListener('click', async function (event) {
  const button = event.target.closest('.stream-check-now');
  if (!button || button.disabled) return;
  const group = button.closest('.st-group');
  const message = group.querySelector('.stream-check-message');
  const badge = group.querySelector('.stream-server-badge');
  button.disabled = true;
  message.textContent = 'Memeriksa URL yang sudah tersimpan…';
  try {
   const response = await fetch(button.dataset.url, {method:'POST',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}});
   const result = await response.json();
   if (!response.ok) throw new Error(result.message || 'Pemeriksaan gagal.');
   const labels = {available:'Healthy',deleted:'Deleted',error:'Error',processing:'Processing',unknown:'Check failed'};
   badge.textContent = labels[result.status] || 'Check failed';
   badge.className = 'stream-server-badge ' + (result.status === 'available' ? 'is-healthy' : ['deleted','error'].includes(result.status) ? 'is-broken' : 'is-unchecked');
   badge.title = result.message;
   message.textContent = result.message;
  } catch (error) { message.textContent = error.message || 'Gagal memeriksa. Coba lagi.'; }
  finally { button.disabled = false; }
 });
})();
