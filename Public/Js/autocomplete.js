document.addEventListener('DOMContentLoaded', function () {
    const companyInput = document.getElementById('company_name');
    const suggestionsBox = document.getElementById('autocomplete-suggestions');

    companyInput.addEventListener('input', async function () {
        const query = companyInput.value.trim();

        if (query.length > 1) { // Fetch suggestions for inputs with more than 1 character
            try {
                const response = await fetch(`/search-companies?query=${encodeURIComponent(query)}`);
                const suggestions = await response.json();

                // Clear previous suggestions
                suggestionsBox.innerHTML = '';

                if (suggestions.length > 0) {
                    suggestions.forEach(company => {
                        const suggestionItem = document.createElement('div');
                        suggestionItem.classList.add('suggestion-item');
                        suggestionItem.textContent = company;
                        suggestionItem.addEventListener('click', function () {
                            companyInput.value = company;
                            suggestionsBox.innerHTML = ''; // Clear suggestions after selection
                        });
                        suggestionsBox.appendChild(suggestionItem);
                    });
                } else {
                    suggestionsBox.innerHTML = '<div class="no-suggestions">No results found</div>';
                }
            } catch (error) {
                console.error('Error fetching suggestions:', error);
            }
        } else {
            suggestionsBox.innerHTML = ''; // Clear suggestions for short queries
        }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function (e) {
        if (!suggestionsBox.contains(e.target) && e.target !== companyInput) {
            suggestionsBox.innerHTML = '';
        }
    });
});
