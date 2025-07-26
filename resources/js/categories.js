import {Modal} from "bootstrap"
import {del, get, post} from "./ajax"
import DataTable from "datatables.net"

window.addEventListener('DOMContentLoaded', function () {
    addListeners(createDataTable())
})

function createDataTable() {
    return new DataTable('#categoriesTable', {
        serverSide: true,
        ajax      : '/categories/load',
        orderMulti: false,
        stateSave: true,
        order: [[0, 'asc']],
        columns   : [{data: "name"}, {data: "createdAt"}, {data: "updatedAt"}, {
            sortable: false, data: row => `
                    <div class="d-flex flex-row">
                        <button type="submit" class="btn btn-outline-primary delete-category-btn" data-id="${row.id}">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                        <button class="ms-2 btn btn-outline-primary edit-category-btn" data-id="${row.id}">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                    </div>
                `
        }]
    });
}

function addListeners(table) {
    const newCategoryModal  = new Modal(document.getElementById('newCategoryModal'))
    const editCategoryModal = new Modal(document.getElementById('editCategoryModal'))

    addNewCategoryListener(table, newCategoryModal)
    addEditCategoryListener(table, editCategoryModal)
    addDeleteCategoryListener(table)
}

function addNewCategoryListener(table, newCategoryModal) {
    document.querySelector('.new-category-btn').addEventListener('click', function (event) {
        openNewCategoryModal(newCategoryModal)
    })

    newCategoryModal._element.querySelector('.create-category-btn').addEventListener('click', function (event) {
        post(`/categories`, {
            name: newCategoryModal._element.querySelector('input[name="name"]').value
        }, newCategoryModal._element).then(response => {
            if (response.ok) {
                table.draw()
                newCategoryModal.hide()
            }
        })
    })
}

function addEditCategoryListener(table, editCategoryModal) {
    document.querySelector('#categoriesTable').addEventListener('click', function (event) {
        const editBtn = event.target.closest('.edit-category-btn')

        if (editBtn) {
            const categoryId = editBtn.getAttribute('data-id')

            get(`/categories/${categoryId}`)
                .then(response => response.json())
                .then(response => openEditCategoryModal(editCategoryModal, response))
        }
    })

    editCategoryModal._element.querySelector('.save-category-btn').addEventListener('click', function (event) {
        const categoryId = event.currentTarget.getAttribute('data-id')

        post(`/categories/${categoryId}`, {
            name: editCategoryModal._element.querySelector('input[name="name"]').value
        }, editCategoryModal._element).then(response => {
            if (response.ok) {
                table.draw(false)
                editCategoryModal.hide()
            }
        })
    })
}

function addDeleteCategoryListener(table) {
    document.querySelector('#categoriesTable').addEventListener('click', function (event) {
        const deleteBtn = event.target.closest('.delete-category-btn')

        if (deleteBtn) {
            const categoryId = deleteBtn.getAttribute('data-id')

            if (confirm('Are you sure you want to delete this category?')) {
                del(`/categories/${categoryId}`).then((response) => {
                    if (response.ok) {
                        table.draw(false)
                    }
                })
            }
        }
    })
}

function openNewCategoryModal(modal) {
    const nameInput = modal._element.querySelector('input[name="name"]')

    nameInput.value = ''
    modal.show()
}

function openEditCategoryModal(modal, {id, name}) {
    const nameInput = modal._element.querySelector('input[name="name"]')

    nameInput.value = name
    modal._element.querySelector('.save-category-btn').setAttribute('data-id', id)
    modal.show()
}
