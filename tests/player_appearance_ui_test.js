const {chromium, webkit, devices} = require('playwright');
const {execFileSync} = require('node:child_process');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const php = process.env.PHP_BINARY || 'php';
function render(mode, color='') {
    return execFileSync(php, [path.join(__dirname, 'fixtures/player_appearance_render.php'), mode, color], {encoding:'utf8'});
}
const poster = '<svg xmlns="http://www.w3.org/2000/svg" width="960" height="540"><rect width="960" height="540" fill="#25495e"/><path d="M0 0H960V540H0Z" fill="none" stroke="#7de0cd" stroke-width="28"/><circle cx="735" cy="155" r="55" fill="#f1be65"/><path d="M0 500L265 145L500 430L675 280L960 540H0Z" fill="#19374a"/><text x="40" y="490" fill="white" font-size="32">THUMBNAIL PREVIEW</text></svg>';
function shot(page, name) {
    if (!process.env.PLAYER_SCREENSHOT_DIR) return;
    fs.mkdirSync(process.env.PLAYER_SCREENSHOT_DIR, {recursive:true});
    return page.screenshot({path:path.join(process.env.PLAYER_SCREENSHOT_DIR,name+'.png')});
}
(async () => {
    const html = render('embed');
    assert.ok(render('embed','missing').includes('--player-loading-color: #d28a15;'));
    const invalid = render('embed','invalid');
    assert.ok(invalid.includes('--player-loading-color: #d28a15;'));
    assert.equal(invalid.includes('<script>alert(1)</script>'),false,'Invalid saved CSS color cannot inject markup');
    for (const engine of ['chromium','webkit']) {
        const browser = await (engine==='webkit' ? webkit.launch({headless:true}) : chromium.launch({channel:'msedge',headless:true}));
        try {
            const context = await browser.newContext({...devices['iPhone 13'], reducedMotion:'no-preference'});
            const page = await context.newPage();
            const errors=[], requests=[];
            let posterMode='ok', releaseFrame;
            const frameGate = new Promise(resolve=>{releaseFrame=resolve;});
            page.on('pageerror',e=>errors.push(e.message));
            await page.route('**/*', async route => {
                const url = new URL(route.request().url()); requests.push(url.pathname);
                if (url.hostname==='player.example') {
                    if (url.pathname==='/play/test') return route.fulfill({contentType:'text/html',body:html});
                    if (url.pathname==='/poster.svg' || url.pathname==='/poster-fallback.svg') {
                        const fail=posterMode==='both' || (posterMode==='primary' && url.pathname==='/poster.svg');
                        return route.fulfill({status:fail?404:200,contentType:fail?'text/plain':'image/svg+xml',body:fail?'Missing':poster});
                    }
                    if (url.pathname==='/ajax/get_stream_link') {
                        await new Promise(resolve=>setTimeout(resolve,180));
                        return route.fulfill({contentType:'application/json',body:JSON.stringify({success:true,data:{id:'one',token:'token',link:'https://embed.example/video',host:'embed.example',report_player_failure:true}})});
                    }
                    if (url.pathname.startsWith('/themes/pirate/')) {
                        const file=path.resolve(root,'public','.'+url.pathname);
                        assert.ok(file.startsWith(path.join(root,'public')+path.sep));
                        if (fs.existsSync(file)) return route.fulfill({path:file});
                    }
                }
                if (url.hostname==='embed.example') {
                    await frameGate;
                    return route.fulfill({contentType:'text/html',body:'<meta name="viewport" content="width=device-width,initial-scale=1"><button onclick="this.textContent=\'Playing\'">Play provider</button>'});
                }
                return route.abort(); // Font-CDN failure must not hide the default Play icon.
            });
            await page.goto('https://player.example/play/test');
            await page.waitForFunction(()=>typeof Player!=='undefined' && Player.isInit).catch(e=>{console.error({errors,requests});throw e;});
            await page.locator('.player-poster').evaluate(img=>img.decode());
            assert.equal(await page.locator('.player-poster').getAttribute('src'),'https://player.example/poster.svg?title="sample"&size=large');
            assert.equal(await page.locator('.cover').evaluate(el=>getComputedStyle(el).filter),'none');
            assert.equal(await page.locator('.player-poster').evaluate(el=>getComputedStyle(el).objectFit),'contain');
            const initial=await page.locator('.main-content').boundingBox();
            assert.equal(Math.round(initial.width),390); assert.equal(Math.round(initial.height),devices['iPhone 13'].viewport.height);
            assert.equal(await page.locator('.play-btn').evaluate(el=>el.tagName),'BUTTON');
            assert.notEqual(await page.locator('.fa-play').evaluate(el=>getComputedStyle(el,'::before').borderLeftWidth),'0px');
            await shot(page,engine+'-iphone-poster');
            await page.locator('.play-btn').tap();
            await page.waitForFunction(()=>Player.framePending);
            assert.equal(await page.locator('#ve-iframe').getAttribute('src'),'https://embed.example/video');
            assert.equal(await page.locator('.frame').isVisible(),true,'Iframe stays laid out while navigating on Safari');
            assert.equal(await page.locator('.loader').isVisible(),true);
            assert.equal(await page.locator('#embed-player').getAttribute('aria-busy'),'true');
            assert.equal(await page.locator('.player-spinner').evaluate(el=>getComputedStyle(el).borderTopColor),'rgb(33, 196, 181)');
            const transforms=await page.locator('.player-spinner').evaluate(async el=>{
                const before=getComputedStyle(el).transform;
                await new Promise(resolve=>setTimeout(resolve,100));
                return [before,getComputedStyle(el).transform];
            });
            assert.notEqual(transforms[0],transforms[1],'Loading ring rotates');
            assert.equal(await page.locator('.loader .ve-text').count(),0,'Visible loading text removed');
            assert.equal(await page.locator('.player-sr-only').evaluate(el=>getComputedStyle(el).clip),'rect(0px, 0px, 0px, 0px)','Status text remains available only to assistive technology');
            await shot(page,engine+'-iphone-loading');
            await page.emulateMedia({reducedMotion:'reduce'});
            assert.equal(await page.locator('.player-spinner').evaluate(el=>getComputedStyle(el).animationName),'none');
            await page.emulateMedia({reducedMotion:'no-preference'});
            const lifecycle=await page.evaluate(()=>{
                let timeoutCallback;
                const original=window.setTimeout;
                window.setTimeout=(fn,ms)=>{timeoutCallback=fn;return 987654;};
                Player.scheduleFrameTimeout();
                Object.defineProperty(document,'hidden',{configurable:true,value:true});
                document.dispatchEvent(new Event('visibilitychange'));
                timeoutCallback();
                $('#ve-iframe').trigger('error');
                const kept=Player.framePending && Player.failedHosts.length===0;
                window.dispatchEvent(new Event('pagehide'));
                Object.defineProperty(document,'hidden',{configurable:true,value:false});
                timeoutCallback=null;
                window.dispatchEvent(new Event('pageshow'));
                const rearmed=typeof timeoutCallback==='function';
                delete document.hidden;
                window.setTimeout=original;
                Player.scheduleFrameTimeout();
                return {kept,rearmed};
            });
            assert.deepEqual(lifecycle,{kept:true,rearmed:true},'Background/lock does not fail a host; resume restores deadline');
            assert.equal(requests.includes('/ajax/report_stream_failure'),false);
            releaseFrame();
            await page.waitForFunction(()=>!Player.framePending);
            assert.equal(await page.locator('.loader').isVisible(),false);
            assert.equal(await page.locator('#embed-player').getAttribute('aria-busy'),'false');
            await page.frameLocator('#ve-iframe').getByRole('button',{name:'Play provider'}).tap();
            assert.equal(await page.frameLocator('#ve-iframe').getByRole('button').innerText(),'Playing','Provider remains interactive');
            await page.setViewportSize({width:844,height:390});
            const rotated=await page.locator('.main-content').boundingBox();
            assert.equal(Math.round(rotated.height),390); assert.equal(Math.round(rotated.width),844);
            await page.goto('https://player.example/play/test');
            await page.waitForFunction(()=>Player.isInit);
            await shot(page,engine+'-iphone-landscape');
            posterMode='primary';
            await page.goto('https://player.example/play/test');
            await page.waitForFunction(()=>document.querySelector('.player-poster').src.endsWith('poster-fallback.svg') && document.querySelector('.player-poster').naturalWidth>0);
            posterMode='both';
            await page.goto('https://player.example/play/test');
            await page.waitForFunction(()=>document.querySelector('.player-poster').style.display==='none');
            assert.equal(await page.locator('.play-btn').isVisible(),true,'Missing thumbnails do not prevent playback');
            assert.deepEqual(errors,[]);
            console.log('PASS '+engine+': actual view, iPhone tap, poster containment/fallback, spinner/color/motion, visible iframe loading, background/resume and landscape.');
            await context.close();
            const settings=await browser.newPage({viewport:{width:1100,height:900}});
            await settings.setContent(render('settings'));
            await settings.addStyleTag({path:path.join(root,'public/admin-assets/css/admin-polish.css')});
            await settings.locator('#player-loading-color').evaluate(el=>{el.value='#bb44ee';el.dispatchEvent(new Event('input',{bubbles:true}));});
            assert.equal(await settings.locator('.player-preview__spinner').evaluate(el=>getComputedStyle(el).borderTopColor),'rgb(187, 68, 238)');
            assert.equal(await settings.locator('[data-for="player-loading-color"]').innerText(),'#bb44ee');
            await shot(settings,engine+'-settings-preview');
        } finally { await browser.close(); }
    }
})().catch(e=>{console.error(e);process.exitCode=1;});
