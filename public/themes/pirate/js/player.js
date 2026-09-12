
"use strict";

const GCaptcha = {
    token: null,
    isEnabled: function (){
        return typeof grecaptcha !== 'undefined';
    },
    reload: function () {
        grecaptcha.reset();
        grecaptcha.execute();
    },
    getToken: function (){
        return this.token;
    },
    setToken: function ( token ){
        this.token = token;
    }
}

const Player = {

    id: null,
    name: 'VIP Embed Player',
    version: '2.0',
    author: 'John Antonio',
    isInit: false,
    isPlayed: false,
    linkToken: null,
    activeLinkId: null,
    failedHosts: [],
    frameLoadTimeout: null,
    frameLoadTimeoutMs: 30000,
    reportFrameFailure: true,
    isResolving: false,
    framePending: false,
    frameGeneration: 0,
    node: null,
    servers: {

        activeId: null,
        node: null,

        init: function (){
            let self = this;

            //init servers node
            self.node = $('#servers');

        },
        update: function ( server ){
            let self = this;

            let id = server.attr('data-id');
            self.activeId = id;
        },
        selectResolved: function (id, host) {
            let self = this;
            self.activeId = id;
        },
        get: function (){
            let self = this;

            if(self.activeId === null){
                self.activeId = self.node.attr('data-initial-id') || self.node.find('.server').first().attr('data-id');
            }
            return self.activeId;
        }
    },
    init: function () {
        let self = this;

        if( self.isInit ){
            return;
        }

        //set author credits
        self.setAuthorCredit();

        //set player node
        self.node = $("#embed-player");

        //init servers
        self.servers.init();

        //set player id
        self.setPlayerId();

        self.isInit = true;
    },
    play: async function (isVerified = false, server = null) {
        const self = this;
        if (self.isResolving) return;
        // A manual retry starts a new local attempt; automatic rotation retains exclusions.
        if (!isVerified && server === null) {
            self.failedHosts = [];
            self.servers.activeId = null;
        }
        self.isResolving = true;
        self.framePending = false;
        self.frameGeneration++;
        window.clearTimeout(self.frameLoadTimeout);
        self.loading();
        if (server !== null) self.servers.update($(server));
        if (GCaptcha.isEnabled() && !isVerified) {
            self.isResolving = false;
            try { GCaptcha.reload(); }
            catch (error) { self.errorOccurred('Verifikasi belum siap. Periksa koneksi lalu coba lagi.'); }
            return;
        }
        try {
            const data = await self.getLink();
            self.linkToken = data.token;
            self.activeLinkId = data.id;
            self.reportFrameFailure = data.report_player_failure !== false;
            const timeout = Number(data.frame_load_timeout_ms);
            self.frameLoadTimeoutMs = Number.isFinite(timeout) ? Math.max(15000, Math.min(60000, timeout)) : 30000;
            const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            if (connection && (['slow-2g','2g','3g'].includes(connection.effectiveType) || connection.rtt >= 400)) {
                self.frameLoadTimeoutMs = Math.max(self.frameLoadTimeoutMs, 60000);
            }
            self.servers.selectResolved(data.id, data.host);
            self.isPlayed = true;
            self.loadFrame(data.link);
        } catch (error) {
            // A failure to reach our endpoint says nothing about the remote video file.
            self.errorOccurred(error.message || 'Player tidak dapat dimuat. Coba lagi.');
        } finally {
            self.isResolving = false;
        }
    },
    apiUrl: function (action) {
        // Keep app AJAX on the page origin, even with an old base URL or CDN-hosted script.
        let base = '/';
        try { base = new URL(typeof BASE_URL === 'string' ? BASE_URL : '/', window.location.href).pathname; }
        catch (error) { /* The root endpoint remains a same-origin fallback. */ }
        return '/' + base.replace(/^\/+|\/+$/g, '') + (base.replace(/^\/+|\/+$/g, '') ? '/' : '') + 'ajax/' + action;
    },
    getLink: function () {
        const self = this;
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: self.apiUrl('get_stream_link'),
                type: 'GET',
                headers: {'X-Requested-With':'XMLHttpRequest'},
                data: {id:self.servers.get(), movie:self.id, is_init:self.isPlayed,
                    captcha:GCaptcha.getToken(), exclude:self.failedHosts},
                dataType: 'JSON',
                timeout: 30000,
                success: function (result) {
                    if (result && result.success && result.data && result.data.link) resolve(result.data);
                    else reject(new Error(result && result.error ? result.error : 'Stream belum tersedia. Coba lagi.'));
                },
                error: function (xhr, status) {
                    reject(new Error(status === 'timeout' ? 'Koneksi terlalu lama. Coba muat player lagi.'
                        : 'Koneksi player gagal. Periksa jaringan lalu coba lagi.'));
                }
            });
        });
    },
    loading: function (){
        let self = this;
        self.node.find('.cover, .play-btn, .frame, .error').hide();
        self.node.find('.loader').css('display', 'flex');
    },
    loaded: function () {
        this.framePending = false;
        window.clearTimeout(this.frameLoadTimeout);
        this.node.find('.loader').stop(true, true).hide();
        this.node.find('.frame').stop(true, true).show();
    },
    loadFrame: function (link) {
        const self = this;
        window.clearTimeout(self.frameLoadTimeout);
        const generation = ++self.frameGeneration;
        const linkId = self.activeLinkId;
        const previous = self.node.find('iframe');
        const frame = previous.clone(false).removeAttr('src');
        // A new node prevents a delayed load from an old host cancelling this host's timeout.
        previous.replaceWith(frame);
        self.framePending = true;
        frame.on('load', function () {
            if (generation === self.frameGeneration && self.framePending && linkId === self.activeLinkId) self.loaded();
        });
        frame.on('error', function () {
            if (generation === self.frameGeneration && self.framePending && linkId === self.activeLinkId) self.handleFrameFailure();
        });
        frame.prop('src', link);
        self.frameLoadTimeout = window.setTimeout(function () {
            if (generation === self.frameGeneration && self.framePending && linkId === self.activeLinkId) self.handleFrameFailure();
        }, self.frameLoadTimeoutMs);
    },
    handleFrameFailure: function () {
        let self = this;
        let failedId = self.activeLinkId;
        self.framePending = false;
        window.clearTimeout(self.frameLoadTimeout);

        if (failedId === null || self.failedHosts.indexOf(failedId) !== -1) {
            self.errorOccurred('Unable to load a streaming host. Please try again later.');
            return;
        }

        self.failedHosts.push(failedId);
        if (self.reportFrameFailure) $.ajax({
            url: self.apiUrl('report_stream_failure'),
            timeout: 10000,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: { id: failedId, token: self.linkToken },
            dataType: 'JSON'
        });

        self.activeLinkId = null;
        self.play(true);
    },
    setPlayerId: function () {
        let self = this;

        if(self.id === null) {
            self.id = self.node.attr('data-movie-id');
        }

    },
    setAuthorCredit: function (){
        console.log(
            "%c! " + this.name,
            "color:#1b59a3;font-family:system-ui;font-size:1rem;font-weight:bold"
        );
        console.log('Version - ' + this.version);
        console.log('Created by - John Antonio');
    },
    errorOccurred: function ( error ) {
        let self = this;
        window.clearTimeout(self.frameLoadTimeout);

        self.framePending = false;
        self.frameGeneration++;
        self.node.find('.loader, .frame').stop(true, true).hide();
        self.node.find('.error .msg').text( error );
        self.node.find('.error').css('display', 'flex');
    },
    bind: function(selector, action, event = 'click'){
        $(document).on(event,selector,function(self) {
            return function (e){
                return self[action].apply(self, arguments);
            }
        }(this));
    }
}

$(document).ready(function() {

    //init player
    Player.init();

    window.set_captcha_response = function( response ){

        //set token and play
        GCaptcha.setToken( response );
        Player.play( true );

    };

})
