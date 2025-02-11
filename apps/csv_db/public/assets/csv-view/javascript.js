$(document).ready(function () {

    // This will be popped and pushed into history, so must contain all state values.
    let state = {
        csvUploadId: $('#csvUploadId').val(),
        currentPage: $('#currentPage').val(),
        perPage: $('#perPage').val(),
        basePath: window.currentPagePath,
        apiUri: window.apiUri
    };

    // Fetch data from the server based on the current state
    async function fetchData(state) {
        const query = {}
        if (state.currentPage){
            query.page = state.currentPage;
        }
        if (state.perPage){
            query.perPage = state.perPage;
        }
        const queryString = new URLSearchParams(query).toString();
        const response = await fetch(`${state.apiUri}/${state.csvUploadId}?${queryString}`);
        const data = await response.json();

        if (!response.ok) {
            $('#errorBox').text(data.error || `HTTP error! status: ${response.status}`).show();
            $('#data-table').hide();
            return null;
        }

        $('#errorBox').hide();
        $('#data-table').show();

        return data;
    }

    // Render the table with the fetched data
    function renderTable(data) {
        $('#fileName').val(data.fileName);
        $('#created').val(data.created);
        $('#currentPage').val(data.page);

        const headers = $('#table-headers');
        const body = $('#table-body');
        headers.empty();
        body.empty();

        // Render headers
        data.columns.forEach(column => {
            headers.append(`<th>${column}</th>`);
        });

        // Render rows
        data.rows.forEach(row => {
            const tr = $('<tr></tr>');
            row.forEach(cell => {
                tr.append(`<td>${cell}</td>`);
            });
            body.append(tr);
        });

        // Update pagination info
        renderPagination(data.page, data.pageCount);
    }

    // Render pagination buttons
    function renderPagination(currentPage, pageCount) {
        const pagination = $('.pagination');
        pagination.empty();

        const createButton = (text, page, disabled = false) => {
            const button = $(`<button class="btn btn-primary mx-1">${text}</button>`);
            if (disabled) {
                button.prop('disabled', true);
            } else {
                const newState = { ...state, currentPage: page };
                button.on('click', () => loadPage(newState));
            }
            return button;
        };

        pagination.append(createButton('<<', 1, currentPage === 1));
        pagination.append(createButton('<', currentPage - 1, currentPage === 1));

        for (let i = Math.max(1, currentPage - 2); i <= Math.min(pageCount, currentPage + 2); i++) {
            const button = createButton(i, i, i === currentPage);
            if (i === currentPage) {
                button.addClass('active');
            }
            pagination.append(button);
        }

        pagination.append(createButton('>', currentPage + 1, currentPage === pageCount));
        pagination.append(createButton('>>', pageCount, currentPage === pageCount));
    }

    // Handle back/forward navigation
    window.onpopstate = function (event) {
        if (event.state) {
            loadPage(event.state);
        }
    };

    // Load a page using the passed state
    async function loadPage(state) {
        // Update the URL with the current state
        const queryParams = new URLSearchParams({ page: state.currentPage, perPage: state.perPage });
        const newUrl = `${state.basePath}/${state.csvUploadId}?${queryParams.toString()}`;
        history.pushState(state, '', newUrl);

        const data = await fetchData(state);
        renderTable(data);
    }

    // Initial load
    loadPage(state);

    // Event listeners for inputs
    $('#csvUploadId').on('change', function () {
        state.csvUploadId = $(this).val();
        state.page = 1;
        loadPage(state); // Reset to first page
    });

    $('#perPage').on('change', function () {
        state.perPage = $(this).val();
        state.page = 1;
        loadPage(state); // Reset to first page
    });

    $('#currentPage').on('change', function () {
        state.currentPage = $(this).val();
        loadPage(state);
    });
});