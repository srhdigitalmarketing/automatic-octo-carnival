const {chromium} = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
(async () => {
    const browser = await chromium.launch({channel:'msedge',headless:true});
    try {
        const page = await browser.newPage({viewport:{width:390,height:844},isMobile:true});
        const requests=[],errors=[];
        let mode='success';
        page.on('pageerror',error=>errors.push(error.message));
        await page.route('**/*',async route => {
            const url=new URL(route.request().url());requests.push(url);
            if (url.hostname==='player.example' && url.pathname==='/sub/play/demo') return route.fulfill({contentType:'text/html',body:
                '<div id="embed-player" data-movie-id="movie"><div id="servers" data-initial-id="one"></div><div class="cover"></div><div class="play-btn"></div><div class="loader">Loading</div><div class="frame"><iframe allow="autoplay; fullscreen; encrypted-media; picture-in-picture"></iframe></div><div class="error"><span class="msg"></span><button onclick="Player.play()">Retry</button></div></div>'});
            if (url.hostname==='player.example' && url.pathname==='/sub/index.php/ajax/get_stream_link') {
                assert.equal(route.request().headers()['x-requested-with'],'XMLHttpRequest');
                await new Promise(resolve=>setTimeout(resolve,150));
                if (mode==='offline') return route.abort('failed');
                if (mode==='html') return route.fulfill({contentType:'text/html',body:'Gateway unavailable'});
                const second=url.searchParams.getAll('exclude[]').includes('one');
                return route.fulfill({contentType:'application/json',body:JSON.stringify({success:true,data:{
                    id:second?'two':'one',token:'token',link:'https://embed.sdh.example/embed/'+(second?'two':'one'),
                    host:'embed.sdh.example',frame_load_timeout_ms:30000,report_player_failure:false}})});
            }
            if (url.hostname==='embed.sdh.example') return route.fulfill({contentType:'text/html',body:'<p>Embedded provider</p>'});
            return route.abort('failed');
        });
        await page.goto('https://player.example/sub/play/demo');
        await page.addScriptTag({path:path.join(__dirname,'../public/themes/pirate/js/vendor/jquery-3.6.0.min.js')});
        await page.addScriptTag({content:"const BASE_URL='http://stale-config.invalid:22721/sub/index.php/';"});
        await page.addScriptTag({path:path.join(__dirname,'../public/themes/pirate/js/player.js')});
        const asyncResult=await page.evaluate(async()=>{
            Player.init();
            Object.defineProperty(navigator,'connection',{configurable:true,value:{effectiveType:'3g',rtt:500}});
            const pending=Player.play();
            Player.play(); // double tap must not start a second request or double-count a view
            await new Promise(resolve=>setTimeout(resolve,25));
            const responsive=Player.isResolving && $('#embed-player .loader').css('display')!=='none';
            await pending;
            return {responsive,timeout:Player.frameLoadTimeoutMs,url:Player.apiUrl('get_stream_link')};
        });
        assert.deepEqual(asyncResult,{responsive:true,timeout:60000,url:'/sub/index.php/ajax/get_stream_link'});
        await page.waitForFunction(()=>!Player.framePending);
        assert.equal(await page.locator('#embed-player .frame').isVisible(),true);
        const apiRequests=()=>requests.filter(url=>url.pathname.endsWith('/ajax/get_stream_link'));
        assert.equal(apiRequests().length,1,'Double tap does not issue duplicate request');
        assert.equal(apiRequests()[0].searchParams.get('is_init'),'false');
        assert.equal(requests.some(url=>url.hostname==='stale-config.invalid'),false,'No old-host or mixed-content/CORS request');
        await page.evaluate(()=>{Player.handleFrameFailure();});
        await page.waitForFunction(()=>Player.activeLinkId==='two' && !Player.isResolving);
        assert.equal(requests.some(url=>url.pathname.endsWith('/report_stream_failure')),false,'Excluded provider never sends a failure report');
        assert.deepEqual(await page.evaluate(()=>Player.failedHosts),['one'],'Failure exclusion remains browser-local');
        await page.waitForFunction(()=>!Player.framePending);
        await page.evaluate(()=>{
            const before=Player.frameGeneration;
            const old=$('#embed-player iframe');
            Player.loadFrame('https://embed.sdh.example/embed/fresh');
            old.trigger('load');
            window.oldLoadIgnored=Player.framePending && Player.frameGeneration>before;
        });
        assert.equal(await page.evaluate(()=>window.oldLoadIgnored),true,'Old iframe event cannot finish the new load');
        for (const failure of ['offline','html']) {
            mode=failure;
            await page.evaluate(()=>Player.play());
            assert.equal(await page.locator('#embed-player .error').isVisible(),true);
            assert.equal(await page.locator('#embed-player .loader').isVisible(),false);
            assert.deepEqual(await page.evaluate(()=>Player.failedHosts),[],'API/network failure does not mark any stream failed');
            assert.equal(await page.evaluate(()=>Player.isResolving),false);
        }
        mode='success';
        await page.locator('#embed-player .error button').click();
        await page.waitForFunction(()=>Player.activeLinkId==='one' && !Player.isResolving && !Player.framePending);
        assert.equal(await page.locator('#embed-player .error').isVisible(),false,'Retry recovers player');
        assert.equal(requests.some(url=>url.pathname.endsWith('/report_stream_failure')),false);
        assert.deepEqual(errors,[]);
        const view=fs.readFileSync(path.join(__dirname,'../app/Views/themes/pirate/embed.php'),'utf8');
        assert.equal(view.includes('https://code.jquery.com/jquery-3.6.0.min.js'),false,'Core dependency no longer requires third-party CDN');
        assert.equal(view.includes('js/vendor/jquery-3.6.0.min.js'),true);
        assert.equal(view.includes('js/vendor/bootstrap-5.1.3.bundle.min.js'),true);
        console.log('PASS: asynchronous slow-mobile player, same-origin AJAX with subdirectory, no double requests, local-only failure rotation, stale frame events, network failure and retry.');
    } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
