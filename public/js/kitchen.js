const API_BASE = '/api';
let token = localStorage.getItem('kitchen_token');
let pollInterval = null;

if (token) {
    showKitchen();
}

async function login() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    try {
        const res = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        if (res.ok) {
            const data = await res.json();
            token = data.token;
            localStorage.setItem('kitchen_token', token);
            showKitchen();
        } else {
            alert('Login failed');
        }
    } catch (e) {
        console.error('Login error', e);
    }
}

function logout() {
    localStorage.removeItem('kitchen_token');
    token = null;
    clearInterval(pollInterval);
    document.getElementById('kitchen-view').classList.add('hidden');
    document.getElementById('kitchen-view').classList.remove('active');
    document.getElementById('login-view').classList.add('active');
    document.getElementById('login-view').classList.remove('hidden');
}

function showKitchen() {
    document.getElementById('login-view').classList.add('hidden');
    document.getElementById('login-view').classList.remove('active');
    document.getElementById('kitchen-view').classList.add('active');
    document.getElementById('kitchen-view').classList.remove('hidden');
    
    fetchOrders();
    pollInterval = setInterval(fetchOrders, 5000); // Poll every 5s
}

async function fetchOrders() {
    try {
        const res = await fetch(`${API_BASE}/kitchen/orders`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (res.status === 401 || res.status === 403) {
            logout();
            return;
        }
        
        const data = await res.json();
        renderOrders(data.data);
    } catch (e) {
        console.error('Fetch orders error', e);
    }
}

function renderOrders(orders) {
    const grid = document.getElementById('order-grid');
    grid.innerHTML = '';
    
    orders.forEach(order => {
        const card = document.createElement('div');
        card.className = `order-card ${order.status}`;
        
        const itemsList = order.items.map(item => `<li>${item.quantity}x ${item.item_name}</li>`).join('');
        
        const timeSince = Math.floor((new Date() - new Date(order.created_at)) / 60000);
        
        card.innerHTML = `
            <div class="order-header">
                <span>Order #${order.id} (${order.table_id})</span>
                <span>${timeSince}m ago</span>
            </div>
            <ul class="order-items">
                ${itemsList}
            </ul>
            <div class="controls">
                <select id="status-${order.id}">
                    <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="preparing" ${order.status === 'preparing' ? 'selected' : ''}>Preparing</option>
                    <option value="ready" ${order.status === 'ready' ? 'selected' : ''}>Ready to Serve</option>
                    <option value="served" ${order.status === 'served' ? 'selected' : ''}>Served</option>
                </select>
                <input type="number" id="wait-${order.id}" value="${order.estimated_wait_minutes}" placeholder="Wait time (mins)">
                <button class="btn primary" onclick="updateOrder(${order.id})">Update</button>
            </div>
        `;
        
        grid.appendChild(card);
    });
}

async function updateOrder(id) {
    const status = document.getElementById(`status-${id}`).value;
    const estimated_wait_minutes = parseInt(document.getElementById(`wait-${id}`).value, 10);
    
    try {
        await fetch(`${API_BASE}/kitchen/orders/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ status, estimated_wait_minutes })
        });
        fetchOrders();
    } catch (e) {
        console.error('Update failed', e);
    }
}
