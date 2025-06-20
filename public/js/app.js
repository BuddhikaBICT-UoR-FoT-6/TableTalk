const API_BASE = '/api';

// Theme Initialization
const savedTheme = localStorage.getItem('theme') || 'dark';
document.documentElement.setAttribute('data-bs-theme', savedTheme);
window.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btn-theme-toggle');
    if(btn) btn.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
});

// UI Utility
function setLoading(btnId, isLoading, originalText = '') {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    if (isLoading) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`;
    } else {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

let tableId = null;
let role = null; // 'table', 'chef', 'admin'
let token = null;
let currentOrders = [];
let payingOrder = null;
let pollInterval = null;
let messagePollInterval = null;
let currentChatTableId = null; // For chef chat

const cart = {};

// Bootstrap Elements
let messageToast;
let systemToastInstance;
let chatOffcanvas;
let cartModalInstance = null;
let editDishModal;

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Bootstrap components
    messageToast = new bootstrap.Toast(document.getElementById('messageToast'));
    systemToastInstance = new bootstrap.Toast(document.getElementById('systemToast'));
    chatOffcanvas = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));
    cartModalInstance = new bootstrap.Modal(document.getElementById('cartModal'));
    editDishModal = new bootstrap.Modal(document.getElementById('editDishModal'));

    // Login listeners
    document.getElementById('btn-login-table').addEventListener('click', loginTable);
    document.getElementById('btn-login-staff').addEventListener('click', loginStaff);
    document.getElementById('btn-logout').addEventListener('click', logout);
    document.getElementById('btn-theme-toggle')?.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-bs-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        localStorage.setItem('theme', next);
        document.getElementById('btn-theme-toggle').textContent = next === 'dark' ? '☀️' : '🌙';
    });

    // Navigation listeners
    document.getElementById('btn-view-status').addEventListener('click', () => showView('status'));
    document.getElementById('btn-back-menu').addEventListener('click', () => showView('menu'));
    document.getElementById('btn-view-feedback-admin').addEventListener('click', loadFeedbackAdmin);
    
    // Order listeners
    document.getElementById('btn-place-order').addEventListener('click', reviewCart);
    document.getElementById('btn-confirm-order').addEventListener('click', confirmOrder);

    // Payment listeners
    document.querySelectorAll('.payment-btn').forEach(btn => {
        btn.addEventListener('click', processPayment);
    });

    // Feedback listeners
    document.querySelectorAll('.stars span').forEach(star => {
        star.addEventListener('click', setRating);
    });
    document.getElementById('btn-submit-feedback').addEventListener('click', submitFeedback);
    document.getElementById('btn-skip-feedback').addEventListener('click', () => {
        currentOrder = null;
        showView('menu');
    });

    // Chat listeners
    document.getElementById('btn-chat-send').addEventListener('click', sendChatMessage);
    document.getElementById('chat-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendChatMessage();
    });
    document.getElementById('chef-table-select').addEventListener('change', (e) => {
        currentChatTableId = e.target.value;
        if(currentChatTableId) loadChatHistory(currentChatTableId);
    });

    // Modal listeners to trigger chat history load and mark as read
    document.getElementById('chatOffcanvas').addEventListener('shown.bs.offcanvas', () => {
        document.getElementById('chat-badge').classList.add('d-none');
        if (role === 'table') {
            loadChatHistory(tableId);
        } else if (role === 'chef') {
            if (currentChatTableId) loadChatHistory(currentChatTableId);
        }
    });

    checkExistingSession();
});

function checkExistingSession() {
    const savedToken = localStorage.getItem('tabletalk_token');
    const savedRole = localStorage.getItem('tabletalk_role');
    const savedTable = localStorage.getItem('tabletalk_tableId');

    if (savedToken && savedRole) {
        token = savedToken;
        role = savedRole;
        
        setupRoleHeader();

        if (role === 'table' && savedTable) {
            tableId = savedTable;
            document.getElementById('table-info').textContent = `Table ${tableId}`;
            document.getElementById('table-info').classList.remove('d-none');
            document.getElementById('btn-floating-chat').classList.remove('d-none');
            resumeOrLoadMenu();
            startMessagePolling();
        } else if (role === 'chef') {
            document.getElementById('btn-floating-chat').classList.remove('d-none');
            document.getElementById('chef-table-select').classList.remove('d-none');
            loadKitchen();
            startMessagePolling();
        } else if (role === 'admin') {
            loadAdmin();
        }
    }
}

async function resumeOrLoadMenu() {
    try {
        const res = await fetch(`${API_BASE}/orders/active`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        
        if (data.data && data.data.length > 0) {
            currentOrders = data.data;
            document.getElementById('btn-view-status').classList.remove('d-none');
            startPolling();
            showView('status');
        } else {
            loadMenu();
        }
    } catch (e) {
        console.error('Failed to check active orders', e);
        loadMenu();
    }
}

async function loginTable() {
    const tId = document.getElementById('input-table-id').value;
    if (!tId) return showNotification("Please enter a table number", "warning");
    
    tableId = tId;

    try {
        const res = await fetch(`${API_BASE}/auth/table`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table_id: tableId })
        });
        const data = await res.json();
        token = data.token;
        role = 'table';
        
        localStorage.setItem('tabletalk_token', token);
        localStorage.setItem('tabletalk_role', role);
        localStorage.setItem('tabletalk_tableId', tableId);

        setupRoleHeader();
        document.getElementById('table-info').textContent = `Table ${tableId}`;
        document.getElementById('table-info').classList.remove('d-none');
        document.getElementById('btn-floating-chat').classList.remove('d-none');
        
        resumeOrLoadMenu();
        startMessagePolling();
    } catch (e) {
        console.error('Session init failed', e);
    }
}

async function loginStaff() {
    const email = document.getElementById('input-staff-email').value;
    const password = document.getElementById('input-staff-pass').value;

    try {
        const res = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        if (data.token) {
            token = data.token;
            role = data.user.role; // 'chef' or 'admin'
            
            localStorage.setItem('tabletalk_token', token);
            localStorage.setItem('tabletalk_role', role);

            setupRoleHeader();
            if (role === 'chef') {
                document.getElementById('btn-floating-chat').classList.remove('d-none');
                document.getElementById('chef-table-select').classList.remove('d-none');
                loadKitchen();
                startMessagePolling();
            } else if (role === 'admin') {
                loadAdmin();
            }
        } else {
            showNotification('Login failed', 'danger');
            setLoading('btn-login-staff', false, 'Login');
            setLoading('btn-login-table', false, 'Start');
        }
    } catch (e) {
        console.error('Login failed', e);
        setLoading('btn-login-table', false, 'Start');
    }
}

function logout() {
    localStorage.clear();
    token = null;
    role = null;
    tableId = null;
    currentOrders = [];
    currentChatTableId = null;
    
    if (pollInterval) clearInterval(pollInterval);
    if (messagePollInterval) clearInterval(messagePollInterval);

    document.getElementById('role-badge').classList.add('d-none');
    document.getElementById('table-info').classList.add('d-none');
    document.getElementById('btn-logout').classList.add('d-none');
    document.getElementById('btn-view-status').classList.add('d-none');
    document.getElementById('btn-view-feedback-admin').classList.add('d-none');
    document.getElementById('btn-floating-chat').classList.add('d-none');
    document.getElementById('chef-table-select').classList.add('d-none');
    
    showView('login');
}

function setupRoleHeader() {
    const badge = document.getElementById('role-badge');
    badge.textContent = role === 'chef' ? '👨‍🍳 Kitchen' : (role === 'admin' ? '👑 Admin' : '🍽️ Customer');
    badge.classList.remove('d-none');
    document.getElementById('btn-logout').classList.remove('d-none');

    if (role === 'admin' || role === 'chef') {
        document.getElementById('btn-view-feedback-admin').classList.remove('d-none');
    }
}

let allMenuItems = [];

// -----------------------------------------------------------------------------
// CUSTOMER FLOW
// -----------------------------------------------------------------------------

async function loadMenu() {
    showView('menu');
    try {
        const res = await fetch(`${API_BASE}/menu`);
        const data = await res.json();
        allMenuItems = data.data;
        renderMenu(allMenuItems);
    } catch (e) {
        console.error('Menu load failed', e);
    }
}

window.filterMenu = function(category, btnElement) {
    // Update active tab styling
    document.querySelectorAll('#menu-categories .nav-link').forEach(btn => {
        btn.classList.remove('active', 'text-light', 'fw-bold');
        btn.classList.add('text-muted');
    });
    btnElement.classList.remove('text-muted');
    btnElement.classList.add('active', 'text-light', 'fw-bold');

    if (category === 'All') {
        renderMenu(allMenuItems);
    } else {
        const filtered = allMenuItems.filter(i => i.category === category);
        renderMenu(filtered);
    }
};

function renderMenu(items) {
    const grid = document.getElementById('menu-grid');
    grid.innerHTML = '';
    
    if (items.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center text-muted mt-5">No items found in this category.</div>';
        return;
    }

    items.forEach(item => {
        if(!cart[item.id]) cart[item.id] = { ...item, qty: 0 };
        const cItem = cart[item.id];
        
        let ratingHtml = '';
        if (item.rating) {
            ratingHtml = `<span class="badge bg-warning text-dark ms-2 mb-2">⭐ ${item.rating}</span>`;
        }
        
        const el = document.createElement('div');
        el.className = 'col-12 col-md-6 col-lg-4';
        el.innerHTML = `
            <div class="menu-item h-100 d-flex flex-column">
                ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}">` : ''}
                <div class="p-3 flex-grow-1 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start">
                        <h3 class="h5 fw-bold mb-1">${item.name}</h3>
                        ${ratingHtml}
                    </div>
                    <p class="text-muted small mb-3 flex-grow-1">${item.description}</p>
                    <div class="fw-bold fs-4 text-white mb-3">$${item.price}</div>
                    
                    <div class="quantity-control d-flex justify-content-between align-items-center mt-auto">
                        <button class="qty-btn" onclick="updateQty(${item.id}, -1)">-</button>
                        <span id="qty-${item.id}" class="fw-bold fs-5">${cItem.qty}</span>
                        <button class="qty-btn text-primary" onclick="updateQty(${item.id}, 1)">+</button>
                    </div>
                </div>
            </div>
        `;
        grid.appendChild(el);
    });
    
    updateCartSummary();
}

window.updateQty = function(id, change) {
    const newQty = Math.max(0, cart[id].qty + change);
    cart[id].qty = newQty;
    // Only update DOM if the element is currently visible in the active tab
    const el = document.getElementById(`qty-${id}`);
    if (el) el.textContent = newQty;
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
    const summary = document.getElementById('cart-summary');
    
    btn.disabled = itemsCount === 0;
    
    if (itemsCount > 0) {
        summary.classList.remove('d-none');
    } else if (currentOrders.length === 0) {
        summary.classList.add('d-none');
    }
}

function reviewCart() {
    const items = Object.values(cart).filter(i => i.qty > 0);
    const list = document.getElementById('cart-review-items');
    
    let total = 0;
    list.innerHTML = items.map(item => {
        const sub = item.qty * item.price;
        total += sub;
        return `
            <div class="d-flex justify-content-between align-items-center">
                <span><span class="fw-bold me-2">${item.qty}x</span> ${item.name}</span>
                <span>$${sub.toFixed(2)}</span>
            </div>
        `;
    }).join('');
    
    document.getElementById('cart-modal-total').textContent = `$${total.toFixed(2)}`;
    document.getElementById('order-notes').value = '';
    
    cartModalInstance.show();
}

async function confirmOrder() {
    const items = Object.values(cart)
        .filter(i => i.qty > 0)
        .map(i => ({ menu_item_id: i.id, quantity: i.qty }));
        
    const notes = document.getElementById('order-notes').value.trim();

    try {
        const btn = document.getElementById('btn-confirm-order');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        const res = await fetch(`${API_BASE}/orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ items, notes })
        });
        const data = await res.json();
        
        btn.disabled = false;
        btn.textContent = 'Confirm Order';
        
        if (data.order_id) {
            cartModalInstance.hide();
            
            // Re-fetch all active orders
            await checkActiveOrders();
            
            // Reset cart
            Object.keys(cart).forEach(id => cart[id].qty = 0);
            updateCartSummary();
            renderMenu(allMenuItems); 
            
            // Reset categories to all
            document.querySelector('#menu-categories .nav-link').click();
            
            document.getElementById('btn-view-status').classList.remove('d-none');
            
            showView('status');
            startPolling();
        }
    } catch (e) {
        console.error('Order placement failed', e);
        document.getElementById('btn-confirm-order').disabled = false;
    }
}

