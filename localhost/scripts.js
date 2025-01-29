// Функция для загрузки корзины
function loadBasket() {
    // Отправляем GET запрос к get_basket.php для получения данных корзины
    fetch('get_basket.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Данные корзины:', data);
            const basketList = document.getElementById('basket-list');
            
            if (!basketList) {
                console.error('Элемент корзины не найден');
                return;
            }

            if (data.error) {
                basketList.innerHTML = `<p class="error-message">Ошибка: ${data.error}</p>`;
                return;
            }
            
            if (!data.items || data.items.length === 0) {
                basketList.innerHTML = '<p class="empty-basket">Корзина пуста</p>';
                return;
            }

            // Создаем HTML структуру корзины
            let html = `
                <div class="basket-container">
                    <table class="basket-table">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Количество</th>
                                <th>Стоимость</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            let totalSum = 0;
            
            // Добавляем каждый товар в таблицу
            data.items.forEach(item => {
                const itemTotal = parseFloat(item.stoimost);
                totalSum += itemTotal;
                
                html += `
                    <tr>
                        <td>${item.name}</td>
                        <td>
                            <div class="quantity-controls">
                                <button onclick="updateQuantity(${item.id}, ${item.kol_vo - 1})" 
                                        ${item.kol_vo <= 1 ? 'disabled' : ''}>-</button>
                                <span>${item.kol_vo}</span>
                                <button onclick="updateQuantity(${item.id}, ${item.kol_vo + 1})"
                                        ${item.kol_vo >= item.stock ? 'disabled' : ''}>+</button>
                            </div>
                        </td>
                        <td>${itemTotal.toFixed(2)} ₽</td>
                        <td>
                            <button onclick="removeFromBasket(${item.id})" class="remove-button">
                                Удалить
                            </button>
                        </td>
                    </tr>`;
            });
            
            // Добавляем итоговую сумму и кнопку оформления заказа
            html += `
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="2"><strong>Итого:</strong></td>
                                <td colspan="2"><strong>${totalSum.toFixed(2)} ₽</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="order-button-container">
                        <button onclick="placeOrder()" class="order-button">Оформить заказ</button>
                    </div>
                </div>`;
            
            basketList.innerHTML = html;
        })
        .catch(error => {
            console.error('Ошибка загрузки корзины:', error);
            const basketList = document.getElementById('basket-list');
            if (basketList) {
                basketList.innerHTML = '<p class="error-message">Ошибка при загрузке корзины</p>';
            }
        });
}

// Функция для загрузки списка товаров
function loadProducts() {
    // Отправляем GET запрос к get_product.php для получения списка товаров
    fetch('get_product.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Данные товаров:', data);
            const productList = document.getElementById('product-list');
            
            if (!productList) {
                console.error('Элемент списка товаров не найден');
                return;
            }

            if (!data || data.length === 0) {
                productList.innerHTML = '<p class="empty-products">Товары не найдены</p>';
                return;
            }

            // Создаем сетку товаров
            let html = '<div class="products-grid">';
            data.forEach(product => {
                html += `
                    <div class="product-card">
                        <h3>${product.Name}</h3>
                        <p>Цена: ${product.Price} ₽</p>
                        <p>В наличии: ${product.QuantityStock}</p>
                        <button onclick="addToBasket(${product.ProductID})" 
                                ${product.QuantityStock <= 0 ? 'disabled' : ''}>
                            ${product.QuantityStock <= 0 ? 'Нет в наличии' : 'В корзину'}
                        </button>
                    </div>`;
            });
            html += '</div>';
            productList.innerHTML = html;
        })
        .catch(error => {
            console.error('Ошибка загрузки товаров:', error);
            const productList = document.getElementById('product-list');
            if (productList) {
                productList.innerHTML = '<p class="error-message">Ошибка при загрузке товаров</p>';
            }
        });
}

// Функция обновления количества товара
function updateQuantity(productId, newQuantity) {
    if (newQuantity < 1) return;
    
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', newQuantity);

    fetch('basket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadBasket(); // Перезагружаем корзину после успешного обновления
        } else {
            alert(data.error || 'Ошибка при обновлении количества');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при обновлении количества');
    });
}

// Функция удаления товара из корзины
function removeFromBasket(productId) {
    if (!confirm('Вы уверены, что хотите удалить этот товар из корзины?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    fetch('basket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadBasket(); // Перезагружаем корзину после успешного удаления
        } else {
            alert(data.error || 'Ошибка при удалении товара');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при удалении товара');
    });
}

// Функция добавления товара в корзину
function addToBasket(productId) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', 1); // По умолчанию добавляем 1 единицу товара

    fetch('basket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadBasket(); // Перезагружаем корзину после успешного добавления
            alert('Товар добавлен в корзину');
        } else {
            alert(data.error || 'Ошибка при добавлении товара в корзину');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при добавлении товара в корзину');
    });
}

// Функция оформления заказа
function placeOrder() {
    if (!confirm('Вы уверены, что хотите оформить заказ?')) {
        return;
    }

    fetch('place_order.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Заказ успешно оформлен!');
                loadBasket(); // Перезагружаем корзину
            } else {
                alert(data.error || 'Ошибка при оформлении заказа');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Ошибка при оформлении заказа');
        });
}

// Загружаем корзину и товары при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM загружен, начинаем загрузку данных...');
    loadBasket();
    loadProducts();
});