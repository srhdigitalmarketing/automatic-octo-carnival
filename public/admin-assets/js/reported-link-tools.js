(()=>{
let running=false, stopped=false;
document.addEventListener('click',async event=>{
 const button=event.target.closest('button');if(!button)return;
 if(button.id==='reported-tools-stop'){stopped=true;button.disabled=true;return;}
 if(!['bulk-report-clear','export-error-links'].includes(button.id)||running)return;
 const host=document.getElementById('reported-host');if(host.disabled)return;
 const clear=button.id==='bulk-report-clear', selected=host.value;
 const mode=document.getElementById('reported-export-duplicates')?.value||'either';
 if(clear&&!window.confirm(`Clear semua laporan untuk ${selected||'semua host'} di seluruh halaman tabel? Link dan status kesehatan tetap disimpan. Export dahulu jika laporan masih diperlukan.`))return;
 running=true;stopped=false;
 const controls=Array.from(document.querySelectorAll('#reported-host-filter button, #reported-host-filter select'));
 const states=controls.map(el=>el.disabled);controls.forEach(el=>el.disabled=true);
 const panel=document.getElementById('reported-tools-progress'),status=document.getElementById('reported-tools-status'),bar=document.getElementById('reported-tools-bar'),stop=document.getElementById('reported-tools-stop');
 panel.hidden=false;panel.setAttribute('aria-busy','true');stop.hidden=false;stop.disabled=false;
 bar.className='progress-bar progress-bar-striped progress-bar-animated';
 const progress=value=>{bar.style.width=value+'%';bar.setAttribute('aria-valuenow',String(value));};progress(0);
 const request=async data=>{const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),30000);try{
  const res=await fetch(button.dataset.url,{method:'POST',credentials:'same-origin',signal:controller.signal,headers:{'X-Requested-With':'XMLHttpRequest'},body:new URLSearchParams({host:selected,...data})});
  const json=await res.json();if(!res.ok)throw Error(json.message||'Permintaan gagal');return json;
 }finally{clearTimeout(timeout);}};
 let processed=0,exported=0;const rows=[];
 try{
  status.textContent='Menyiapkan laporan…';const job=await request({action:'start'});let cursor=0,done=job.total===0;
  while(!stopped&&!done){
   const result=await request({action:clear?'clear':'export',cursor,max_id:job.max_id});
   if(!result.done&&result.cursor<=cursor)throw Error('Progres tidak berubah; proses dihentikan.');
   cursor=result.cursor;processed+=result.count;done=result.done;
   if(!clear)for(const row of result.rows)rows.push(row);
   progress(Math.min(100,Math.round(processed/Math.max(1,job.total)*100)));status.textContent=`${clear?'Clear':'Export'} ${processed}/${job.total} laporan…`;
  }
  if(!stopped){
   if(!clear){
    status.textContent='Menyaring duplikat dan membuat Excel…';
    if(!window.reportedLinksExcel)throw Error('Modul Excel belum dimuat. Muat ulang halaman.');
    const unique=window.reportedLinksExcel.unique(rows,mode);exported=unique.length;
    const blob=await window.reportedLinksExcel.create(unique,window.JSZip);
    if(!stopped){const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='error-links-'+(selected||'all-hosts')+'.xlsx';document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);}
   }
   if(!stopped)progress(100);
  }
  status.textContent=stopped?`Dihentikan. ${processed} diproses.${clear?' Clear yang selesai tetap tersimpan.':' Export dibatalkan, file tidak diunduh.'}`:(clear?`Selesai: ${processed} laporan dibersihkan.`:`Selesai: ${exported} baris Excel dari ${processed} laporan; ${processed-exported} duplikat dilewati.`);
 }catch(error){status.textContent=`Gagal: ${error.message}. ${clear?'Batch clear yang selesai tetap tersimpan.':'File export tidak diunduh.'}`;bar.classList.add('bg-danger');}
 finally{
  running=false;panel.setAttribute('aria-busy','false');bar.classList.remove('progress-bar-striped','progress-bar-animated');stop.hidden=true;controls.forEach((el,i)=>el.disabled=states[i]);
  if(clear&&window.jQuery&&jQuery.fn.dataTable.isDataTable('#reported-links-datatable'))jQuery('#reported-links-datatable').DataTable().ajax.reload(null,false);
 }
});
})();