async function checkActiveOrders() {
    try {
        const res = await fetch(`${API_BASE}/orders/active`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        currentOrders = data.data || [];
        updateStatusUI();
    } catch(e) { console.error(e); }
}

function startPolling() {
    if (pollInterval) clearInterval(pollInterval);
    updateStatusUI();
    
    pollInterval = setInterval(async () => {
        try {
            const res = await fetch(`${API_BASE}/orders/active`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();
            
            if (data.data && data.data.length > 0) {
                currentOrders = data.data;
            } else {
                currentOrders = [];
                clearInterval(pollInterval);
                document.getElementById('btn-view-status').classList.add('d-none');
                if (document.getElementById('view-status').classList.contains('active')) {
                    showView('menu');
                }
            }
            
            if(document.getElementById('view-status').classList.contains('active')) {
                updateStatusUI();
            }
        } catch (e) {
            console.error('Poll failed', e);
        }
    }, 3000);
}

function updateStatusUI() {
    const container = document.getElementById('active-orders-container');
    
    if (!currentOrders || currentOrders.length === 0) {
        container.innerHTML = '<p class="text-muted">No active orders.</p>';
        return;
    }
    
    container.innerHTML = currentOrders.map(order => {
        const isReadyOrServed = ['ready', 'served', 'paid'].includes(order.status);
        const spinnerClass = isReadyOrServed ? 'text-success' : 'text-primary';
        const spinnerStyle = isReadyOrServed ? 'animation: none; border-color: var(--success-color);' : 'animation: spin 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;';
        
        const itemsList = order.items ? order.items.map(item => `
            <div class="d-flex justify-content-between text-muted small mb-1">
                <span>${item.quantity}x ${item.item_name || 'Item'}</span>
                <span>$${item.subtotal}</span>
            </div>
        `).join('') : '';

        const payButton = order.status === 'served' ? 
            `<button class="btn btn-success w-100 mt-3 fw-bold" onclick="showPaymentForOrder(${order.id})">Pay Now ($${order.total_amount})</button>` : '';

        return `
            <div class="glass-card text-center w-100" style="max-width: 500px;">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-subtle pb-2">
                    <span class="text-muted small">Order #${order.id}</span>
                    <span class="badge ${isReadyOrServed ? 'bg-success' : 'bg-primary'}">${order.status.toUpperCase()}</span>
                </div>
                <div class="status-indicator mb-3">
                    <div class="spinner-border custom-spinner mb-2 ${spinnerClass}" role="status" style="width: 3rem; height: 3rem; border-width: 0.3rem; ${spinnerStyle}"></div>
                    <h3 class="gradient-text display-6 fw-bold text-capitalize m-0">${order.status}</h3>
                </div>
                
                <div class="wait-time-badge mb-3">
                    <span class="text-muted d-block mb-1 small">Estimated Wait</span>
                    <span class="fs-2 fw-bold">${order.estimated_wait_minutes}</span> <span class="text-muted small">mins</span>
                </div>
                
                <div class="text-start mt-3 border-top border-subtle pt-2">
                    ${itemsList}
                    <div class="d-flex justify-content-between fw-bold mt-2 pt-2 border-top border-subtle">
                        <span>Total:</span>
                        <span>$${order.total_amount}</span>
                    </div>
                </div>
                ${payButton}
            </div>
        `;
    }).join('');
}

window.showPaymentForOrder = function(orderId) {
    payingOrder = currentOrders.find(o => o.id == orderId);
    if (!payingOrder) return;
    
    document.getElementById('payment-amount').textContent = `$${payingOrder.total_amount}`;
    showView('payment');
};

function processPayment(e) {
    const method = e.currentTarget.dataset.method;
    
    // Simulate payment process
    document.getElementById('payment-header').classList.add('d-none');
    document.getElementById('payment-methods-container').classList.add('d-none');
    document.getElementById('payment-processing').classList.remove('d-none');
    
    // Fake progress steps
    const instructions = ['Contacting bank...', 'Verifying card...', 'Authorizing...', 'Payment Approved!'];
    let step = 0;
    
    const simInterval = setInterval(() => {
        document.getElementById('payment-instruction').textContent = instructions[step];
        step++;
        if (step >= instructions.length) {
            clearInterval(simInterval);
            finishPayment(method);
        }
    }, 800);
}

let feedbackTimeout = null;

async function finishPayment(method) {
    try {
        const res = await fetch(`${API_BASE}/payments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                order_id: payingOrder.id,
                amount: payingOrder.total_amount,
                method: method
            })
        });
        
        if (res.ok) {
            document.getElementById('payment-processing').classList.add('d-none');
            document.getElementById('payment-success').classList.remove('d-none');
            
            // Refetch active orders to see if any remain
            await checkActiveOrders();
            
            document.getElementById('btn-proceed-feedback').onclick = () => {
                // Reset payment UI
                document.getElementById('payment-header').classList.remove('d-none');
                document.getElementById('payment-methods-container').classList.remove('d-none');
                document.getElementById('payment-success').classList.add('d-none');
                showView('feedback');

                // Auto skip after 10 seconds
                if (feedbackTimeout) clearTimeout(feedbackTimeout);
                feedbackTimeout = setTimeout(() => {
                    if(document.getElementById('view-feedback').classList.contains('active')) {
                        document.getElementById('btn-skip-feedback').click();
                    }
                }, 10000);
            };

            document.getElementById('btn-send-receipt').onclick = async () => {
                const email = document.getElementById('receipt-email').value;
                const status = document.getElementById('receipt-status');
                if(!email) return;
                
                const btn = document.getElementById('btn-send-receipt');
                btn.disabled = true;
                
                try {
                    const res = await fetch(`${API_BASE}/receipt`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`
                        },
                        body: JSON.stringify({ email, order_id: payingOrder.id })
                    });
                    if (res.ok) {
                        status.textContent = 'Receipt sent!';
                        document.getElementById('receipt-email').value = '';
                    } else {
                        status.textContent = 'Failed to send receipt.';
                        status.classList.replace('text-success', 'text-danger');
                    }
                } catch(e) {
                    status.textContent = 'Error sending receipt.';
                    status.classList.replace('text-success', 'text-danger');
                }
                btn.disabled = false;
            };
        } else {
            showNotification("Payment failed", "danger");
            document.getElementById('payment-header').classList.remove('d-none');
            document.getElementById('payment-processing').classList.add('d-none');
            document.getElementById('payment-methods-container').classList.remove('d-none');
        }
    } catch (err) {
        console.error('Payment failed', err);
        showNotification("Payment failed", "danger");
        document.getElementById('payment-header').classList.remove('d-none');
        document.getElementById('payment-processing').classList.add('d-none');
        document.getElementById('payment-methods-container').classList.remove('d-none');
    }
}

