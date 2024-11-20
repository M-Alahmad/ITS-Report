document.addEventListener('DOMContentLoaded', function () {
    const resourcePoolInput = document.getElementById('resource_pool');
    const suggestionsBox = document.getElementById('autocomplete-suggestions');
    const searchButton = document.getElementById('search-resource-pool');
    const vmDetailsTable = document.getElementById('vm-details');

    // Autocomplete for resource pools
    resourcePoolInput.addEventListener('input', async function () {
        const query = resourcePoolInput.value.trim();

        if (query.length > 1) {
            try {
                const response = await fetch(`/search-resource-pools?query=${encodeURIComponent(query)}`);
                const suggestions = await response.json();

                suggestionsBox.innerHTML = '';

                if (suggestions.length > 0) {
                    suggestions.forEach(pool => {
                        const suggestionItem = document.createElement('div');
                        suggestionItem.classList.add('suggestion-item');
                        suggestionItem.textContent = pool;
                        suggestionItem.addEventListener('click', function () {
                            resourcePoolInput.value = pool;
                            suggestionsBox.innerHTML = '';
                        });
                        suggestionsBox.appendChild(suggestionItem);
                    });
                } else {
                    suggestionsBox.innerHTML = '<div class="no-suggestions">No results found</div>';
                }
            } catch (error) {
                console.error('Error fetching resource pools:', error);
            }
        } else {
            suggestionsBox.innerHTML = '';
        }
    });

    // Fetch VM details for the selected resource pool
    searchButton.addEventListener('click', async function () {
        const resourcePoolName = resourcePoolInput.value.trim();
        if (resourcePoolName) {
            try {
                const response = await fetch(`/vsphere/search-vms?resource_pool=${encodeURIComponent(resourcePoolName)}`);
                const vmData = await response.json();

                if (vmData.error) {
                    vmDetailsTable.innerHTML = `<tr><td colspan="5">${vmData.error}</td></tr>`;
                } else {
                    vmDetailsTable.innerHTML = '';
                    vmData.forEach(vm => {
                        const row = `
                            <tr>
                                <td>${vm.name}</td>
                                <td>${vm.cpu_count}</td>
                                <td>${vm.memory_size_GB}</td>
                                <td>${vm.disk_capacity_GB}</td>
                                <td>${vm.disk_used_GB}</td>
                            </tr>
                        `;
                        vmDetailsTable.innerHTML += row;
                    });
                }
            } catch (error) {
                console.error('Error fetching VM details:', error);
                vmDetailsTable.innerHTML = `<tr><td colspan="5">Error fetching data</td></tr>`;
            }
        } else {
            alert('Please enter a resource pool name.');
        }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function (e) {
        if (!suggestionsBox.contains(e.target) && e.target !== resourcePoolInput) {
            suggestionsBox.innerHTML = '';
        }
    });
});
