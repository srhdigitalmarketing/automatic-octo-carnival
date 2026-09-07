const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');
(async () => {
    const browser = await chromium.launch({ headless: true, channel: 'msedge' });
    try {
        const page = await browser.newPage();
        await page.addScriptTag({ path: path.join(__dirname, '../public/admin-assets/js/video-excel-export.js') });
        const result = await page.evaluate(() => {
            const records = [
                { DT_RowData: { excel: { id: 42, name: '=1+1 & <Title>', video_id: '000123', image: 'https://images.example.com/a.jpg?x=1&y=2', embed: 'https://example.com/embed/movie?imdb=000123' } } },
                { DT_RowData: { excel: { id: 43, name: 'No artwork', video_id: 'tt456', image: '', embed: 'https://example.com/embed/movie?imdb=tt456' } } }
            ];
            const config = window.videoExcelExport(() => records);
            const exported = {};
            config.customizeData(exported);
            const doc = new DOMParser().parseFromString('<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:I3"/><cols><col min="1" max="9" width="20"/></cols><sheetData><row r="1"><c r="A1"/></row></sheetData></worksheet>', 'application/xml');
            config.customize({ xl: { worksheets: { 'sheet1.xml': doc } } });
            const xml = new XMLSerializer().serializeToString(doc);
            const reread = new DOMParser().parseFromString(xml, 'application/xml');
            const cells = Array.from(reread.querySelectorAll('c')).map(c => ({ ref: c.getAttribute('r'), type: c.getAttribute('t'), value: c.textContent, style: c.getAttribute('s') }));
            const widths = Array.from(reread.querySelectorAll('col')).map(c => Number(c.getAttribute('width')));
            records.length = 0;
            config.customizeData(exported);
            config.customize({ xl: { worksheets: { 'sheet1.xml': doc } } });
            return { cells, widths, invalid: reread.querySelectorAll('parsererror,f').length, emptyRows: doc.querySelectorAll('row').length, emptyDimension: doc.querySelector('dimension').getAttribute('ref'), sheetName: config.sheetName, title: config.title };
        });
        assert.deepEqual(result.cells.slice(0, 5).map(c => c.value), ['ID', 'Name', 'Video ID', 'Image', 'Embed']);
        assert.equal(result.cells.length, 15);
        assert.equal(result.cells[5].type, 'n');
        assert.equal(result.cells[6].value, '=1+1 & <Title>');
        assert.equal(result.cells[6].type, 'inlineStr');
        assert.equal(result.cells[7].value, '000123');
        assert.equal(result.cells[8].value, 'https://images.example.com/a.jpg?x=1&y=2');
        assert.equal(result.cells[13].value, '');
        assert.deepEqual(result.widths, [6, 54, 14.85546875, 10.85546875, 13.42578125]);
        assert.equal(result.invalid, 0);
        assert.equal(result.emptyRows, 1);
        assert.equal(result.emptyDimension, 'A1:E1');
        assert.equal(result.sheetName, 'Sheet1');
        assert.equal(result.title, null);
        console.log('PASS: Excel template headers, widths, literal values, image/embed URLs and empty results.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