// -----------------------------------------------------------------------------
// FEEDBACK
// -----------------------------------------------------------------------------
let selectedRating = 0;

function setRating(e) {
    selectedRating = parseInt(e.target.dataset.val);
    document.querySelectorAll('.stars span').forEach(star => {
        if (parseInt(star.dataset.val) <= selectedRating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

async function submitFeedback() {
    if (selectedRating === 0) return showNotification('Please select a star rating', 'warning');
    const comment = document.getElementById('feedback-comment').value;

    let finalComment = comment.trim();
    if (!finalComment) {
        const defaults = {
            1: "Very dissatisfied with the experience.",
            2: "Below average experience.",
            3: "Average experience.",
            4: "Good experience, but room for improvement.",
            5: "Excellent food and service!"
        };
        finalComment = defaults[selectedRating] || "No comment provided.";
    }

    try {
        setLoading('btn-submit-feedback', true, 'Submitting...');
        const res = await fetch(`${API_BASE}/feedback`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                order_id: payingOrder.id,
                rating: selectedRating,
                comment: finalComment
            })
        });
        
        setLoading('btn-submit-feedback', false, 'Submit Feedback');
        showNotification('Thank you for your feedback!', 'success');
        
        // Cleanup and back to menu
        currentOrder = null;
        selectedRating = 0;
        document.querySelectorAll('.stars span').forEach(s => s.classList.remove('active'));
        document.getElementById('feedback-comment').value = '';
        showView('menu');
    } catch (e) {
        console.error('Feedback failed', e);
    }
}

// -----------------------------------------------------------------------------
// TWO-WAY MESSAGING
// -----------------------------------------------------------------------------
const shownToastIds = new Set();

function startMessagePolling() {
    if (messagePollInterval) clearInterval(messagePollInterval);
    
    messagePollInterval = setInterval(async () => {
        try {
            const url = role === 'table' ? `${API_BASE}/messages/unread` : `${API_BASE}/messages/chef/unread`;
            const res = await fetch(url, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();
            
            if (data.data && data.data.length > 0) {
                const isChatOpen = document.getElementById('chatOffcanvas').classList.contains('show');
                
                data.data.forEach(msg => {
                    if (!isChatOpen || (role === 'chef' && msg.table_id !== currentChatTableId)) {
                        if (!shownToastIds.has(msg.id)) {
                            showToastMessage(msg);
                            shownToastIds.add(msg.id);
                        }
                        document.getElementById('chat-badge').classList.remove('d-none');
                    } else {
                        // Chat is open, auto refresh
                        if (!shownToastIds.has(msg.id)) shownToastIds.add(msg.id);
                        if (role === 'table') {
                            loadChatHistory(tableId);
                        } else {
                            loadChatHistory(currentChatTableId);
                        }
                    }
                });
            }
        } catch(e) {
            console.error('Message poll failed', e);
        }
    }, 3000);
}

let toastTimeout = null;

function showToastMessage(msg) {
    const senderTitle = msg.sender === 'chef' ? '👨‍🍳 Kitchen' : `🍽️ Table ${msg.table_id}`;
    document.getElementById('toast-sender').textContent = senderTitle;
    document.getElementById('toast-message-body').textContent = msg.message;
    
    // Reset animation
    const progressBar = document.getElementById('toast-progress-bar');
    progressBar.style.animation = 'none';
    void progressBar.offsetWidth; // Trigger reflow
    progressBar.style.animation = 'toastProgress 5s linear forwards';
    
    messageToast.show();

    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        messageToast.hide();
    }, 5000);
}

