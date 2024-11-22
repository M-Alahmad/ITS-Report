document.addEventListener("DOMContentLoaded", () => {
    // Import CSV Functionality
    const importButton = document.getElementById("importCsvButton");
    const importStatus = document.getElementById("importStatus");

    importButton.addEventListener("click", () => {
        fetch('/sipnow/import', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    importStatus.textContent = data.success;
                    importStatus.classList.add('status-success');
                    importStatus.classList.remove('status-error');
                } else {
                    importStatus.textContent = data.error;
                    importStatus.classList.add('status-error');
                    importStatus.classList.remove('status-success');
                }
            })
            .catch(error => {
                importStatus.textContent = "Failed to import CSV.";
                importStatus.classList.add('status-error');
                importStatus.classList.remove('status-success');
                console.error("Error importing CSV:", error);
            });
    });
});
