
// กำหนดพาธ
// ============================================================================
(function () {
    const path = window.location.pathname;          // /ecommerce-project1.2/views/index.php
    const idx = path.indexOf('/views/');
    let basePath = '';
    if (idx !== -1) {
        basePath = path.substring(0, idx);         // /ecommerce-project1.2
    } else {
        
        const parts = path.split('/');
        parts.pop(); // เอาไฟล์ออก
        basePath = parts.join('/');
    }

    window.__ECOM_BASE_PATH__ = basePath;
})();

// ใช้ BASE_PATH ที่เราคำนวณไว้
const BASE_PATH = window.__ECOM_BASE_PATH__ || '';
const CART_API_URL = `${BASE_PATH}/api/cart_db.php`;
const CHECKOUT_API_URL = `${BASE_PATH}/api/create_checkout_session.php`;

let currentCartId = null; // เก็บ cart_id ปัจจุบันที่ได้จากเซิร์ฟเวอร์

// ----------------------------------------------------------------------------
// helper เรียก API
// ----------------------------------------------------------------------------
async function callCartApi(action, payload = {}) {
    const body = Object.assign({ action }, payload);

    const res = await fetch(CART_API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });

    let data;
    try {
        data = await res.json();
    } catch (e) {
        console.error('Invalid JSON from cart_db.php', e);
        throw new Error('Invalid JSON from server');
    }

    if (!data.success && data.error) {
        console.error('API Error:', data.error || data.message);
    }

    return data;
}

// ----------------------------------------------------------------------------
// อัปเดต badge ที่ไอคอนตะกร้า 
// ----------------------------------------------------------------------------
function renderCartBadge(count) {
    const badge = document.querySelector('.cart-count');
    if (badge) {
        badge.textContent = String(count || 0);
    }
}

async function syncCartBadgeFromServer() {
    try {
        const data = await callCartApi('get_cart');
        currentCartId = data.cart_id || null;
        renderCartBadge(data.total_quantity || 0);
    } catch (e) {
        console.warn('updateCartBadge error:', e.message);
    }
}

// ----------------------------------------------------------------------------
// ปุ่มเพิ่มลงตะกร้า 
// ----------------------------------------------------------------------------
function setupAddToCartButtons() {
    const buttons = document.querySelectorAll('.add-to-cart-btn');
    if (!buttons.length) return;

    buttons.forEach(btn => {
        const newBtn = btn.cloneNode(true); // กัน event ซ้ำ
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', async () => {
            const productId = parseInt(newBtn.dataset.id);
            if (!productId) return;

            try {
                const data = await callCartApi('add_item', {
                    product_id: productId,
                    quantity: 1,
                });

                if (!data.success) {
                    alert(data.message || 'ไม่สามารถเพิ่มสินค้าลงตะกร้าได้');
                    return;
                }

                currentCartId = data.cart_id || currentCartId;
                renderCartBadge(data.total_quantity || 0);
            } catch (e) {
                console.error(e);
                alert('เกิดข้อผิดพลาดในการเพิ่มสินค้า');
            }
        });
    });
}

// ----------------------------------------------------------------------------
// ส่วนของ cart.php
// ----------------------------------------------------------------------------
function isCartPage() {
    return !!document.getElementById('cart-items');
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderCartTable(items = [], total = 0) {
    const tbody = document.getElementById('cart-items');
    const totalPriceElem = document.getElementById('total-price');
    if (!tbody || !totalPriceElem) return;

    tbody.innerHTML = '';

    if (!items.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="loading-text">ไม่มีสินค้าในตะกร้า</td>
            </tr>
        `;
        totalPriceElem.textContent = '0.00';
        return;
    }

    items.forEach(item => {
        const tr = document.createElement('tr');
        tr.classList.add('cart-item-row');

        const lineTotal = (item.unit_price * item.quantity).toFixed(2);
        const priceText = Number(item.unit_price).toFixed(2);

        tr.innerHTML = `
            <td>${item.name ? escapeHtml(item.name) : ''}</td>
            <td>฿${priceText}</td>
            <td>
                <input type="number"
                       class="item-quantity"
                       data-item-id="${item.id}"
                       min="1"
                       value="${item.quantity}">
            </td>
            <td>฿${lineTotal}</td>
            <td>
                <button type="button"
                        class="remove-item-btn"
                        data-item-id="${item.id}">
                    ลบ
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    totalPriceElem.textContent = Number(total).toFixed(2);
}

async function loadCartFromServer() {
    try {
        const data = await callCartApi('get_cart');

        currentCartId = data.cart_id || null;
        renderCartBadge(data.total_quantity || 0);
        renderCartTable(data.items || [], data.total || 0);
    } catch (e) {
        console.error('โหลดตะกร้าไม่สำเร็จ', e);
    }
}

function setupCartTableEvents() {
    const tbody = document.getElementById('cart-items');
    if (!tbody) return;

    tbody.addEventListener('input', async (e) => {
        const input = e.target.closest('.item-quantity');
        if (!input) return;

        const itemId = parseInt(input.dataset.itemId);
        let qty = parseInt(input.value);
        if (!itemId || qty <= 0) {
            qty = 1;
            input.value = 1;
        }

        try {
            const data = await callCartApi('update_qty', {
                item_id: itemId,
                quantity: qty,
            });

            if (!data.success) {
                alert(data.message || 'ไม่สามารถอัปเดตจำนวนได้');
                return;
            }

            await loadCartFromServer();
        } catch (err) {
            console.error(err);
            alert('เกิดข้อผิดพลาดในการอัปเดตจำนวน');
        }
    });

    tbody.addEventListener('click', async (e) => {
        const btn = e.target.closest('.remove-item-btn');
        if (!btn) return;

        const itemId = parseInt(btn.dataset.itemId);
        if (!itemId) return;

        if (!confirm('ต้องการลบสินค้ารายการนี้ออกจากตะกร้าหรือไม่?')) return;

        try {
            const data = await callCartApi('remove_item', { item_id: itemId });
            if (!data.success) {
                alert(data.message || 'ไม่สามารถลบสินค้าได้');
                return;
            }

            await loadCartFromServer();
        } catch (err) {
            console.error(err);
            alert('เกิดข้อผิดพลาดในการลบสินค้า');
        }
    });
}

// ปุ่มไปชำระเงิน
function setupCheckoutButton() {
    const btn = document.getElementById('checkout-button');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        if (!currentCartId) {
            alert('ไม่พบตะกร้าสินค้าที่ใช้งานอยู่');
            return;
        }

        try {
            const res = await fetch(CHECKOUT_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart_id: currentCartId }),
            });

            const data = await res.json();

            if (data.checkout_url) {
                window.location.href = data.checkout_url;
            } else {
                console.error('Error with the response from API:', data);
                alert(data.error || 'ไม่สามารถสร้างหน้าชำระเงินได้');
            }
        } catch (e) {
            console.error('Checkout error', e);
            alert('เกิดข้อผิดพลาดระหว่างไปยังหน้าชำระเงิน');
        }
    });
}

// main
document.addEventListener('DOMContentLoaded', async () => {
    await syncCartBadgeFromServer();
    setupAddToCartButtons();

    if (isCartPage()) {
        await loadCartFromServer();
        setupCartTableEvents();
        setupCheckoutButton();
    }
});
