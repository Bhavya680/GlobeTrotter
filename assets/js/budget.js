/**
 * GlobeTrotter - Budget & Expenses JS Engine
 */

document.addEventListener('DOMContentLoaded', function() {
    if (typeof TRIP_ID === 'undefined') return;

    initBudgetChart();
    initAddExpenseForm();
});

async function initBudgetChart() {
    const canvas = document.getElementById('budgetChart');
    if (!canvas) return;

    try {
        const res = await api('GET', '/api/budget.php?trip_id=' + TRIP_ID);
        if (!res || !res.data) return;

        const data = res.data;
        const categories = data.by_category || {};

        // Render Chart.js Pie Chart
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Transport', 'Stay', 'Activities', 'Meals', 'Other'],
                datasets: [{
                    data: [
                        categories.transport || 0,
                        categories.stay || 0,
                        categories.activities || 0,
                        categories.meals || 0,
                        categories.other || 0
                    ],
                    backgroundColor: ['#0ea5e9', '#6366f1', '#10b981', '#f59e0b', '#ec4899']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

    } catch (err) {
        console.error('Failed to load budget data:', err);
    }
}

function initAddExpenseForm() {
    const form = document.getElementById('addExpenseForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const category = document.getElementById('expCategory').value;
        const amount = parseFloat(document.getElementById('expAmount').value);
        const description = document.getElementById('expDescription').value;
        const spentOn = document.getElementById('expSpentOn').value;

        if (!category || isNaN(amount) || amount <= 0) {
            toast('Please enter a valid amount and category', 'error');
            return;
        }

        try {
            const res = await api('POST', '/api/budget.php?trip_id=' + TRIP_ID, {
                category: category,
                amount: amount,
                description: description,
                spent_on: spentOn || null
            });

            if (res && res.success) {
                toast('Expense recorded!', 'success');
                location.reload();
            } else {
                toast(res.error || 'Failed to add expense', 'error');
            }
        } catch (err) {
            toast(err.message || 'Error adding expense', 'error');
        }
    });

    document.getElementById('btnAutoFillExpense')?.addEventListener('click', function() {
        const sampleExpenses = [
            { category: 'meals', amount: 48.50, desc: 'Traditional Bistro Lunch & Wine' },
            { category: 'transport', amount: 35.00, desc: 'Express Train Pass' },
            { category: 'stay', amount: 165.00, desc: 'Boutique Hotel Night 1' },
            { category: 'other', amount: 25.00, desc: 'Museum Entry & Audio Guide' }
        ];
        const choice = sampleExpenses[Math.floor(Math.random() * sampleExpenses.length)];
        const catEl = document.getElementById('expCategory');
        const amtEl = document.getElementById('expAmount');
        const descEl = document.getElementById('expDescription');
        if (catEl) catEl.value = choice.category;
        if (amtEl) amtEl.value = choice.amount;
        if (descEl) descEl.value = choice.desc;
        if (typeof toast === 'function') {
            toast('Expense details auto-filled!', 'success');
        }
    });

    document.querySelectorAll('.delete-expense-btn').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const expId = btn.dataset.id;
            if (confirm('Delete this expense entry?')) {
                try {
                    const res = await api('DELETE', '/api/budget.php?id=' + expId);
                    if (res && res.success) {
                        toast('Expense deleted', 'success');
                        btn.closest('tr').remove();
                        location.reload();
                    }
                } catch (err) {
                    toast(err.message || 'Error deleting expense', 'error');
                }
            }
        });
    });
}
