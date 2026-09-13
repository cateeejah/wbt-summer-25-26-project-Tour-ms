/* ================================================================
   LIVE SEARCH
   Two small helpers used by every searchable table in the app:

   liveSearch(inputId, handler)
       Waits 250ms after the user stops typing, then calls handler().
       Debouncing this way avoids firing a request on every keystroke.

   ajaxTable(options)
       Fetches JSON from options.url and redraws a table body without
       reloading the page. options = { url, tbody, counter, columns, word, row }
       row(item, index) must return one <tr>...</tr> string for that item.
   ================================================================ */

function esc(text) {
    var div = document.createElement('div');
    div.textContent = (text === null || text === undefined) ? '' : String(text);
    return div.innerHTML;
}

function liveSearch(inputId, handler) {
    var input = document.getElementById(inputId);
    if (!input) { return; }

    var timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(handler, 250);
    });
}

function ajaxTable(options) {
    var tbody = document.getElementById(options.tbody);
    var counter = options.counter ? document.getElementById(options.counter) : null;
    if (!tbody) { return; }

    fetch(options.url, { credentials: 'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (rows) {

            if (rows && rows.error) {
                tbody.innerHTML = '<tr><td colspan="' + options.columns + '" class="empty">' +
                                   esc(rows.error) + '</td></tr>';
                return;
            }
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="' + options.columns + '" class="empty">' +
                                   'Nothing matches your search.</td></tr>';
                if (counter) { counter.textContent = '0 ' + options.word; }
                return;
            }

            var html = '';
            rows.forEach(function (item, index) { html += options.row(item, index); });
            tbody.innerHTML = html;

            if (counter) { counter.textContent = rows.length + ' ' + options.word; }
        })
        .catch(function (err) {
            console.error('Search failed:', err);
        });
}
