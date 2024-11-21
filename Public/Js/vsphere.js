document.addEventListener("DOMContentLoaded", () => {
    const importVmDataButton = document.getElementById("importVmDataButton");
    const resourcePoolInput = document.getElementById("resourcePool");
    const suggestionsContainer = document.getElementById("autocompleteSuggestions");
    const vmResultsContainer = document.getElementById("vmResults");

    // Handle VM Data Import
    importVmDataButton.addEventListener("click", () => {
        fetch('/vsphere/import', { method: 'POST' })
            .then((response) => response.json())
            .then((data) => alert(data.success || data.error))
            .catch((error) => console.error("Error during VM data import:", error));
    });

    // Fetch suggestions as the user types
    resourcePoolInput.addEventListener("input", () => {
        const query = resourcePoolInput.value.trim();

        if (query.length < 2) {
            suggestionsContainer.innerHTML = ""; // Clear suggestions for short input
            return;
        }

        fetch(`/vsphere/resource-pools?query=${encodeURIComponent(query)}`)
            .then((response) => response.json())
            .then((data) => {
                suggestionsContainer.innerHTML = ""; // Clear previous suggestions
                data.forEach((resourcePool) => {
                    const suggestion = document.createElement("div");
                    suggestion.textContent = resourcePool;
                    suggestion.className = "suggestion-item";
                    suggestion.addEventListener("click", () => {
                        resourcePoolInput.value = resourcePool;
                        suggestionsContainer.innerHTML = ""; // Clear suggestions
                    });
                    suggestionsContainer.appendChild(suggestion);
                });
            })
            .catch((error) => console.error("Error fetching resource pool suggestions:", error));
    });

    // Handle VM search
    document.getElementById("searchVmsForm").addEventListener("submit", (e) => {
        e.preventDefault();
        const resourcePool = resourcePoolInput.value.trim();

        fetch(`/vsphere/vms?resource_pool=${encodeURIComponent(resourcePool)}`)
            .then((response) => response.json())
            .then((data) => {
                vmResultsContainer.innerHTML = ""; // Clear previous results
                if (data.error) {
                    vmResultsContainer.textContent = data.error;
                } else {
                    data.forEach((vm) => {
                        const vmInfo = document.createElement("div");
                        vmInfo.textContent = `${vm.name} - CPU: ${vm.cpu_count}, RAM: ${vm.memory_size_GB} GB`;
                        vmResultsContainer.appendChild(vmInfo);
                    });
                }
            })
            .catch((error) => console.error("Error fetching VM data:", error));
    });
});
