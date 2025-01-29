// scripts_own.js
document.addEventListener('DOMContentLoaded', function() {
    // Отображение текущей даты
    const currentDate = new Date().toLocaleDateString();
    document.getElementById('current-date').textContent = currentDate;

    // Обработка формы для получения прибыли
    document.getElementById('profit-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        fetchProfitData(startDate, endDate);
    });

    // Обработка формы для получения рейтинга товаров
    document.getElementById('rating-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const startDate = document.getElementById('rating-start-date').value;
        const endDate = document.getElementById('rating-end-date').value;
        fetchRatingData(startDate, endDate);
    });

    // Обработка кнопки выхода
    document.getElementById('logout-button').addEventListener('click', function() {
        window.location.href = '/';
    });
});

function fetchProfitData(startDate, endDate) {
    fetch(`/get-profit?start=${startDate}&end=${endDate}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('profit-result').innerHTML = `Прибыль за период: ${data.profit}`;
        })
        .catch(error => console.error('Error fetching profit data:', error));
}

function fetchRatingData(startDate, endDate) {
    fetch(`/get-rating?start=${startDate}&end=${endDate}`)
        .then(response => response.json())
        .then(data => {
            let ratingList = '<ul>';
            data.rating.forEach(item => {
                ratingList += `<li>${item.name}: ${item.profit}</li>`;
            });
            ratingList += '</ul>';
            document.getElementById('rating-result').innerHTML = `Рейтинг товаров: ${ratingList}`;
        })
        .catch(error => console.error('Error fetching rating data:', error));
}