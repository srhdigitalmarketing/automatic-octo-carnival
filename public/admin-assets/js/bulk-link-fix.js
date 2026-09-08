(()=>{
let running=false,stop=false;
document.addEventListener('click',async event=>{
 if(event.target.id==='bulk-fix-stop'){stop=true;return;}
 if(event.target.id!=='bulk-link-fix'||running)return;
 const button=event.target,host=document.getElementById('reported-host'),panel=document.getElementById('bulk-fix-progress'),status=document.getElementById('bulk-fix-status'),bar=document.getElementById('bulk-fix-bar'),log=document.getElementById('bulk-fix-log');
 panel.hidden=false;log.replaceChildren();
 if(!host.value){status.textContent='Pilih satu hostname terlebih dahulu.';return;}
 const selected=host.value;running=true;stop=false;button.disabled=true;host.disabled=true;
 const submit=document.querySelector('#reported-host-filter button[type="submit"]');submit.disabled=true;
 let cursor=0,processed=0,counts={success:0,skipped:0,failed:0};
 const request=async data=>{const response=await fetch(button.dataset.url,{method:'POST',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},body:new URLSearchParams({host:selected,...data})});const json=await response.json();if(!response.ok)throw Error(json.message||'Permintaan gagal');return json;};
 try{
  status.textContent='Menyiapkan daftar link…';const job=await request({action:'start'});bar.max=Math.max(1,job.total);bar.value=0;
  while(!stop&&processed<job.total){status.textContent=`Memproses ${processed}/${job.total} — Berhasil ${counts.success}, dilewati ${counts.skipped}, gagal ${counts.failed}`;
   const result=await request({action:'next',cursor,max_id:job.max_id});if(result.done)break;
   cursor=result.id;processed++;counts[result.state]++;bar.value=processed;
   if(processed%10===0&&window.jQuery&&jQuery.fn.dataTable.isDataTable('#reported-links-datatable'))jQuery('#reported-links-datatable').DataTable().ajax.reload(null,false);
   const li=document.createElement('li');li.textContent=`#${result.id} [${result.state}] ${result.message}`;log.prepend(li);if(log.children.length>200)log.lastChild.remove();
  }
  status.textContent=`${stop?'Dihentikan':'Selesai'} — ${processed} diproses. Berhasil ${counts.success}, dilewati ${counts.skipped}, gagal ${counts.failed}.`;
 }catch(error){status.textContent=error.message+' Proses berhenti; link yang berhasil tetap tersimpan.';}
 finally{running=false;button.disabled=false;host.disabled=false;submit.disabled=false;if(window.jQuery&&jQuery.fn.dataTable.isDataTable('#reported-links-datatable'))jQuery('#reported-links-datatable').DataTable().ajax.reload(null,false);}
});
})();
