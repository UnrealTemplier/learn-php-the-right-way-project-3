import "../css/dashboard.scss"
import {get} from './ajax'
import Chart from "chart.js/auto";

window.addEventListener('DOMContentLoaded', function () {
    const yearSelect = document.getElementById('yearSelect')

    yearSelect.addEventListener('change', () => update(yearSelect))

    update(yearSelect)
})

function update(yearSelect) {
    const year = yearSelect.value

    updateYearStats(year)
    updateYearChart(year)
    updateTopSpendingCategories()
}

function updateYearStats(year) {
    get(`/stats/${year}`).then(response => response.json()).then(response => {
        const expenseDiv = document.getElementById('expense')
        const incomeDiv  = document.getElementById('income')
        const netDiv     = document.getElementById('net')

        const expenseValue = parseFloat(response.expense)
        const incomeValue  = parseFloat(response.income)
        const netValue     = parseFloat(response.net)

        expenseDiv.textContent = '$' + expenseValue
        incomeDiv.textContent  = '$' + incomeValue

        netDiv.classList.remove('text-success', 'text-danger')

        if (netValue >= 0) {
            netDiv.classList.add('text-success')
            netDiv.textContent = '$' + netValue
        } else {
            netDiv.classList.add('text-danger')
            netDiv.textContent = '-$' + Math.abs(netValue)
        }
    })
}

function updateYearChart(year) {
    document.getElementById('chartLabel').textContent = `${year} Summary`

    get(`/chart/${year}`).then(response => response.json()).then(response => {
        const chartCanvas = document.getElementById('chart')

        let expensesData = Array(12).fill(null)
        let incomeData   = Array(12).fill(null)

        response.forEach(({m, expense, income}) => {
            expensesData[m - 1] = expense
            incomeData[m - 1]   = income
        })

        const currentChart = Chart.getChart(chartCanvas)
        if (currentChart) {
            currentChart.destroy()
        }

        new Chart(chartCanvas, {
            type   : 'bar',
            data   : {
                labels  : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label          : 'Expense',
                        data           : expensesData,
                        borderWidth    : 1,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor    : 'rgba(255, 99, 132, 1)',
                    },
                    {
                        label          : 'Income',
                        data           : incomeData,
                        borderWidth    : 1,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor    : 'rgba(75, 192, 192, 1)',
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        })
    })
}

function updateTopSpendingCategories() {
    const categoriesContainer = document.getElementById('categories-container')
    const categoryItem        = document.getElementById('category-item')

    get('/top-spending-categories').then(response => response.json()).then(response => {
        response.forEach(({name, total}) => {
            const category                                             = categoryItem.cloneNode(true)
            category.hidden                                            = false
            category.querySelector('#category-item-name').textContent  = name
            category.querySelector('#category-item-total').textContent = total
            categoriesContainer.appendChild(category)
        })
    })
}
