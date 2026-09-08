(()=>{
'use strict';
function enhance(root=document){
 root.querySelectorAll('input[type="file"]:not([data-bootstrap-file])').forEach(input=>{
  input.dataset.bootstrapFile='1';
  const wrap=document.createElement('div');wrap.className='custom-file mb-2';wrap.style.maxWidth='100%';
  input.before(wrap);wrap.append(input);input.classList.add('custom-file-input');
  if(!input.id)input.id='admin-file-'+(++enhance.id);
  const label=document.createElement('label');label.className='custom-file-label text-truncate';label.htmlFor=input.id;label.setAttribute('data-browse','Pilih file');wrap.append(label);
  const update=()=>{const names=Array.from(input.files||[],file=>file.name);label.textContent=names.length?names.join(', '):'Belum ada file dipilih';label.title=label.textContent;};
  input.addEventListener('change',update);if(input.form)input.form.addEventListener('reset',()=>setTimeout(update,0));update();
 });
 root.querySelectorAll('progress:not([data-bootstrap-progress])').forEach(native=>{
  native.dataset.bootstrapProgress='1';native.hidden=true;
  const wrap=document.createElement('div');wrap.className='progress my-3';wrap.style.height='12px';wrap.style.borderRadius='8px';
  const bar=document.createElement('div');bar.className='progress-bar bg-primary';bar.setAttribute('role','progressbar');bar.setAttribute('aria-valuemin','0');bar.setAttribute('aria-valuemax','100');bar.setAttribute('aria-label',native.getAttribute('aria-label')||'Progres');wrap.append(bar);native.after(wrap);
  const prefix=native.id.replace(/-progress$/,'');const start=document.getElementById(prefix+'-start'),stop=document.getElementById(prefix+'-stop');
  const spinner=document.createElement('span');spinner.className='spinner-border spinner-border-sm mr-2';spinner.setAttribute('aria-hidden','true');spinner.hidden=true;if(start)start.prepend(spinner);
  const update=()=>{const percent=Math.round(Math.min(100,Math.max(0,native.value/Math.max(1,native.max)*100)));bar.style.width=percent+'%';bar.setAttribute('aria-valuenow',String(percent));const active=!!(start&&start.disabled);bar.classList.toggle('progress-bar-striped',active);bar.classList.toggle('progress-bar-animated',active);spinner.hidden=!active;if(stop)stop.hidden=!active;};
  const observer=new MutationObserver(update);observer.observe(native,{attributes:true,attributeFilter:['value','max']});if(start)observer.observe(start,{attributes:true,attributeFilter:['disabled']});update();
 });
}
enhance.id=0;
function init(){enhance();new MutationObserver(records=>{if(records.some(record=>Array.from(record.addedNodes).some(node=>node.nodeType===1)))enhance();}).observe(document.body,{childList:true,subtree:true});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
