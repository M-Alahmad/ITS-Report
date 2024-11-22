document.addEventListener("DOMContentLoaded", () => {
    const resourcePoolInput = document.getElementById("resourcePool");
    const resultsContainer = document.getElementById("results");

    // Fetch VM data dynamically when a resource pool is selected
    resourcePoolInput.addEventListener("change", () => {
        const resourcePool = resourcePoolInput.value;

        fetch(`/vsphere/search-vms?resource_pool=${encodeURIComponent(resourcePool)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    resultsContainer.innerHTML = `<tr><td colspan="4">${data.error}</td></tr>`;
                } else {
                    populateTable(data);
                }
            })
            .catch(error => {
                console.error("Error fetching VM data:", error);
                resultsContainer.innerHTML = `<tr><td colspan="4">Failed to fetch data</td></tr>`;
            });
    });

    // Populate table rows with dynamic data
    function populateTable(data) {
        resultsContainer.innerHTML = ""; // Clear existing rows

        data.forEach(vm => {
            const row = `
                <tr>
                    <td>${vm.name}</td>
                    <td>${vm.cpu_count}</td>
                    <td>${vm.memory_size_GB}</td>
                    <td>${vm.disk_capacity_GB}</td>
                </tr>
            `;
            resultsContainer.insertAdjacentHTML("beforeend", row);
        });
    }
});
