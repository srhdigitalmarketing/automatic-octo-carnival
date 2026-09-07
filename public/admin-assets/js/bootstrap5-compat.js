(function (window, document, $) {
    'use strict';

    if (!window.bootstrap) {
        return;
    }

    function migrateDataAttributes(root) {
        var attributes = {
            'data-toggle': 'data-bs-toggle',
            'data-dismiss': 'data-bs-dismiss',
            'data-target': 'data-bs-target',
            'data-parent': 'data-bs-parent'
        };

        Object.keys(attributes).forEach(function (oldName) {
            var newName = attributes[oldName];
            var nodes = root.querySelectorAll('[' + oldName + ']');
            Array.prototype.forEach.call(nodes, function (node) {
                if (!node.hasAttribute(newName)) {
                    node.setAttribute(newName, node.getAttribute(oldName));
                }
            });
        });
    }

    migrateDataAttributes(document);
    document.addEventListener('DOMContentLoaded', function () {
        migrateDataAttributes(document);
    });

    if (!$) {
        return;
    }

    function bridgePlugin(name) {
        return function (options) {
            return this.each(function () {
                var instance = window.bootstrap[name].getOrCreateInstance(this, typeof options === 'object' ? options : {});
                if (typeof options === 'string' && typeof instance[options] === 'function') {
                    instance[options]();
                }
            });
        };
    }

    ['Tooltip', 'Popover', 'Modal', 'Dropdown', 'Collapse', 'Tab'].forEach(function (name) {
        $.fn[name.toLowerCase()] = bridgePlugin(name);
    });
    // The legacy theme extends this property. A no-op keeps that extension safe.
    $.fn.popover.Constructor = { prototype: { leave: function () {} } };

    $(document).on('click', '[data-dismiss="alert"]', function () {
        var alert = this.closest('.alert');
        if (alert) {
            window.bootstrap.Alert.getOrCreateInstance(alert).close();
        }
    });
})(window, document, window.jQuery);
