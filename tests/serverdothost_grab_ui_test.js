const {chromium} = require('playwright');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const php = process.env.PHP_BINARY || 'C:/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe';
function view(empty = false) {
    return execFileSync(php, ['-r', `function esc($s,$context='html'){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
        function admin_url($s){return 'https://example.test/admin'.$s;}
        $serverDotHostApis=${empty ? '[]' : "[(object)['id'=>7,'name'=>'ServerDotHost <Account>']]"};
        include 'app/Views/admin/settings/general/form_x_panels/serverdothost_grab.php';`], {encoding:'utf8'});
}
(async () => {
    const browser = await chromium.launch({channel:'msedge',headless:true});
    try {
        const page = await browser.newPage();
        const errors = [];page.on('pageerror', error => errors.push(error.message));
        async function mount(empty = false) {
            await page.goto('about:blank');
            await page.setContent('<meta name="viewport" content="width=device-width,initial-scale=1"><div style="padding:15px">' + view(empty) + '</div>');
            for (const path of ['public/admin-assets/vendors/bootstrap5/css/bootstrap.min.css', 'public/admin-assets/css/custom.min.css',
                'public/admin-assets/css/bootstrap5-compat.css', 'public/admin-assets/css/admin-polish.css']) await page.addStyleTag({path});
            await page.addScriptTag({path:'public/admin-assets/js/serverdothost-grab.js'});
        }
        await mount();
        await page.evaluate(() => {
            window.requests = [];
            let step = 0;
            window.fetch = async (url,options) => {
                const action = options.body.get('action');window.requests.push(action);
                const results = [
                    {processed:0,success:0,skipped:0,done:false,title:'Finding',message:'Page 2'},
                    {processed:1,success:1,skipped:0,done:false,state:'success',title:'<img src=x onerror=alert(1)>',message:'Added'},
                    {processed:2,success:1,skipped:1,done:true,state:'skipped',title:'Not uploaded',message:'Skipped'}
                ];
                return {ok:true,json:async () => action === 'start' ? {total:2,token:'test-job'} : results[step++]};
            };
        });
        await page.locator('#serverdothost-grab-start').click();
        await page.waitForFunction(() => document.querySelector('#serverdothost-grab-status').textContent.startsWith('Selesai.'));
        assert.match(await page.locator('#serverdothost-grab-status').textContent(),/Diproses 2\/2.*Ditambahkan 1.*Dilewati 1/);
        assert.equal(await page.locator('#serverdothost-grab-log li').count(),2);
        assert.equal(await page.locator('#serverdothost-grab-log img').count(),0,'Remote title is text, not HTML');
        assert.equal(await page.locator('#serverdothost-grab-progress').getAttribute('aria-valuenow'),'100');
        assert.equal(await page.locator('#serverdothost-grab-start').isEnabled(),true);
        assert.deepEqual(await page.evaluate(() => window.requests),['start','next','next','next']);
        for (const width of [390,1280]) {
            await page.setViewportSize({width,height:900});
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth),false,'No horizontal overflow at '+width);
            assert.equal(await page.locator('#serverdothost-grab-start').isVisible(),true);
            await page.locator('#serverdothost-grab-start').hover();
            assert.equal(await page.locator('#serverdothost-grab-start').evaluate(el => getComputedStyle(el).color === getComputedStyle(el).backgroundColor),false,'Hover text contrast');
        }
        await page.evaluate(() => {
            window.requests = []; window.inflight = false;
            window.fetch = async (url,options) => {
                const action = options.body.get('action');window.requests.push(action);
                if (action === 'next') {window.inflight=true;await new Promise(resolve => setTimeout(resolve,300));}
                return {ok:true,json:async () => action === 'start' ? {total:10,token:'stop-job'} : {processed:0,success:0,skipped:0,done:false}};
            };
        });
        await page.locator('#serverdothost-grab-start').click();
        await page.waitForFunction(() => window.inflight);
        await page.locator('#serverdothost-grab-stop').click();
        await page.waitForFunction(() => document.querySelector('#serverdothost-grab-status').textContent.startsWith('Dihentikan.'));
        assert.deepEqual(await page.evaluate(() => window.requests),['start','next','stop'],'Stop cancels further pagination');
        for (const failure of ['rate','html','network']) {
            await page.evaluate(failure => {
                window.requests = [];
                window.fetch = async (url,options) => {
                    const action=options.body.get('action');window.requests.push(action);
                    if (action==='start') return {ok:true,json:async () => ({total:50,token:'error-job'})};
                    if (failure==='network') throw new Error('Network interrupted');
                    return {ok:false,json:async () => {if (failure==='html') throw new Error('HTML');return {error:'Batas permintaan tercapai'};}};
                };
            },failure);
            await page.locator('#serverdothost-grab-start').click();
            await page.waitForFunction(() => document.querySelector('#serverdothost-grab-status').textContent.startsWith('Proses berhenti:'));
            assert.deepEqual(await page.evaluate(() => window.requests),['start','next'],'No automatic API error retries');
            assert.equal(await page.locator('#serverdothost-grab-start').isEnabled(),true);
            assert.equal(await page.locator('#serverdothost-grab-progress').evaluate(el => el.classList.contains('progress-bar-animated')),false);
        }
        await mount(true);
        assert.equal(await page.locator('#serverdothost-grab-start').isDisabled(),true,'Unavailable API cannot start');
        assert.equal(await page.locator('#serverdothost-grab-panel a').isVisible(),true);
        assert.deepEqual(errors,[]);
        console.log('PASS: rendered admin panel, escaped titles, progress counters, stop, API errors, empty config, mobile layout and button contrast.');
    } finally {await browser.close();}
})().catch(error => {console.error(error);process.exitCode=1;});
