document.addEventListener("DOMContentLoaded", () => { 
    const companyNameInput = document.getElementById("companyName");
    const suggestionsContainer = document.getElementById("autocompleteSuggestions");

    // Fetch suggestions as the user types
    companyNameInput.addEventListener("input", () => {
        const query = companyNameInput.value.trim();

        if (query.length < 2) {
            suggestionsContainer.innerHTML = ""; // Clear suggestions for short input
            return;
        }

        fetch(`/sipnow/getCompanySuggestions?query=${encodeURIComponent(query)}`)
            .then((response) => response.json())
            .then((data) => {
                suggestionsContainer.innerHTML = ""; // Clear previous suggestions

                data.forEach((company) => {
                    const suggestion = document.createElement("div");
                    suggestion.textContent = company.company_name;
                    suggestion.className = "suggestion-item";

                    // On click, populate the input field
                    suggestion.addEventListener("click", () => {
                        companyNameInput.value = company.company_name;
                        suggestionsContainer.innerHTML = ""; // Clear suggestions
                    });

                    suggestionsContainer.appendChild(suggestion);
                });
            })
            .catch((error) => console.error("Error fetching company suggestions:", error));
    });
});
