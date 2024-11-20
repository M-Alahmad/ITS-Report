document.getElementById('importButton')?.addEventListener('click', function() {
    fetch('/import-sipnow-data', {
        method: 'POST',
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message || 'Import completed successfully!');
    })
    .catch(error => {
        alert('Error during import: ' + error.message);
    });
});
