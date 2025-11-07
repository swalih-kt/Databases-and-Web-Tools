let allData = [];
let originalData = [];
let currentPage = 1;
let rowsPerPage = 25;

function performSearch(event) {
    event.preventDefault();

    const search = document.getElementById('searchInput').value.trim();
    const gene = document.getElementById('geneSelect').value;
    const errorMessage = document.getElementById('errorMessage');
    errorMessage.textContent = '';

    if (!search) {
        errorMessage.textContent = 'Please fill the search column';
        return;
    }
    if (!gene) {
        errorMessage.textContent = 'Please select a gene';
        return;
    }

    fetch(`backend.php?search=${encodeURIComponent(search)}&gene=${encodeURIComponent(gene)}`)
        .then(response => response.json())
        .then(result => {
            allData = result.data || [];
            originalData = [...allData];
            currentPage = 1;

            if (allData.length > 0) {
                document.getElementById('resultsContainer').style.display = 'block';
                document.getElementById('noResultsMessage').style.display = 'none';
                displayResults();
            } else {
                document.getElementById('resultsContainer').style.display = 'none';
                document.getElementById('noResultsMessage').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.textContent = 'Failed to fetch results.';
        });
}

function displayResults() {
    const tableBody = document.querySelector('#resultsTable tbody');
    tableBody.innerHTML = '';

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const pageData = allData.slice(start, end);

    if (pageData.length === 0) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 13;
        cell.textContent = 'No data available for the current page';
        cell.className = 'text-center text-muted';
        row.appendChild(cell);
        tableBody.appendChild(row);
        return;
    }

    pageData.forEach(row => {
        const tr = document.createElement('tr');

        const displayKeys = ['Gene','AAChange','dbSNP','Pop','Mutationeffect','ACMG_Classification'];
        displayKeys.forEach(key => {
            const td = document.createElement('td');
            td.textContent = row[key] || '—';
            tr.appendChild(td);
        });

        // More Info button
        const infoCell = document.createElement('td');
        const infoBtn = document.createElement('button');
        infoBtn.className = 'btn btn-sm btn-primary';
        infoBtn.textContent = 'More Info';
        infoBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            showDetailView(row);
        });
        infoCell.appendChild(infoBtn);
        tr.appendChild(infoCell);

        tableBody.appendChild(tr);
    });

    updatePageInfo();
}

function updatePageInfo() {
    const totalPages = Math.ceil(allData.length / rowsPerPage);
    document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
}

function changePage(direction) {
    const newPage = currentPage + direction;
    const totalPages = Math.ceil(allData.length / rowsPerPage);
    if (newPage > 0 && newPage <= totalPages) {
        currentPage = newPage;
        displayResults();
    }
}

function changeRowsPerPage() {
    rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
    currentPage = 1;
    displayResults();
}

function searchTable() {
    const searchTerm = document.getElementById('tableSearch').value.toLowerCase();
    if (searchTerm === "") {
        allData = [...originalData];
    } else {
        allData = originalData.filter(row =>
            Object.values(row).some(value =>
                (value || '').toString().toLowerCase().includes(searchTerm)
            )
        );
    }
    currentPage = 1;
    displayResults();
}

function fillSearch(text) {
    document.getElementById('searchInput').value = text;
}

function showDetailView(rowData) {
    const modalContent = document.getElementById('modalContent');
    modalContent.innerHTML = '';

    const geneKeys = ['Gene','Build', 'Chr', 'Start', 'End', 'Ref', 'Alt','HGVS_NM', 'HGVS_NC', 'HGVS_NP','Mutation','GeographicalOrigin','AAChange'];
    const clinicalKeys = ['Disease','Technique', 'Mutationeffect','Inheritance','ACMG_Classification','Ethnicity','Pop'];
    const annotationKeys = ['References_info'];

    function createTable(keys, title) {
        const section = document.createElement('div');
        section.className = 'modal-section';

        const heading = document.createElement('h5');
        heading.textContent = title;
        heading.style.marginBottom = '10px';
        heading.style.color = '#551764';
        section.appendChild(heading);

        const table = document.createElement('table');
        table.className = 'table table-sm table-bordered';

        keys.forEach(key => {
            const tr = document.createElement('tr');

            const th = document.createElement('th');
            th.textContent = key;
            tr.appendChild(th);

            const td = document.createElement('td');
            td.textContent = rowData[key] || '—';
            tr.appendChild(td);

            table.appendChild(tr);
        });

        section.appendChild(table);
        return section;
    }

    modalContent.appendChild(createTable(geneKeys, 'Variant Annotation'));
    modalContent.appendChild(createTable(clinicalKeys, 'Clinical Variant Information'));
    modalContent.appendChild(createTable(annotationKeys, 'References'));

    document.getElementById('variantModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('variantModal').style.display = 'none';
}

window.onclick = function (event) {
    const modal = document.getElementById('variantModal');
    if (event.target === modal) {
        closeModal();
    }
};

document.getElementById('searchForm').addEventListener('submit', performSearch);
