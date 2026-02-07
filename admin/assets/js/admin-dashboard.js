new Chart(document.getElementById('ordersChart'), {
    type: 'line',
    data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{
            data: [3,8,4,6,10,7,9],
            borderColor: '#fb7185',
            backgroundColor: 'rgba(251,113,133,0.2)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending','Processing','Delivered'],
        datasets: [{
            data: [0,0,0],
            backgroundColor: ['#fbbf24','#60a5fa','#34d399']
        }]
    }
});
