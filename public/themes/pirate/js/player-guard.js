/* UI deterrent only. Browser tools and cross-origin frames remain user-controlled. */
(function () {
    'use strict';
    if (!document.getElementById('embed-player')) return;

    document.addEventListener('contextmenu', function (event) {
        if (event.target instanceof Element && event.target.closest('#embed-player')) {
            event.preventDefault();
        }
    });

    document.addEventListener('keydown', function (event) {
        var key = String(event.key || '').toLowerCase();
        var inspectShortcut = (event.ctrlKey && event.shiftKey || event.metaKey && event.altKey)
            && ['i', 'j', 'c'].indexOf(key) !== -1;
        var sourceShortcut = (event.ctrlKey || event.metaKey) && key === 'u';
        if (key === 'f12' || inspectShortcut || sourceShortcut) {
            event.preventDefault();
        }
    }, true);
})();
