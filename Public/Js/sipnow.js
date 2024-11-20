document.getElementById('sipnow-search').addEventListener('input', function () {
    const query = this.value;
    const resultsList = document.getElementById('search-results');

    if (query.length < 3) {
        resultsList.innerHTML = ''; // Clear suggestions if query is too short
        return;
    }

    fetch(`/search-sipnow?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            resultsList.innerHTML = '';
            if (data.message) {
                resultsList.innerHTML = `<li>${data.message}</li>`;
                return;
            }
            data.forEach(item => {
                const li = document.createElement('li');
                li.textContent = item.company_name;
                li.addEventListener('click', () => {
                    document.getElementById('sipnow-search').value = item.company_name;
                    resultsList.innerHTML = '';
                });
                resultsList.appendChild(li);
            });
        })
        .catch(error => console.error('Error fetching search results:', error));
});

document.getElementById('download-report').addEventListener('click', function () {
    const companyName = document.getElementById('sipnow-search').value.trim();
    if (companyName) {
        window.location.href = `/download-csv?company_name=${encodeURIComponent(companyName)}`;
    } else {
        alert('Please enter or select a company name.');
    }
});