async function markMessageRead(id) {
    try {
        await fetch(`${API_BASE}/messages/${id}/read`, {
            method: 'PUT',
            headers: { 'Authorization': `Bearer ${token}` }
        });
    } catch(e) { console.error('Failed to mark read', e); }
}

async function loadChatHistory(targetTableId) {
    if (!targetTableId) return;
    
    // Enable inputs
    document.getElementById('chat-input').disabled = false;
    document.getElementById('btn-chat-send').disabled = false;

    try {
        const res = await fetch(`${API_BASE}/messages/${targetTableId}/history`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        
        const chatBox = document.getElementById('chat-messages');
        if (data.data && data.data.length > 0) {
            chatBox.innerHTML = data.data.map(msg => {
                const isMine = (role === 'table' && msg.sender === 'table') || (role === 'chef' && msg.sender === 'chef');
                const bubbleClass = isMine ? 'sent' : 'received';
                
                // If it's not mine and unread, mark it read
                if (!isMine && msg.is_read == 0) {
                    markMessageRead(msg.id);
                }

                return `<div class="chat-bubble ${bubbleClass}">${msg.message}</div>`;
            }).join('');
        } else {
            chatBox.innerHTML = `<div class="text-center text-muted small mt-3">No messages yet.</div>`;
        }
        
        // Scroll to bottom
        chatBox.scrollTop = chatBox.scrollHeight;
    } catch (e) {
        console.error("Failed to load history", e);
    }
}

async function sendChatMessage() {
    const input = document.getElementById('chat-input');
    const msg = input.value.trim();
    if (!msg) return;

    let targetTable = role === 'table' ? tableId : currentChatTableId;
    if (!targetTable) return showNotification("Select a table first", "warning");

    try {
        const btn = document.getElementById('btn-chat-send');
        btn.disabled = true;

        await fetch(`${API_BASE}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ table_id: targetTable, message: msg })
        });
        
        input.value = '';
        btn.disabled = false;
        loadChatHistory(targetTable);
    } catch(e) {
        console.error('Failed to send message', e);
        document.getElementById('btn-chat-send').disabled = false;
    }
}

// -----------------------------------------------------------------------------
// KITCHEN FLOW
// -----------------------------------------------------------------------------

function loadKitchen() {
    showView('kitchen');
    fetchKitchenOrders();
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(fetchKitchenOrders, 5000);
}

async function fetchKitchenOrders() {
    try {
        const res = await fetch(`${API_BASE}/kitchen/orders`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        renderKitchen(data.data);
    } catch (e) {
        console.error('Kitchen fetch failed', e);
    }
}

function renderKitchen(orders) {
    const grid = document.getElementById('kitchen-grid');
    if (!orders || orders.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center text-muted mt-5">No active orders</div>';
        updateChefTableSelect([]);
        return;
    }

    const tableIds = new Set();

    grid.innerHTML = orders.map(order => {
        tableIds.add(order.table_id);

        let statusBadge = 'bg-secondary';
        if (order.status === 'preparing') statusBadge = 'bg-warning text-dark';
        if (order.status === 'ready') statusBadge = 'bg-success';

        let actionBtn = '';
        if (order.status === 'pending') {
            actionBtn = `<button class="btn btn-warning w-100 fw-bold" onclick="updateOrderStatus(${order.id}, 'preparing')">Start Preparing</button>`;
        } else if (order.status === 'preparing') {
            actionBtn = `<button class="btn btn-success w-100 fw-bold" onclick="updateOrderStatus(${order.id}, 'ready')">Mark Ready</button>`;
        } else if (order.status === 'ready') {
            actionBtn = `<button class="btn btn-primary w-100 fw-bold" onclick="updateOrderStatus(${order.id}, 'served')">Mark Served</button>`;
        }

        const notesHtml = order.notes ? `<div class="alert alert-warning py-1 px-2 mt-2 mb-0 small"><strong>Notes:</strong> ${order.notes}</div>` : '';

        return `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="glass-card h-100 d-flex flex-column p-4 order-card border-secondary">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h3 class="h4 fw-bold text-white mb-0">Table ${order.table_id}</h3>
                        <span class="badge ${statusBadge} text-uppercase">${order.status}</span>
                    </div>
                    
                    <div class="mb-3 text-muted small">
                        Wait Time: <strong>${order.estimated_wait_minutes} mins</strong>
                    </div>
                    
                    ${notesHtml}

                    <div class="flex-grow-1 border-top border-secondary pt-3 mb-4 mt-2">
                        ${order.items ? order.items.map(item => `
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold">${item.quantity}x</span>
                                <span class="text-white text-end ms-2">${item.item_name || 'Item'}</span>
                            </div>
                        `).join('') : '<span class="text-muted small">Items not loaded...</span>'}
                    </div>

                    <div class="mt-auto d-flex flex-column gap-2">
                        ${actionBtn}
                        <button class="btn btn-outline-info w-100" onclick="openChefChat('${order.table_id}')">💬 Open Chat</button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    updateChefTableSelect(Array.from(tableIds));
}

function updateChefTableSelect(tables) {
    const select = document.getElementById('chef-table-select');
    const currentVal = select.value;
    select.innerHTML = '<option value="">Select Table...</option>' + 
        tables.map(t => `<option value="${t}">Table ${t}</option>`).join('');
    
    if (tables.includes(currentVal)) {
        select.value = currentVal;
    }
}

window.updateOrderStatus = async function(id, newStatus) {
    try {
        await fetch(`${API_BASE}/kitchen/orders/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ status: newStatus })
        });
        fetchKitchenOrders();
    } catch (e) {
        console.error('Status update failed', e);
    }
};

window.openChefChat = function(tId) {
    document.getElementById('chef-table-select').value = tId;
    currentChatTableId = tId;
    chatOffcanvas.show();
};

window.switchChefTab = function(tab) {
    if (tab === 'orders') {
        document.getElementById('tab-orders').classList.add('active', 'text-light', 'fw-bold');
        document.getElementById('tab-orders').classList.remove('text-muted');
        document.getElementById('tab-menu-manage').classList.remove('active', 'text-light', 'fw-bold');
        document.getElementById('tab-menu-manage').classList.add('text-muted');
        
        document.getElementById('chef-section-orders').classList.remove('d-none');
        document.getElementById('chef-section-menu').classList.add('d-none');
    } else {
        document.getElementById('tab-menu-manage').classList.add('active', 'text-light', 'fw-bold');
        document.getElementById('tab-menu-manage').classList.remove('text-muted');
        document.getElementById('tab-orders').classList.remove('active', 'text-light', 'fw-bold');
        document.getElementById('tab-orders').classList.add('text-muted');
        
        document.getElementById('chef-section-orders').classList.add('d-none');
        document.getElementById('chef-section-menu').classList.remove('d-none');
        loadMenuAdmin();
    }
};

window.submitNewDish = async function() {
    const name = document.getElementById('dish-name').value;
    const category = document.getElementById('dish-category').value;
    const price = document.getElementById('dish-price').value;
    const rating = document.getElementById('dish-rating').value;
    const desc = document.getElementById('dish-desc').value;
    
    const formData = new FormData();
    formData.append('name', name);
    formData.append('category', category);
    formData.append('price', price);
    formData.append('rating', rating);
    formData.append('description', desc);
    
    setLoading('btn-submit-dish', true);
    const imageInput = document.getElementById('dish-image');
    if (imageInput.files[0]) {
        formData.append('image', imageInput.files[0]);
    }
    
    try {
        const res = await fetch(`${API_BASE}/menu`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        
        if (res.ok) {
            showNotification("Dish added successfully!", "success");
            document.getElementById('form-add-dish').reset();
            loadMenuAdmin();
        } else {
            showNotification("Failed to add dish", "danger");
        }
        setLoading('btn-submit-dish', false, 'Add Dish to Menu');
    } catch (e) {
        console.error('Failed to add dish', e);
        showNotification('Error adding dish', 'danger');
        setLoading('btn-submit-dish', false, 'Add Dish to Menu');
    }
};

async function loadMenuAdmin() {
    if (role !== 'chef' && role !== 'admin') return;
    try {
        const res = await fetch(`${API_BASE}/menu/all`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        const tbody = document.getElementById('admin-menu-body');
        
        // Save to global variable for editing later
        window.adminMenuData = data.data;

        if (data.data && data.data.length > 0) {
            tbody.innerHTML = data.data.map(item => `
                <tr>
                    <td>#${item.id}</td>
                    <td><img src="${item.image_url || 'https://via.placeholder.com/50'}" alt="img" class="rounded" style="width: 40px; height: 40px; object-fit: cover;"></td>
                    <td class="fw-bold">${item.name}</td>
                    <td><span class="badge bg-secondary">${item.category}</span></td>
                    <td>$${parseFloat(item.price).toFixed(2)}</td>
                    <td>
                        <span class="badge ${item.is_available == 1 ? 'bg-success' : 'bg-danger'}">
                            ${item.is_available == 1 ? 'Available' : 'Unavailable'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info me-1" onclick="openEditDishModal(${item.id})">Edit</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDish(${item.id})">Delete</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No menu items found.</td></tr>';
        }
    } catch (e) {
        console.error('Failed to load menu admin', e);
    }
}

function openEditDishModal(id) {
    const item = window.adminMenuData.find(i => i.id == id);
    if (!item) return;

    document.getElementById('edit-dish-id').value = item.id;
    document.getElementById('edit-dish-name').value = item.name;
    document.getElementById('edit-dish-category').value = item.category;
    document.getElementById('edit-dish-price').value = item.price;
    document.getElementById('edit-dish-rating').value = item.rating;
    document.getElementById('edit-dish-desc').value = item.description || '';
    document.getElementById('edit-dish-available').checked = (item.is_available == 1);
    document.getElementById('edit-dish-image').value = ''; // Reset file input

    editDishModal.show();
}

async function submitEditDish() {
    setLoading('btn-save-edit-dish', true, 'Saving...');
    const id = document.getElementById('edit-dish-id').value;
    const formData = new FormData();
    
    formData.append('name', document.getElementById('edit-dish-name').value);
    formData.append('category', document.getElementById('edit-dish-category').value);
    formData.append('price', document.getElementById('edit-dish-price').value);
    formData.append('rating', document.getElementById('edit-dish-rating').value);
    formData.append('description', document.getElementById('edit-dish-desc').value);
    formData.append('is_available', document.getElementById('edit-dish-available').checked);
    
    const imageFile = document.getElementById('edit-dish-image').files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }

    try {
        const res = await fetch(`${API_BASE}/menu/update/${id}`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}` },
            body: formData
        });
        const data = await res.json();
        
        if (res.ok) {
            showNotification('Dish updated successfully!', 'success');
            editDishModal.hide();
            loadMenuAdmin();
        } else {
            showNotification(data.error || 'Failed to update dish', 'danger');
        }
    } catch (e) {
        console.error('Update dish failed', e);
        showNotification('Update failed', 'danger');
    }
    setLoading('btn-save-edit-dish', false, 'Save Changes');
}

async function deleteDish(id) {
    if (!confirm('Are you sure you want to delete this dish? This will make it unavailable for order.')) return;
    
    try {
        const res = await fetch(`${API_BASE}/menu/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (res.ok) {
            showNotification('Dish deleted successfully!', 'success');
            loadMenuAdmin();
        } else {
            const data = await res.json();
            showNotification(data.error || 'Failed to delete dish', 'danger');
        }
    } catch (e) {
        console.error('Delete failed', e);
        showNotification('Failed to delete dish', 'danger');
    }
}

// -----------------------------------------------------------------------------
// ADMIN FLOW
// -----------------------------------------------------------------------------
function loadAdmin() {
    showView('admin');
    if (pollInterval) clearInterval(pollInterval);
    fetchAdminOrders();
    pollInterval = setInterval(fetchAdminOrders, 10000);
}

async function fetchAdminOrders() {
    try {
        const res = await fetch(`${API_BASE}/admin/orders`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        
        const tbody = document.getElementById('admin-orders-body');
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No orders found</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.map(order => `
            <tr>
                <td>#${order.id}</td>
                <td class="fw-bold">Table ${order.table_id}</td>
                <td><span class="badge bg-secondary">${order.status}</span></td>
                <td>$${order.total_amount}</td>
                <td>${new Date(order.created_at).toLocaleString()}</td>
            </tr>
        `).join('');

    } catch (e) {
        console.error('Admin fetch failed', e);
    }
}

async function loadFeedbackAdmin() {
    showView('feedback-admin');
    try {
        const res = await fetch(`${API_BASE}/feedback`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        
        // Update Stats
        document.getElementById('fb-avg-rating').textContent = parseFloat(data.stats.average_rating || 0).toFixed(1) + ' ★';
        document.getElementById('fb-total-reviews').textContent = data.stats.total_reviews || 0;

        // Update Grid
        const grid = document.getElementById('feedback-grid');
        grid.innerHTML = data.recent.map(fb => `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="glass-card h-100 border-secondary">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-warning fs-5">${'★'.repeat(Math.round(fb.rating))}${'☆'.repeat(5 - Math.round(fb.rating))}</span>
                        <small class="text-muted">${new Date(fb.created_at).toLocaleDateString()}</small>
                    </div>
                    <p class="mb-3 text-light">"${fb.comment || 'No comment provided.'}"</p>
                    <div class="mt-auto pt-3 border-top border-subtle">
                        <small class="text-muted d-block mb-1">Items Ordered:</small>
                        <small class="text-info">${fb.items_ordered || 'Unknown'}</small>
                    </div>
                </div>
            </div>
        `).join('');

    } catch (e) {
        console.error('Feedback fetch failed', e);
        showNotification('Failed to load feedback', 'danger');
    }
}


// -----------------------------------------------------------------------------
// UTILS
// -----------------------------------------------------------------------------

function showView(viewId) {
    document.querySelectorAll('.view').forEach(el => {
        el.classList.add('d-none');
        el.classList.remove('active');
        el.classList.remove('d-flex');
    });
    const target = document.getElementById(`view-${viewId}`);
    target.classList.remove('d-none');
    target.classList.add('active');
    target.classList.add('d-flex');
}

window.showNotification = function(msg, type = 'info') {
    const title = document.getElementById('system-toast-title');
    const body = document.getElementById('system-toast-body');
    const toastEl = document.getElementById('systemToast');

    if (!toastEl) return;

    toastEl.classList.remove('border-danger', 'border-success', 'border-warning', 'border-info');
    title.classList.remove('text-danger', 'text-success', 'text-warning', 'text-info', 'text-white');

    toastEl.classList.add(`border-${type}`);
    title.classList.add(`text-${type}`);
    
    title.textContent = type === 'danger' ? 'Error' : (type === 'warning' ? 'Warning' : (type === 'success' ? 'Success' : 'Notification'));
    body.textContent = msg;
    
    systemToastInstance.show();
};
