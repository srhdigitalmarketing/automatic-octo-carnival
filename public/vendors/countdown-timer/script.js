/**
 * Author Nat Lee (c) 2021
 * Free to use
 * https://github.com/lee-ratinan/countdownTimer
 */
(function ($) {
    $.fn.countdownTimer = function(options) {
        let settings = $.extend({
            seconds: 5,
            loop: true,
            callback: null
        }, options);
        return this.each(function() {
            calculateTime($(this), settings.seconds, settings.seconds, settings.loop, settings.callback);
        });
    };
    function calculateTime(element, seconds_start, seconds_now, loop, callback) {

        element.html(seconds_now);
        seconds_now--;
        // LOOP
        if (seconds_now === -1 && loop) {
            seconds_now = seconds_start;
            if (null !== callback) {
                callback();
            }
        }
        if (seconds_now > -1) {
            setTimeout(function () {
                calculateTime(element, seconds_start, seconds_now, loop, callback);
            }, 1000);
        } else if (null !== callback) {
            callback();
        }
    }
}(jQuery));