import {Modal} from "bootstrap"
import {del, get, post} from "./ajax"
import DataTable from "datatables.net"

import "../css/transactions.scss"

window.addEventListener('DOMContentLoaded', function () {
    addListeners(createDataTable())
})

function createDataTable() {
    return new DataTable('#transactionsTable', {
        serverSide : true,
        ajax       : '/transactions/load',
        orderMulti : false,
        stateSave: true,
        order: [[4, 'asc']],
        rowCallback: (row, data) => {
            if (!data.wasReviewed) {
                row.classList.add('fw-bold')
            }

            return row
        },
        columns    :
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
                {data: "category"},
                {
                    data: row => {
                        let icons = []

                        for (let i = 0; i < row.receipts.length; i++) {
                            const receipt = row.receipts[i]

                            const span       = document.createElement('span')
                            const anchor     = document.createElement('a')
                            const icon       = document.createElement('i')
                            const deleteIcon = document.createElement('i')

                            deleteIcon.role = 'button'

                            span.classList.add('position-relative')
                            icon.classList.add('bi', 'bi-file-earmark-text', 'download-receipt', 'text-primary', 'fs-4')
                            deleteIcon.classList.add('bi', 'bi-x-circle-fill', 'delete-receipt', 'text-danger', 'position-absolute')

                            anchor.href   = `/transactions/${row.id}/receipts/${receipt.id}`
                            anchor.target = 'blank'
                            anchor.title  = receipt.name

                            deleteIcon.setAttribute('data-id', receipt.id)
                            deleteIcon.setAttribute('data-transactionId', row.id)

                            anchor.append(icon)
                            span.append(anchor)
                            span.append(deleteIcon)

                            icons.push(span.outerHTML)
                        }

                        return icons.join('')
                    }
                },
                {data: "date"},
                {
                    sortable: false,
                    data    : row => `
                    <div class="d-flex gap-2">
                        <div>
                            <i class="bi ${row.wasReviewed ? 'bi-check-circle-fill text-success' : 'bi-check-circle'} toggle-reviewed-btn fs-4" 
                                role="button" data-id="${row.id}"></i>
                        </div>
                        <div class="dropdown">
                            <i class="bi bi-gear fs-4" role="button" data-bs-toggle="dropdown"></i>

                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item open-receipt-upload-btn" href="#" data-id="${row.id}">
                                        <i class="bi bi-upload"></i> Upload Receipt
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item edit-transaction-btn" href="#" data-id="${row.id}">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item delete-transaction-btn" href="#" data-id="${row.id}">
                                        <i class="bi bi-trash3-fill"></i> Delete
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                `
                }
            ]
    });
}

function addListeners(table) {
    const newTransactionModal     = new Modal(document.getElementById('newTransactionModal'))
    const editTransactionModal    = new Modal(document.getElementById('editTransactionModal'))
    const uploadReceiptModal      = new Modal(document.getElementById('uploadReceiptModal'))
    const importTransactionsModal = new Modal(document.getElementById('importTransactionsModal'))

    addNewTransactionListener(table, newTransactionModal)
    addEditTransactionListener(table, editTransactionModal)
    addDeleteTransactionListener(table)

    addUploadReceiptListener(table, uploadReceiptModal)
    addDeleteReceiptListener(table)

    addImportTransactionsListener(table, importTransactionsModal)

    addToggleReviewedListener(table)
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
                    table.draw(false)
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

function addUploadReceiptListener(table, uploadReceiptModal) {
    document.querySelector('#transactionsTable').addEventListener('click', function (event) {
        const openReceiptUploadBtn = event.target.closest('.open-receipt-upload-btn')

        if (openReceiptUploadBtn) {
            const transactionId = openReceiptUploadBtn.getAttribute('data-id')

            uploadReceiptModal._element
                              .querySelector('.upload-receipt-btn')
                              .setAttribute('data-id', transactionId)

            uploadReceiptModal.show()
        }
    })

    uploadReceiptModal._element.querySelector('.upload-receipt-btn').addEventListener('click', function (event) {
        const transactionId = event.currentTarget.getAttribute('data-id')
        const formData      = new FormData();
        const files         = uploadReceiptModal._element.querySelector('input[type="file"]').files;

        for (let i = 0; i < files.length; i++) {
            formData.append('receipt', files[i]);
        }

        post(`/transactions/${transactionId}/receipts`, formData, uploadReceiptModal._element)
            .then(response => {
                if (response.ok) {
                    table.draw(false)
                    uploadReceiptModal.hide()
                }
            })
    })
}

function addDeleteReceiptListener(table) {
    document.querySelector('#transactionsTable').addEventListener('click', function (event) {
        const deleteReceiptBtn = event.target.closest('.delete-receipt')

        if (deleteReceiptBtn) {
            const receiptId     = deleteReceiptBtn.getAttribute('data-id')
            const transactionId = deleteReceiptBtn.getAttribute('data-transactionId')

            if (confirm('Are you sure you want to delete this receipt?')) {
                del(`/transactions/${transactionId}/receipts/${receiptId}`).then(response => {
                    if (response.ok) {
                        table.draw(false)
                    }
                })
            }
        }
    })
}

function addImportTransactionsListener(table, importTransactionsModal) {
    document.querySelector('.import-transactions-btn').addEventListener('click', function (event) {
        const formData = new FormData()
        const button   = event.currentTarget
        const files    = importTransactionsModal._element.querySelector('input[type="file"]').files

        for (let i = 0; i < files.length; i++) {
            formData.append('importFile', files[i])
        }

        button.setAttribute('disabled', true)

        const btnHtml = button.innerHTML

        button.innerHTML = `
            <div class="spinner-grow spinner-grow-sm text-light" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <div class="spinner-grow spinner-grow-sm text-light" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <div class="spinner-grow spinner-grow-sm text-light" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
        `

        post(`/transactions/import`, formData, importTransactionsModal._element)
            .then(response => {
                button.removeAttribute('disabled')
                button.innerHTML = btnHtml

                if (response.ok) {
                    table.draw()
                    importTransactionsModal.hide()
                }
            })
    })
}

function addToggleReviewedListener(table) {
    document.querySelector('#transactionsTable').addEventListener('click', function (event) {
        const toggleReviewedBtn = event.target.closest('.toggle-reviewed-btn')

        if (toggleReviewedBtn) {
            const transactionId = toggleReviewedBtn.getAttribute('data-id')

            post(`/transactions/${transactionId}/review`).then(response => {
                if (response.ok) {
                    table.draw(false)
                }
            })
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
