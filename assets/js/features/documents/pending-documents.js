document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('pending-document-search');
    const documentRows = Array.from(document.querySelectorAll('[data-pending-document-row]'));
    const noResultsRow = document.getElementById('pending-no-search-results');

    if (!searchInput || !documentRows.length || !noResultsRow) {
        return;
    }

    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        documentRows.forEach((row) => {
            const matches = row.textContent.toLowerCase().includes(searchTerm);
            row.classList.toggle('d-none', !matches);
            if (matches) {
                visibleCount += 1;
            }
        });

        noResultsRow.classList.toggle('d-none', visibleCount > 0);
    });
});
