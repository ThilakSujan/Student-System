/**
 * Universal Dynamic Export Engine
 * Scrapes the currently visible DataTable to guarantee 100% exact exports,
 * preserving all active searches, filters, and sorts.
 */

function exportTable(tableSelector, title, format) {
    const table = document.querySelector(tableSelector);
    if (!table) {
        alert("Table not found for export.");
        return;
    }

    // 1. Extract Headers (ignoring columns with 'Actions' or explicitly non-exportable)
    const headers = [];
    const exportIndexes = [];
    
    const thead = table.querySelector('thead');
    if (!thead) {
        alert("Table must have a <thead>.");
        return;
    }

    const headerCells = thead.querySelectorAll('th');
    headerCells.forEach((th, index) => {
        // Skip the first column (usually '#' or '#ID' which the user requested to remove)
        if (index === 0) return;

        const text = th.innerText.trim();
        // Ignore action columns
        if (text.toLowerCase() !== 'actions' && text.toLowerCase() !== 'action' && !th.classList.contains('no-export')) {
            headers.push(text);
            exportIndexes.push(index);
        }
    });

    // 2. Extract Visible Rows
    const rows = [];
    const tbody = table.querySelector('tbody');
    if (tbody) {
        const trs = tbody.querySelectorAll('tr');
        trs.forEach(tr => {
            // Ignore rows hidden by JS search filters
            if (tr.style.display !== 'none' && !tr.classList.contains('d-none')) {
                // Ignore empty states (like "No records found")
                const tds = tr.querySelectorAll('td');
                if (tds.length > 1 || (tds.length === 1 && !tds[0].colSpan)) {
                    const rowData = [];
                    exportIndexes.forEach(idx => {
                        if (tds[idx]) {
                            // Extract text content cleanly
                            rowData.push(tds[idx].innerText.trim());
                        } else {
                            rowData.push("");
                        }
                    });
                    rows.push(rowData);
                }
            }
        });
    }

    if (rows.length === 0) {
        alert("No visible data to export.");
        return;
    }

    // 3. Submit data to export_handler.php via a hidden form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../exports/export_handler.php';
    // Open PDF in a new tab if preferred, but usually downloading is fine
    // form.target = '_blank'; 

    const payload = {
        title: title,
        format: format,
        headers: headers,
        rows: rows
    };

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'export_payload';
    input.value = JSON.stringify(payload);

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
