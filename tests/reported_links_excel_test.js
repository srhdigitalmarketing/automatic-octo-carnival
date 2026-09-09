const assert=require('node:assert/strict');
const excel=require('../public/admin-assets/js/reported-links-excel.js');
const Zip=require('../public/admin-assets/vendors/jszip/jszip.min.js');
(async()=>{
const rows=[
 {id:1,title:' Drone  Shots ',link:'https://a.test/#A'},
 {id:2,title:'drone shots',link:'https://b.test/#B'},
 {id:3,title:'Other',link:'https://a.test/#A'},
 {id:4,title:'=SUM(1,2)',link:'https://a.test/#a'},
 {id:5,title:'[Video tidak ditemukan]',link:'https://a.test/#C'},
 {id:6,title:'[Video tidak ditemukan]',link:'https://a.test/#D'}
];
const ids=mode=>excel.unique(rows,mode).map(r=>r.id);
assert.deepEqual(ids('link'),[1,2,4,5,6]);
assert.deepEqual(ids('title'),[1,3,4,5,6]);
assert.deepEqual(ids('either'),[1,4,5,6]);
assert.equal(rows.length,6);
const buffer=await excel.create(excel.unique(rows,'either'),Zip,'nodebuffer');
const zip=await Zip.loadAsync(buffer);const sheet=await zip.file('xl/worksheets/sheet1.xml').async('string');
assert(sheet.includes('A1:B5'));assert(sheet.includes('t="inlineStr"'));assert(sheet.includes('=SUM(1,2)'));assert(!sheet.includes('<f>'));
assert(sheet.includes('autoFilter'));assert(sheet.includes('state="frozen"'));
for(const name of ['[Content_Types].xml','_rels/.rels','xl/workbook.xml','xl/_rels/workbook.xml.rels'])assert(zip.file(name));
const empty=await Zip.loadAsync(await excel.create([],Zip,'nodebuffer'));assert((await empty.file('xl/worksheets/sheet1.xml').async('string')).includes('A1:B1'));
await assert.rejects(()=>excel.create([{title:'x'.repeat(32768),link:'https://a.test'}],Zip,'nodebuffer'),/32767/);
console.log('PASS: XLSX packaging, text cells, empty export and link/title deduplication.');
})().catch(e=>{console.error(e);process.exitCode=1});
