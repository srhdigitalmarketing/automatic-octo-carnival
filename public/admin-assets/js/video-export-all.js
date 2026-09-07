(function (root, $) {
    'use strict';
    var busy = false;
    root.allVideoExport = function (config) {
        var exportType = config.extend;
        config.action = async function (event, dt, button, options) {
            if (busy) { return; }
            busy = true;
            var self = this;
            var originalText = self.text();
            var table = null;
            var holder = null;
            self.processing(true);
            try {
                var params = $.extend(true, {}, dt.ajax.params());
                params.export = '1';
                params.length = 1000;
                params.start = 0;
                var rows = [];
                var expected = null;
                do {
                    self.text('Loading ' + rows.length + (expected === null ? '' : ' / ' + expected));
                    var response = await $.ajax({ url: dt.ajax.url(), data: params, dataType: 'json', timeout: 60000 });
                    if (! response || ! Array.isArray(response.data) || ! Number.isInteger(Number(response.recordsFiltered))) {
                        throw new Error('Invalid export response. Please refresh the page and try again.');
                    }
                    var total = Number(response.recordsFiltered);
                    if (expected !== null && total !== expected) {
                        throw new Error('The video list changed during export. Please try again.');
                    }
                    expected = total;
                    if (! response.data.length && rows.length < expected) {
                        throw new Error('The export is incomplete. Please try again.');
                    }
                    rows = rows.concat(response.data);
                    params.start = rows.length;
                } while (rows.length < expected);
                if (rows.length !== expected || new Set(rows.map(function (row) { return row[0]; })).size !== expected) {
                    throw new Error('The video list changed during export. Please try again.');
                }
                self.text('Creating export…');
                // Use a separate client-side table so the visible page and filters stay intact.
                holder = $('<table>').css('display', 'none').append($(dt.table().header()).clone()).appendTo(document.body);
                table = holder.DataTable({
                    data: rows, dom: 't', deferRender: true, paging: true, pageLength: 10,
                    ordering: false, searching: false,
                    columns: dt.columns().indexes().toArray().map(function (index) { return { visible: dt.column(index).visible() }; })
                });
                var exportConfig = $.extend(true, {}, options);
                exportConfig.exportOptions = $.extend(true, {}, options.exportOptions, { modifier: { page: 'all', search: 'none', selected: null } });
                if (exportType === 'excelHtml5') {
                    var excel = root.videoExcelExport(function () { return rows; });
                    exportConfig.customizeData = excel.customizeData;
                    exportConfig.customize = excel.customize;
                }
                var nativeType = exportType === 'csv' ? 'csvHtml5' : exportType;
                $.fn.dataTable.ext.buttons[nativeType].action.call(self, event, table, button, exportConfig);
            } catch (error) {
                root.alert(error.message || 'Unable to export all videos. Please try again.');
            } finally {
                if (table) { table.destroy(); }
                if (holder) { holder.remove(); }
                self.processing(false);
                self.text(originalText);
                busy = false;
            }
        };
        return config;
    };
}(window, jQuery));
