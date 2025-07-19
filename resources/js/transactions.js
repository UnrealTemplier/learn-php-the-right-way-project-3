import {Modal} from "bootstrap"
import {del, get, post} from "./ajax"
import DataTable from "datatables.net"

window.addEventListener('DOMContentLoaded', function () {
    addListeners(createDataTable())
})

function createDataTable() {
    return new DataTable('#transactionsTable', {
        serverSide: true,
        ajax      : '/transactions/load',
        orderMulti: false,
        columns   :
            [
                {data: "description"},
                {
                    data: row => new Intl.NumberFormat(
                        'en-US',
                        {
                            style          : "currency",
                            currency       : 'USD',
                            currencyDisplay: 'symbol'
                        }
                    ).format(row.amount)
                },
                {data: "category", sortable: false},
                {data: "date"},
                {
                    sortable: false, data: row => `
                    <div class="d-flex flex-row">
                        <button type="submit" class="btn btn-outline-primary delete-transaction-btn" data-id="${row.id}">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                        <button class="ms-2 btn btn-outline-primary edit-transaction-btn" data-id="${row.id}">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                    </div>
                `
                }
            ]
    });
}

function addListeners(table) {
    const newTransactionModal  = new Modal(document.getElementById('newTransactionModal'))
    const editTransactionModal = new Modal(document.getElementById('editTransactionModal'))

    addNewTransactionListener(table, newTransactionModal)
    addEditTransactionListener(table, editTransactionModal)
    addDeleteTransactionListener(table)
}

function addNewTransactionListener(table, newTransactionModal) {
    document.querySelector('.new-transaction-btn').addEventListener('click', function (event) {
        openNewTransactionModal(newTransactionModal)
    })

    newTransactionModal._element.querySelector('.create-transaction-btn').addEventListener('click', function (event) {
        post(`/transactions`, getTransactionData(newTransactionModal), newTransactionModal._element)
            .then(response => {
                if (response.ok) {
                    table.draw()
                    newTransactionModal.hide()
                }
            })
    })
}

function addEditTransactionListener(table, editTransactionModal) {
    document.querySelector('#transactionsTable').addEventListener('click', function (event) {
        const editBtn = event.target.closest('.edit-transaction-btn')

        if (editBtn) {
            const transactionId = editBtn.getAttribute('data-id')

            get(`/transactions/${transactionId}`)
                .then(response => response.json())
                .then(response => openEditTransactionModal(editTransactionModal, response))
        }
    })

    editTransactionModal._element.querySelector('.save-transaction-btn').addEventListener('click', function (event) {
        const transactionId = event.currentTarget.getAttribute('data-id')

        post(`/transactions/${transactionId}`, getTransactionData(editTransactionModal), editTransactionModal._element)
            .then(response => {
                if (response.ok) {
                    table.draw()
                    editTransactionModal.hide()
                }
            })
    })
}

function addDeleteTransactionListener(table) {
    document.querySelector('#transactionsTable').addEventListener('click', function (event) {
        const deleteBtn = event.target.closest('.delete-transaction-btn')

        if (deleteBtn) {
            const transactionId = deleteBtn.getAttribute('data-id')

            if (confirm('Are you sure you want to delete this transaction?')) {
                del(`/transactions/${transactionId}`).then((response) => {
                    if (response.ok) {
                        table.draw()
                    }
                })
            }
        }
    })
}

function getTransactionData(modal) {
    let data     = {}
    const fields = [
        ...modal._element.querySelectorAll(`input`),
        ...modal._element.querySelectorAll(`select`),
    ]

    fields.forEach(field => {
        data[field.name] = field.value
    })

    return data
}

function openNewTransactionModal(modal) {
    const fields = [
        ...modal._element.querySelectorAll(`input`),
        ...modal._element.querySelectorAll(`select`)
    ]

    fields.forEach(field => {
        field.value = ''
    })

    modal.show()
}

function openEditTransactionModal(modal, {id, ...data}) {
    for (const name in data) {
        const input = modal._element.querySelector(`[name="${name}"]`)
        input.value = data[name]
    }

    modal._element.querySelector('.save-transaction-btn').setAttribute('data-id', id)
    modal.show()
}
