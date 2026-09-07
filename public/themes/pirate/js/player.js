
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
    play: function (isVerified = false, server = null) {
        let self = this;

        //player loading
        self.loading();


        //attempt to get link
        if(server !== null){
            self.servers.update( $(server) );
        }

        //check captcha
        if(GCaptcha.isEnabled()){
            if(! isVerified){
                GCaptcha.reload();
                return;
            }
        }

        //attempt to get link
        let link = self.getLink();

        //player loaded link in first time
        if(! self.isPlayed){
            self.isPlayed = true;
            if(window.StreamPlayerAnalytics && typeof window.StreamPlayerAnalytics.recordPlay === 'function'){
                window.StreamPlayerAnalytics.recordPlay();
            }
        }

        //check link
        if(link !== null){

            /*
                set link to frame
                ( we does not close loader in here, it will
                will close automatically after content loaded )
             */
            self.loadFrame( link );

        }else{

            //stop loader animation
            self.loaded();
        }


    },
    getLink: function (){
        let self = this;

        let link = null;

        $.ajax({

            url : BASE_URL + 'ajax/get_stream_link',
            type: "GET",
            headers: { 'X-Requested-With': 'XMLHttpRequest'},
            data: {
                'id' : self.servers.get(),
                'movie' : self.id,
                'is_init' : self.isPlayed,
                'captcha' : GCaptcha.getToken(),
                'exclude' : self.failedHosts
            },
            dataType: "JSON",
            async: false,

            success: function(data)
            {

                if(data.success) {

                    link = data.data.link;
                    self.linkToken = data.data.token;
                    self.activeLinkId = data.data.id;
                    self.servers.selectResolved(data.data.id, data.data.host);

                }else{
                    if('error' in data){
                        self.errorOccurred(data.error);
                    }
                }

            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                setTimeout(function (){
                    self.errorOccurred(errorThrown);
                }, 1000);

            }
        });

        return link;
    },
    loading: function (){
        let self = this;
        self.node.find('.cover, .play-btn, .frame, .error').hide();
        self.node.find('.loader').css('display', 'flex');
    },
    loaded: function ( ) {
        let self = this;
        window.clearTimeout(self.frameLoadTimeout);

        setTimeout(function (){
            //close loader
            self.node.find('.loader').fadeOut(1500);
            self.node.find('.frame').fadeIn(100);
        }, 1500);

    },
    loadFrame: function ( link ) {
        let self = this;
        window.clearTimeout(self.frameLoadTimeout);
        self.node.find('iframe').prop('src', link);
        self.frameLoadTimeout = window.setTimeout(function () {
            self.handleFrameFailure();
        }, 15000);
    },
    handleFrameFailure: function () {
        let self = this;
        let failedId = self.activeLinkId;

        if (failedId === null || self.failedHosts.indexOf(failedId) !== -1) {
            self.errorOccurred('Unable to load a streaming host. Please try again later.');
            return;
        }

        self.failedHosts.push(failedId);
        $.ajax({
            url: BASE_URL + 'ajax/report_stream_failure',
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
        console.clear();
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

    // ========================== waiting till once iframe is done loading ==========================
    $('#embed-player iframe').on('load', function(){

        if($(this).attr('src') !== undefined){
            window.clearTimeout(Player.frameLoadTimeout);
            Player.loaded();
        }

    });

})
