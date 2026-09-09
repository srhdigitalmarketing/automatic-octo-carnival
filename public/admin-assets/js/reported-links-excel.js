(function(root){
'use strict';
function unique(rows,mode){
 const links=new Set(),titles=new Set();const result=[];
 for(const row of rows){
  const link=String(row.link||'').trim();
  const title=String(row.title||'').trim().replace(/\s+/g,' ').toLowerCase();
  const titleKey=title==='[video tidak ditemukan]'?'':title;
  const duplicate=((mode==='link'||mode==='either')&&link&&links.has(link))||((mode==='title'||mode==='either')&&titleKey&&titles.has(titleKey));
  if(duplicate)continue;
  if(link)links.add(link);if(titleKey)titles.add(titleKey);result.push(row);
 }
 return result;
}
function xml(value){const s=String(value??'').replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\uFFFE\uFFFF]/g,'');if(s.length>32767)throw Error('Teks melebihi batas sel Excel (32767 karakter).');return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
async function create(rows,Zip,type){
 if(!Zip)throw Error('Library Excel belum dimuat. Muat ulang halaman.');
 if(rows.length>1048575)throw Error('Jumlah laporan melebihi batas satu sheet Excel. Pilih host tertentu.');
 const ns='http://schemas.openxmlformats.org/spreadsheetml/2006/main';
 const zip=new Zip();
 zip.file('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
 zip.file('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
 zip.file('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="'+ns+'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reported Links" sheetId="1" r:id="rId1"/></sheets></workbook>');
 zip.file('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
 const end=rows.length+1;
 const data=[['Judul','Link Error'],...rows.map(row=>[row.title,row.link])].map((values,i)=>'<row r="'+(i+1)+'">'+values.map((v,c)=>'<c r="'+String.fromCharCode(65+c)+(i+1)+'" t="inlineStr"><is><t xml:space="preserve">'+xml(v)+'</t></is></c>').join('')+'</row>').join('');
 zip.file('xl/worksheets/sheet1.xml','<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="'+ns+'"><dimension ref="A1:B'+end+'"/><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="1" width="50" customWidth="1"/><col min="2" max="2" width="90" customWidth="1"/></cols><sheetData>'+data+'</sheetData><autoFilter ref="A1:B'+end+'"/></worksheet>');
 return zip.generateAsync({type:type||'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'});
}
const api={unique,create};if(typeof module!=='undefined'&&module.exports)module.exports=api;else root.reportedLinksExcel=api;
})(typeof window!=='undefined'?window:this);
