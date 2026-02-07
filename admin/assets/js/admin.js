function toggleSidebar() {
    document.querySelector(".sidebar").classList.toggle("collapsed");
}

new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{
            label: 'Sales',
            data: [12,15,9,18,22,25,24],
            fill: true
        }]
    }
});

new Chart(document.getElementById('orderChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending','Processing','Delivered'],
        datasets: [{ data: [40,25,35] }]
    }
});
