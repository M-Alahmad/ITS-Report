document.addEventListener("DOMContentLoaded", () => {
    const autocompleteInputs = document.querySelectorAll("[data-autocomplete]");

    autocompleteInputs.forEach(input => {
        const suggestionsContainer = document.createElement("div");
        suggestionsContainer.classList.add("suggestions");
        input.parentNode.appendChild(suggestionsContainer);

        input.addEventListener("input", () => {
            const query = input.value.trim();
            const endpoint = input.getAttribute("data-autocomplete");

            if (query.length < 2) {
                suggestionsContainer.innerHTML = '';
                return;
            }

            fetch(`${endpoint}?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsContainer.innerHTML = '';
                    data.forEach(item => {
                        // Dynamically access `company_name` or fallback to the raw value
                        const displayText = item.company_name || item.resource_pool || item.name || item;
                        const suggestion = document.createElement("div");
                        suggestion.textContent = displayText;
                        suggestion.className = "suggestion-item";
                        suggestion.style.cursor = "pointer";

                        suggestion.addEventListener("click", () => {
                            input.value = displayText;
                            suggestionsContainer.innerHTML = '';
                            if (input.dataset.triggerSearch === "true") {
                                fetchDetailsFor(displayText, input.dataset.targetResults);
                            }
                        });

                        suggestionsContainer.appendChild(suggestion);
                    });
                })
                .catch(error => console.error("Error fetching autocomplete data:", error));
        });
    });

    // Fetch details for selected item (specific to vSphere)
    function fetchDetailsFor(selectedItem, targetResultsId) {
        const resultsContainer = document.getElementById(targetResultsId);
        const endpoint = `/vsphere/vms?resource_pool=${encodeURIComponent(selectedItem)}`;

        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                resultsContainer.innerHTML = '';
                if (data.error) {
                    resultsContainer.textContent = data.error;
                } else {
                    const tableBody = document.createElement("tbody");
                    data.forEach(vm => {
                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td>${vm.name}</td>
                            <td>${vm.cpu_count}</td>
                            <td>${vm.memory_size_GB} GB</td>
                            <td>${vm.disk_used_GB} GB</td>
                        `;
                        tableBody.appendChild(row);
                    });
                    resultsContainer.appendChild(tableBody);
                }
            })
            .catch(error => console.error("Error fetching details:", error));
    }
});
