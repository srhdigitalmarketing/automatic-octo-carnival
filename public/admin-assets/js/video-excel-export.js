(function (root) {
    'use strict';
    // The Excel template has one sheet and exactly these five columns.
    function videoExcelExport(getRows) {
        var headers = ['ID', 'Name', 'Video ID', 'Image', 'Embed'];
        var widths = [6, 54, 14.85546875, 10.85546875, 13.42578125];
        var rows = [];
        return {
            extend: 'excelHtml5',
            className: 'btn-sm',
            filename: 'All Videos',
            sheetName: 'Sheet1',
            title: null,
            messageTop: null,
            messageBottom: null,
            footer: false,
            exportOptions: { columns: [0, 1, 2, 4, 8], modifier: { page: 'current' } },
            customizeData: function (data) {
                rows = getRows().map(function (row) {
                    var record = row.DT_RowData.excel;
                    return [record.id, record.name, record.video_id, record.image, record.embed];
                });
                data.header = headers.slice();
                data.body = rows;
                // Buttons 2.2 reads footer[col].length when footer is truthy.
                // No footer must be null, not an empty (truthy) array.
                data.footer = null;
            },
            customize: function (xlsx) {
                var sheet = xlsx.xl.worksheets['sheet1.xml'];
                var ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                function element(name, attrs) {
                    var node = sheet.createElementNS(ns, name);
                    Object.keys(attrs || {}).forEach(function (key) { node.setAttribute(key, attrs[key]); });
                    return node;
                }
                var data = sheet.getElementsByTagName('sheetData')[0];
                while (data.firstChild) { data.removeChild(data.firstChild); }
                [headers].concat(rows).forEach(function (values, index) {
                    var row = element('row', { r: index + 1 });
                    values.forEach(function (value, column) {
                        var cell = element('c', { r: String.fromCharCode(65 + column) + (index + 1), s: index === 0 ? '2' : '0' });
                        var text = value == null ? '' : String(value);
                        if (index > 0 && column === 0 && /^\d+$/.test(text) && text.length < 16) {
                            cell.setAttribute('t', 'n');
                            var number = element('v');
                            number.textContent = text;
                            cell.appendChild(number);
                        } else {
                            // Preserve identifiers, URLs and formula-like titles as literal text.
                            cell.setAttribute('t', 'inlineStr');
                            var inline = element('is');
                            var content = element('t');
                            content.setAttribute('xml:space', 'preserve');
                            content.textContent = text.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\uFFFE\uFFFF]/g, '');
                            inline.appendChild(content);
                            cell.appendChild(inline);
                        }
                        row.appendChild(cell);
                    });
                    data.appendChild(row);
                });
                var cols = sheet.getElementsByTagName('cols')[0];
                while (cols.firstChild) { cols.removeChild(cols.firstChild); }
                widths.forEach(function (width, index) {
                    cols.appendChild(element('col', { min: index + 1, max: index + 1, width: width, customWidth: '1' }));
                });
                var dimension = sheet.getElementsByTagName('dimension')[0];
                if (dimension) { dimension.setAttribute('ref', 'A1:E' + (rows.length + 1)); }
            }
        };
    }
    if (typeof module !== 'undefined' && module.exports) { module.exports = videoExcelExport; }
    else { root.videoExcelExport = videoExcelExport; }
}(typeof window !== 'undefined' ? window : this));
