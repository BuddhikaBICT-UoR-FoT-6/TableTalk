const API_BASE = '/api';
let tableId = 'Table ' + Math.floor(Math.random() * 20 + 1); // Mock table ID
let token = null;
let currentOrder = null;
let pollInterval = null;

const cart = {};

// Elements
const viewMenu = document.getElementById('view-menu');
const viewStatus = document.getElementById('view-status');
const viewPayment = document.getElementById('view-payment');
const viewFeedback = document.getElementById('view-feedback');
const viewThanks = document.getElementById('view-thanks');

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('table-number').textContent = tableId;
    initSession();
});

async function initSession() {
    try {
        const res = await fetch(`${API_BASE}/auth/table`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table_id: tableId })
        });
        const data = await res.json();
        token = data.token;
        loadMenu();
    } catch (e) {
        console.error('Session init failed', e);
    }
}

async function loadMenu() {
    try {
        const res = await fetch(`${API_BASE}/menu`);
        const data = await res.json();
        renderMenu(data.data);
    } catch (e) {
        console.error('Menu load failed', e);
    }
}

function renderMenu(items) {
    const grid = document.getElementById('menu-grid');
    grid.innerHTML = '';
    
    items.forEach(item => {
        cart[item.id] = { ...item, qty: 0 };
        
        const el = document.createElement('div');
        el.className = 'menu-item';
        el.innerHTML = `
            ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}">` : ''}
            <div class="menu-item-info">
                <h3>${item.name}</h3>
                <p>${item.description}</p>
                <div class="price">$${item.price}</div>
                <div class="quantity-control">
                    <button class="qty-btn minus" onclick="updateQty(${item.id}, -1)">-</button>
                    <span id="qty-${item.id}">0</span>
                    <button class="qty-btn plus" onclick="updateQty(${item.id}, 1)">+</button>
                </div>
            </div>
        `;
        grid.appendChild(el);
    });
}

window.updateQty = function(id, change) {
    const newQty = Math.max(0, cart[id].qty + change);
    cart[id].qty = newQty;
    document.getElementById(`qty-${id}`).textContent = newQty;
    updateCartSummary();
};

function updateCartSummary() {
    let total = 0;
    let itemsCount = 0;
    
    Object.values(cart).forEach(item => {
        if (item.qty > 0) {
            total += item.qty * item.price;
            itemsCount += item.qty;
        }
    });
    
    document.getElementById('cart-total').textContent = `$${total.toFixed(2)}`;
    const btn = document.getElementById('btn-place-order');
    btn.disabled = itemsCount === 0;
}

document.getElementById('btn-place-order').addEventListener('click', async () => {
    const items = Object.values(cart)
        .filter(i => i.qty > 0)
        .map(i => ({ menu_item_id: i.id, quantity: i.qty }));

    try {
        const res = await fetch(`${API_BASE}/orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ items })
        });
        const data = await res.json();
        if (data.order_id) {
            currentOrder = data;
            showView('status');
            startPolling();
        }
    } catch (e) {
        console.error('Order placement failed', e);
    }
});

function startPolling() {
    updateStatusUI();
    pollInterval = setInterval(async () => {
        try {
            const res = await fetch(`${API_BASE}/orders/${currentOrder.order_id}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();
            currentOrder = {
                ...currentOrder,
                status: data.data.status,
                estimated_wait_minutes: data.data.estimated_wait_minutes,
                total_amount: data.data.total_amount
            };
            updateStatusUI();
            
            if (currentOrder.status === 'served') {
                clearInterval(pollInterval);
                showView('payment');
                document.getElementById('payment-amount').textContent = `$${currentOrder.total_amount}`;
            }
        } catch (e) {
            console.error('Poll failed', e);
        }
    }, 3000);
}

function updateStatusUI() {
    document.getElementById('current-status').textContent = currentOrder.status;
    document.getElementById('wait-time').textContent = currentOrder.estimated_wait_minutes;
    
    const spinner = document.getElementById('status-spinner');
    if (['ready', 'served', 'paid'].includes(currentOrder.status)) {
        spinner.style.borderTopColor = 'var(--success-color)';
        spinner.style.animation = 'none';
    } else {
        spinner.style.borderTopColor = 'var(--primary-color)';
        spinner.style.animation = 'spin 1s linear infinite';
    }
}

document.querySelectorAll('.payment-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        const method = e.target.dataset.method;
        try {
            const res = await fetch(`${API_BASE}/payments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    order_id: currentOrder.order_id,
                    amount: currentOrder.total_amount,
                    method
                })
            });
            
            if (res.ok) {
                showView('feedback');
            }
        } catch (err) {
            console.error('Payment failed', err);
        }
    });
});

let rating = 5;
document.querySelectorAll('.stars span').forEach(star => {
    star.addEventListener('click', (e) => {
        rating = parseInt(e.target.dataset.value);
        document.querySelectorAll('.stars span').forEach(s => {
            if (parseInt(s.dataset.value) <= rating) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    });
});

document.getElementById('btn-submit-feedback').addEventListener('click', async () => {
    const comment = document.getElementById('feedback-comment').value;
    try {
        await fetch(`${API_BASE}/feedback`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                order_id: currentOrder.order_id,
                rating,
                comment
            })
        });
        showView('thanks');
    } catch (e) {
        console.error('Feedback failed', e);
        showView('thanks'); // move forward anyway
    }
});

document.getElementById('btn-new-order').addEventListener('click', () => {
    currentOrder = null;
    Object.keys(cart).forEach(id => cart[id].qty = 0);
    renderMenu(Object.values(cart));
    updateCartSummary();
    showView('menu');
});

function showView(viewId) {
    document.querySelectorAll('.view').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('active');
    });
    const target = document.getElementById(`view-${viewId}`);
    target.classList.remove('hidden');
    target.classList.add('active');
}
