(function (root, factory) {
  'use strict';
  if (typeof module === 'object') {
    module.exports = factory;
  } else if (typeof define === 'function' && define.amd) {
    define(factory);
  } else {
    root.MediumEditorTable = factory;
  }
}(this, function () {

  'use strict';

function extend(dest, source) {
    var prop;
    dest = dest || {};
    for (prop in source) {
        if (source.hasOwnProperty(prop) && !dest.hasOwnProperty(prop)) {
            dest[prop] = source[prop];
        }
    }
    return dest;
}

function getSelectionText(doc) {
    if (doc.getSelection) {
        return doc.getSelection().toString();
    }
    if (doc.selection && doc.selection.type !== 'Control') {
        return doc.selection.createRange().text;
    }
    return '';
}

function getSelectionStart(doc) {
    var node = doc.getSelection().anchorNode,
        startNode = (node && node.nodeType === 3 ? node.parentNode : node);

    return startNode;
}

function placeCaretAtNode(doc, node, before) {
    if (doc.getSelection !== undefined && node) {
        var range = doc.createRange(),
            selection = doc.getSelection();

        if (before) {
            range.setStartBefore(node);
        } else {
            range.setStartAfter(node);
        }

        range.collapse(true);

        selection.removeAllRanges();
        selection.addRange(range);
    }
}

function isInsideElementOfTag(node, tag) {
    if (!node) {
        return false;
    }

    var parentNode = node.parentNode,
        tagName = parentNode.tagName.toLowerCase();

    while (tagName !== 'body') {
        if (tagName === tag) {
            return true;
        }
        parentNode = parentNode.parentNode;

        if (parentNode && parentNode.tagName) {
            tagName = parentNode.tagName.toLowerCase();
        } else {
            return false;
        }
    }

    return false;
}

function getParentOf(el, tagTarget) {
    var tagName = el && el.tagName ? el.tagName.toLowerCase() : false;

    if (!tagName) {
        return false;
    }
    while (tagName && tagName !== 'body') {
        if (tagName === tagTarget) {
            return el;
        }
        el = el.parentNode;
        tagName = el && el.tagName ? el.tagName.toLowerCase() : false;
    }
}

function Grid(el, callback, rows, columns) {
    return this.init(el, callback, rows, columns);
}

Grid.prototype = {
    init: function (el, callback, rows, columns) {
        this._root = el;
        this._callback = callback;
        this.rows = rows;
        this.columns = columns;
        return this._render();
    },

    setCurrentCell: function (cell) {
        this._currentCell = cell;
    },

    markCells: function () {
        [].forEach.call(this._cellsElements, function (el) {
            var cell = {
                    column: parseInt(el.dataset.column, 10),
                    row: parseInt(el.dataset.row, 10)
                },
                active = this._currentCell &&
                         cell.row <= this._currentCell.row &&
                         cell.column <= this._currentCell.column;

            if (active === true) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        }.bind(this));
    },

    _generateCells: function () {
        this._cells = [];

        for (var i = 0; i < this.rows * this.columns; i++) {
            var column = i % this.columns,
                row = Math.floor(i / this.rows);

            this._cells.push({
                column: column,
                row: row,
                active: false
            });
        }
    },

    _html: function () {
        var html = '<div class="medium-editor-table-builder-grid clearfix">';
        html += this._cellsHTML();
        html += '</div>';
        return html;
    },

    _cellsHTML: function () {
        var html = '';
        this._generateCells();
        this._cells.map(function (cell) {
            html += '<a href="#" class="medium-editor-table-builder-cell' +
                    (cell.active === true ? ' active' : '') +
                    '" ' + 'data-row="' + cell.row +
                    '" data-column="' + cell.column + '">';
            html += '</a>';
        });
        return html;
    },

    _render: function () {
        this._root.innerHTML = this._html();
        this._cellsElements = this._root.querySelectorAll('a');
        this._bindEvents();
    },

    _bindEvents: function () {
        [].forEach.call(this._cellsElements, function (el) {
            this._onMouseEnter(el);
            this._onClick(el);
        }.bind(this));
    },

    _onMouseEnter: function (el) {
        var self = this,
            timer;

        el.addEventListener('mouseenter', function () {
            clearTimeout(timer);

            var dataset = this.dataset;

            timer = setTimeout(function () {
                self._currentCell = {
                    column: parseInt(dataset.column, 10),
                    row: parseInt(dataset.row, 10)
                };
                self.markCells();
            }, 50);
        });
    },

    _onClick: function (el) {
        var self = this;
        el.addEventListener('click', function (e) {
            e.preventDefault();
            self._callback(this.dataset.row, this.dataset.column);
        });
    }
};

function Builder(options) {
    return this.init(options);
}

Builder.prototype = {
    init: function (options) {
        this.options = options;
        this._doc = options.ownerDocument || document;
        this._root = this._doc.createElement('div');
        this._root.className = 'medium-editor-table-builder';
        this.grid = new Grid(
          this._root,
          this.options.onClick,
          this.options.rows,
          this.options.columns
        );

        this._range = null;
        this._toolbar = this._doc.createElement('div');
        this._toolbar.className = 'medium-editor-table-builder-toolbar';

        var spanRow = this._doc.createElement('span');
        spanRow.innerHTML = 'Row:';
        this._toolbar.appendChild(spanRow);
        var addRowBefore = this._doc.createElement('button');
        addRowBefore.title = 'Add row before';
        addRowBefore.innerHTML = '<i class="fa fa-long-arrow-up"></i>';
        addRowBefore.onclick = this.addRow.bind(this, true);
        this._toolbar.appendChild(addRowBefore);

        var addRowAfter = this._doc.createElement('button');
        addRowAfter.title = 'Add row after';
        addRowAfter.innerHTML = '<i class="fa fa-long-arrow-down"></i>';
        addRowAfter.onclick = this.addRow.bind(this, false);
        this._toolbar.appendChild(addRowAfter);

        var remRow = this._doc.createElement('button');
        remRow.title = 'Remove row';
        remRow.innerHTML = '<i class="fa fa-close"></i>';
        remRow.onclick = this.removeRow.bind(this);
        this._toolbar.appendChild(remRow);

        var spanCol = this._doc.createElement('span');
        spanCol.innerHTML = 'Column:';
        this._toolbar.appendChild(spanCol);
        var addColumnBefore = this._doc.createElement('button');
        addColumnBefore.title = 'Add column before';
        addColumnBefore.innerHTML = '<i class="fa fa-long-arrow-left"></i>';
        addColumnBefore.onclick = this.addColumn.bind(this, true);
        this._toolbar.appendChild(addColumnBefore);

        var addColumnAfter = this._doc.createElement('button');
        addColumnAfter.title = 'Add column after';
        addColumnAfter.innerHTML = '<i class="fa fa-long-arrow-right"></i>';
        addColumnAfter.onclick = this.addColumn.bind(this, false);
        this._toolbar.appendChild(addColumnAfter);

        var remColumn = this._doc.createElement('button');
        remColumn.title = 'Remove column';
        remColumn.innerHTML = '<i class="fa fa-close"></i>';
        remColumn.onclick = this.removeColumn.bind(this);
        this._toolbar.appendChild(remColumn);

        var remTable = this._doc.createElement('button');
        remTable.title = 'Remove table';
        remTable.innerHTML = '<i class="fa fa-trash-o"></i>';
        remTable.onclick = this.removeTable.bind(this);
        this._toolbar.appendChild(remTable);

        var grid = this._root.childNodes[0];
        this._root.insertBefore(this._toolbar, grid);
        //console.log(this._root);
    },

    getElement: function () {
        return this._root;
    },

    hide: function () {
        this._root.style.display = '';
        this.grid.setCurrentCell({ column: -1, row: -1 });
        this.grid.markCells();
    },

    show: function (left) {
        this._root.style.display = 'block';
        this._root.style.left = left + 'px';
    },

    setEditor: function (range) {
        this._range = range;
        this._doc.getElementsByClassName('medium-editor-table-builder-toolbar')[0].style.display = 'block';
    },

    setBuilder: function () {
        this._range = null;
        this._doc.getElementsByClassName('medium-editor-table-builder-toolbar')[0].style.display = 'none';
        var elements = this._doc.getElementsByClassName('medium-editor-table-builder-grid');
        for (var i = 0; i < elements.length; i++) {
            // TODO: what is 16 and what is 2?
            elements[i].style.height = (16 * this.rows + 2) + 'px';
            elements[i].style.width = (16 * this.columns + 2) + 'px';
        }
    },

    addRow: function (before, e) {
        e.preventDefault();
        e.stopPropagation();

        // codepot
        //var tbody = this._range.parentNode.parentNode,
        //    tr = this._doc.createElement('tr'),
        //    td;
        var num_cols;
        var tbody, tr, td;

        tr = this._doc.createElement('tr');

        var in_thead = this._range.parentNode.parentNode.tagName.toLowerCase() == 'thead';
        if (in_thead)
        {
            // the current cell is the header.
            var thead = this._range.parentNode.parentNode;
            var table = thead.parentNode;
            tbody = table.childNodes[1];
            
        }
        else
        {
            tbody = this._range.parentNode.parentNode;
        }

        if (tbody.childNodes[0] && tbody.childNodes[0].childNodes[0] && tbody.childNodes[0].childNodes[0].tagName.toLowerCase() == 'th')
        {
            td = this._doc.createElement('th');
            tr.appendChild(td);
            num_cols = this._range.parentNode.childNodes.length - 1;
        }
        else
        {
             num_cols = this._range.parentNode.childNodes.length;
        }
        // end codepot

        for (var i = 0; i < num_cols /*this._range.parentNode.childNodes.length*/; i++) {
            td = this._doc.createElement('td');
            td.appendChild(this._doc.createElement('br'));
            tr.appendChild(td);
        }

        // codepot
        if (in_thead) tbody.appendChild(tr);
        // end codepot
        else if (before !== true && this._range.parentNode.nextSibling) {
            tbody.insertBefore(tr, this._range.parentNode.nextSibling);
        } else if (before === true) {
            tbody.insertBefore(tr, this._range.parentNode);
        } else {
            tbody.appendChild(tr);
        }
        // codepot
        //this.options.onClick(0, 0);
        this.options.onClick(-1, -1);
        // end codepot
    },

    removeRow: function (e) {
        e.preventDefault();
        e.stopPropagation();

        // codepot
        if (this._range.parentNode.parentNode.tagName.toLowerCase() == 'thead') // remove the whole thead
            this._range.parentNode.parentNode.parentNode.removeChild(this._range.parentNode.parentNode); 
        else
        // end codepot
            this._range.parentNode.parentNode.removeChild(this._range.parentNode);

        // codepot
        //this.options.onClick(0, 0);
        this.options.onClick(-1, -1);
        // end codepot
    },

    addColumn: function (before, e) {
        e.preventDefault();
        e.stopPropagation();
        var cell = Array.prototype.indexOf.call(this._range.parentNode.childNodes, this._range);
        var td;

        // codepot
        //var tbody = this._range.parentNode.parentNode;
        var tbody;

        var table = this._range.parentNode.parentNode.parentNode;
        if (table.childNodes[0].tagName.toLowerCase() == 'thead')
        {
            var thead = table.childNodes[0];
            if (thead.childNodes[0]) 
            {
                // tr is expected inside thead. if no <tr> exists, don't add a column
                td = this._doc.createElement('th');
                if (before)
                    thead.childNodes[0].insertBefore(td, thead.childNodes[0].childNodes[cell]);
                else if (thead.childNodes[0].childNodes[cell].nextSibling)
                    thead.childNodes[0].insertBefore(td, thead.childNodes[0].childNodes[cell].nextSibling);
                else
                    thead.childNodes[0].appendChild(td);
            }
        }

        var in_thead = this._range.parentNode.parentNode.tagName.toLowerCase() == 'thead';
        if (in_thead)
        {
            // the current cell is the header.
            var thead = this._range.parentNode.parentNode;
            var table = thead.parentNode;
            tbody = table.childNodes[1];
        }
        else
        {
            tbody = this._range.parentNode.parentNode;
        }
        // end codepot

        for (var i = 0; i < tbody.childNodes.length; i++) {
            td = this._doc.createElement('td');
            td.appendChild(this._doc.createElement('br'));
            if (before === true) {
                tbody.childNodes[i].insertBefore(td, tbody.childNodes[i].childNodes[cell]);
            // codepot
            //} else if (this._range.parentNode.parentNode.childNodes[i].childNodes[cell].nextSibling) {
            } else if (tbody.childNodes[i].childNodes[cell].nextSibling) {
            // end codepot
                tbody.childNodes[i].insertBefore(td, tbody.childNodes[i].childNodes[cell].nextSibling);
            } else {
                tbody.childNodes[i].appendChild(td);
            }
        }

        // codepot
        //this.options.onClick(0, 0);
        this.options.onClick(-1, -1);
        // end codepot
    },

    removeColumn: function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        // codepot
        //var cell = Array.prototype.indexOf.call(this._range.parentNode.childNodes, this._range),
        //    tbody = this._range.parentNode.parentNode,
        //    rows = tbody.childNodes.length;

        var table, thead, tbody, rows;
        var cell = Array.prototype.indexOf.call(this._range.parentNode.childNodes, this._range);

        if (this._range.parentNode.parentNode.tagName.toLowerCase() == 'thead')
        {
            // the current cell is the header.
            thead = this._range.parentNode.parentNode;
            table = thead.parentNode;
            tbody = table.childNodes[1];
        }
        else
        {
            tbody = this._range.parentNode.parentNode;
            table = tbody.parentNode;
        }
        rows = tbody.childNodes.length;

        if (table.childNodes[0].tagName.toLowerCase() == 'thead')
        {
            var thead = table.childNodes[0];
            if (thead.childNodes[0])
            {
                // if no <tr> is inside <thead>, don't delete any.
                thead.childNodes[0].removeChild (thead.childNodes[0].childNodes[cell]);
            }
        }
        // end codepot

        for (var i = 0; i < rows; i++) {
            tbody.childNodes[i].removeChild(tbody.childNodes[i].childNodes[cell]);
        }

        // codepot
        //this.options.onClick(0, 0);
        this.options.onClick(-1, -1);
        // end codepot
    },

    removeTable: function (e) {
        e.preventDefault();
        e.stopPropagation();
        var cell = Array.prototype.indexOf.call(this._range.parentNode.childNodes, this._range),
            table = this._range.parentNode.parentNode.parentNode;

        table.parentNode.removeChild(table);
        this.options.onClick(0, 0);
    }
};

function Table(editor) {
    return this.init(editor);
}

var TAB_KEY_CODE = 9;

Table.prototype = {
    init: function (editor) {
        this._editor = editor;
        this._doc = this._editor.options.ownerDocument;
        this._bindTabBehavior();
    },

    insert: function (rows, cols) {

        var html = this._html(rows, cols);
        // codepot
        var header = this._header(rows, cols);
        // end codepot

        this._editor.pasteHTML(
            '<table class="medium-editor-table" id="medium-editor-table"' +
            ' >' +
            // codepot
            '<thead>' +
            header +
            '</thead>' +
            // end codepot
            '<tbody>' +
            html +
            '</tbody>' +
            // codepot
            //'</table>', {
            '</table><p></p>', {
            // end codepot
                cleanAttrs: [],
                cleanTags: []
            }
        );

        var table = this._doc.getElementById('medium-editor-table');
        table.removeAttribute('id');
        placeCaretAtNode(this._doc, table.querySelector('td'), true);

        this._editor.checkSelection();
    },

    // codepot
    _header: function (rows, cols) {
        var html = '', x, y;

        for (x = 0; x < 1; x++) {
            html += '<tr><th></th>';
            for (y = 0; y <= cols; y++) {
                html += '<th>' + y + '</th>';
            }
            html += '</tr>';
        }
        return html;
    },
    // end codepot

    _html: function (rows, cols) {
        var html = '',
            x, y,
            text = getSelectionText(this._doc);

        for (x = 0; x <= rows; x++) {
            // codepot
            html += '<tr><th>' + x + '</th>';
            // end codepot
            for (y = 0; y <= cols; y++) {
                html += '<td>' + (x === 0 && y === 0 ? text : '<br />') + '</td>';
            }
            html += '</tr>';
        }
        return html;
    },

    _bindTabBehavior: function () {
        var self = this;
        [].forEach.call(this._editor.elements, function (el) {
            el.addEventListener('keydown', function (e) {
                self._onKeyDown(e);
            });
        });
    },

    _onKeyDown: function (e) {
        var el = getSelectionStart(this._doc),
            table;

        if (e.which === TAB_KEY_CODE && isInsideElementOfTag(el, 'table')) {
            e.preventDefault();
            e.stopPropagation();
            table = this._getTableElements(el);
            if (e.shiftKey) {
                this._tabBackwards(el.previousSibling, table.row);
            } else {
                // codepot - note table.row or table.cell tends to become null in firefox
                //           when moving between cells with a tab key. the el passed
                //           seems to get wrong in firefox.
                if (this._isLastCell(el, table.row, table.root)) {
                    // codepot
                    if (table.row.cells[0].tagName.toLowerCase() == 'th')
                        this._insertRow(getParentOf(el, 'tbody'), table.row.cells.length - 1, 1);
                    else
                    // end codepot
                        this._insertRow(getParentOf(el, 'tbody'), table.row.cells.length, 0);
                }
                placeCaretAtNode(this._doc, el);
            }
        }
    },

    _getTableElements: function (el) {
        return {
            // codepot
            //cell: getParentOf(el, 'td'),
            cell: getParentOf(el, el.tagName.toLowerCase()),
            // end codepot
            row: getParentOf(el, 'tr'),
            root: getParentOf(el, 'table')
        };
    },

    _tabBackwards: function (el, row) {
        el = el || this._getPreviousRowLastCell(row);
        placeCaretAtNode(this._doc, el, true);
    },

    _insertRow: function (tbody, cols, prepend_th) {
        var tr = document.createElement('tr'),
            html = '',
            i;

        if (prepend_th) html += '<th><br /></th>';
        for (i = 0; i < cols; i += 1) {
            html += '<td><br /></td>';
        }
        tr.innerHTML = html;
        tbody.appendChild(tr);
    },

    _isLastCell: function (el, row, table) {
        return (
          (row.cells.length - 1) === el.cellIndex &&
          (table.rows.length - 1) === row.rowIndex
        );
    },

    _getPreviousRowLastCell: function (row) {
        row = row.previousSibling;
        if (row) {
            return row.cells[row.cells.length - 1];
        }
    }
};

var MediumEditorTable = MediumEditor.extensions.form.extend({
    name: 'table',

    aria: 'create table',
    action: 'table',
    contentDefault: 'TBL',
    contentFA: '<i class="fa fa-table"></i>',

    handleClick: function (event) {
        event.preventDefault();
        event.stopPropagation();

        this[this.isActive() === true ? 'hide' : 'show']();
    },

    hide: function () {
        this.setInactive();
        this.builder.hide();
    },

    show: function () {
        this.setActive();

        var range = MediumEditor.selection.getSelectionRange(this.document);
        if (range.startContainer.nodeName.toLowerCase() === 'td' ||
            range.endContainer.nodeName.toLowerCase() === 'td' ||
            MediumEditor.util.getClosestTag(MediumEditor.selection.getSelectedParentElement(range), 'td')) {
            this.builder.setEditor(MediumEditor.selection.getSelectedParentElement(range));
        // codepot
        } else if (range.startContainer.nodeName.toLowerCase() === 'th' ||
            range.endContainer.nodeName.toLowerCase() === 'th' ||
            MediumEditor.util.getClosestTag(MediumEditor.selection.getSelectedParentElement(range), 'th')) {
            this.builder.setEditor(MediumEditor.selection.getSelectedParentElement(range));
        // end codepot
        } else {
            this.builder.setBuilder();
        }
        this.builder.show(this.button.offsetLeft);
    },

    getForm: function () {
        this.builder = new Builder({
            onClick: function (rows, columns) {
                // codepot
                //if (rows > 0 && columns > 0) {
                if (rows >= 0 && columns >= 0) {
                // end codepot
                    this.table.insert(rows, columns);
                }
                this.hide();
            }.bind(this),
            ownerDocument: this.document,
            rows: this.rows || 10,
            columns: this.columns || 10
        });

        this.table = new Table(this.base);

        return this.builder.getElement();
    }
});

  return MediumEditorTable;
}()));
