// Category Management JavaScript

let currentCategoryId = null;

function openAddCategoryModal() {
    currentCategoryId = null;
    const form = document.getElementById('categoryForm');
    const title = document.getElementById('modal-title');
    
    // Reset form
    form.reset();
    title.textContent = 'Nueva Categoría';
    
    // Show modal
    document.getElementById('categoryModal').classList.remove('hidden');
}

function openEditCategoryModal(categoryId) {
    currentCategoryId = categoryId;
    const form = document.getElementById('categoryForm');
    const title = document.getElementById('modal-title');
    
    // Update title
    title.textContent = 'Editar Categoría';
    
    // Fetch category data
    fetch(`/api/categories/${categoryId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const category = result.data;
                form.name.value = category.name;
                form.description.value = category.description || '';
                form.parent_id.value = category.parent_id || '';
                
                // Show modal
                document.getElementById('categoryModal').classList.remove('hidden');
            } else {
                showError(result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al cargar la categoría');
        });
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

async function handleCategorySubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const url = currentCategoryId 
            ? `/api/categories/${currentCategoryId}`
            : '/api/categories';
            
        const method = currentCategoryId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        });

        const result = await response.json();
        
        if (result.success) {
            closeCategoryModal();
            showSuccess(currentCategoryId ? 'Categoría actualizada' : 'Categoría creada');
            window.location.reload();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error al guardar la categoría');
    }
}

async function confirmDeleteCategory(categoryId) {
    if (!confirm('¿Está seguro de que desea eliminar esta categoría?')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/categories/${categoryId}`, {
            method: 'DELETE',
        });

        const result = await response.json();
        
        if (result.success) {
            showSuccess('Categoría eliminada');
            window.location.reload();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error al eliminar la categoría');
    }
}

async function moveCategory(categoryId, newParentId) {
    try {
        const response = await fetch('/api/categories/move', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                category_id: categoryId,
                new_parent_id: newParentId
            }),
        });

        const result = await response.json();
        
        if (result.success) {
            showSuccess('Categoría movida');
            window.location.reload();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error al mover la categoría');
    }
}

// Notification functions
function showSuccess(message) {
    // You can implement this using your preferred notification library
    // For now, we'll use a simple alert
    alert(message);
}

function showError(message) {
    // You can implement this using your preferred notification library
    // For now, we'll use a simple alert
    alert('Error: ' + message);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add drag and drop functionality for category reordering if needed
    // This is just a placeholder for potential future enhancement
    
    const categoryRows = document.querySelectorAll('tr[data-category-id]');
    categoryRows.forEach(row => {
        row.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', row.dataset.categoryId);
        });
        
        row.addEventListener('dragover', (e) => {
            e.preventDefault();
        });
        
        row.addEventListener('drop', (e) => {
            e.preventDefault();
            const draggedId = e.dataTransfer.getData('text/plain');
            const droppedId = row.dataset.categoryId;
            
            if (draggedId !== droppedId) {
                moveCategory(draggedId, droppedId);
            }
        });
    });
});

// Form validation
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    const name = this.name.value.trim();
    const parentId = this.parent_id.value;
    
    // Basic validation
    if (name.length < 2) {
        e.preventDefault();
        showError('El nombre de la categoría debe tener al menos 2 caracteres');
        return;
    }
    
    // Prevent selecting self as parent
    if (currentCategoryId && parentId === currentCategoryId) {
        e.preventDefault();
        showError('Una categoría no puede ser su propia categoría padre');
        return;
    }
});

// Search functionality
function searchCategories(query) {
    const rows = document.querySelectorAll('tbody tr');
    query = query.toLowerCase();
    
    rows.forEach(row => {
        const name = row.querySelector('td:first-child').textContent.toLowerCase();
        row.style.display = name.includes(query) ? '' : 'none';
    });
}

// Add search input event listener if search functionality is needed
const searchInput = document.querySelector('input[type="search"]');
if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        searchCategories(e.target.value);
    });
}
