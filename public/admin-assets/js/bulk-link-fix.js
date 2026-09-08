(()=>{
let running=false,stop=false;
document.addEventListener('click',async event=>{
 if(event.target.id==='bulk-fix-stop'){stop=true;event.target.disabled=true;event.target.textContent='Menghentikan…';return;}
 if(event.target.id!=='bulk-link-fix'||running)return;
 const button=event.target,host=document.getElementById('reported-host'),panel=document.getElementById('bulk-fix-progress'),status=document.getElementById('bulk-fix-status'),bar=document.getElementById('bulk-fix-bar'),log=document.getElementById('bulk-fix-log');
 const spinner=document.getElementById('bulk-fix-spinner'),stopButton=document.getElementById('bulk-fix-stop');
 const activity=active=>{if(spinner)spinner.hidden=!active;stopButton.hidden=!active;bar.classList.toggle('progress-bar-striped',active);bar.classList.toggle('progress-bar-animated',active);panel.setAttribute('aria-busy',String(active));};
 const progress=value=>{bar.style.width=value+'%';bar.setAttribute('aria-valuenow',String(value));};
 panel.hidden=false;log.replaceChildren();activity(false);progress(0);bar.className='progress-bar bg-primary';status.className='small mb-3';
 if(!host.value){status.textContent='Pilih satu hostname terlebih dahulu.';status.classList.add('text-warning');return;}
 activity(true);stopButton.disabled=false;stopButton.textContent='Hentikan proses';
 const selected=host.value;running=true;stop=false;button.disabled=true;host.disabled=true;
 const submit=document.querySelector('#reported-host-filter button[type="submit"]');submit.disabled=true;
 let cursor=0,processed=0,counts={success:0,skipped:0,failed:0};
 const request=async data=>{const response=await fetch(button.dataset.url,{method:'POST',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},body:new URLSearchParams({host:selected,...data})});const json=await response.json();if(!response.ok)throw Error(json.message||'Permintaan gagal');return json;};
 try{
  status.textContent='Menyiapkan daftar link…';const job=await request({action:'start'});progress(0);
  while(!stop&&processed<job.total){status.textContent=`Memproses ${processed}/${job.total} — Berhasil ${counts.success}, dilewati ${counts.skipped}, gagal ${counts.failed}`;
   const result=await request({action:'next',cursor,max_id:job.max_id});if(result.done)break;
   cursor=result.id;processed++;counts[result.state]++;progress(Math.round(processed/Math.max(1,job.total)*100));
   if(processed%10===0&&window.jQuery&&jQuery.fn.dataTable.isDataTable('#reported-links-datatable'))jQuery('#reported-links-datatable').DataTable().ajax.reload(null,false);
   const li=document.createElement('li');li.className='list-group-item px-0 py-2 small '+(result.state==='success'?'text-success':result.state==='failed'?'text-danger':'text-muted');li.textContent=`#${result.id} [${result.state}] ${result.message}`;log.prepend(li);if(log.children.length>200)log.lastChild.remove();
  }
  if(!stop)progress(100);bar.classList.replace('bg-primary',stop?'bg-warning':counts.failed?'bg-warning':'bg-success');
  status.textContent=`${stop?'Dihentikan':'Selesai'} — ${processed} diproses. Berhasil ${counts.success}, dilewati ${counts.skipped}, gagal ${counts.failed}.`;
 }catch(error){bar.classList.replace('bg-primary','bg-danger');status.classList.add('text-danger');status.textContent=error.message+' Proses berhenti; link yang berhasil tetap tersimpan.';}
 finally{activity(false);running=false;button.disabled=false;host.disabled=false;submit.disabled=false;if(window.jQuery&&jQuery.fn.dataTable.isDataTable('#reported-links-datatable'))jQuery('#reported-links-datatable').DataTable().ajax.reload(null,false);}
});
})();
