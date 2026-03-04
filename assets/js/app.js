const API_BASE = '/praktikum4Webprog/api/products';

// ── State ──────────────────────────────────────────────────────────────────────
let allProducts  = [];
let editMode     = false;
let editId       = null;
let deleteTarget = null;

// ── DOM refs ───────────────────────────────────────────────────────────────────
const tbody         = document.getElementById('productTableBody');
const statTotal     = document.getElementById('statTotal');
const statStock     = document.getElementById('statStock');
const statValue     = document.getElementById('statValue');
const statCateg     = document.getElementById('statCateg');
const searchInput   = document.getElementById('searchInput');
const formModal     = document.getElementById('formModal');
const confirmModal  = document.getElementById('confirmModal');
const productForm   = document.getElementById('productForm');
const modalTitle    = document.getElementById('modalTitle');
const saveBtn       = document.getElementById('saveBtn');
const deleteNameEl  = document.getElementById('deleteName');
const toastCont     = document.getElementById('toastContainer');

// ── Init ───────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', fetchProducts);

// ── Fetch all ──────────────────────────────────────────────────────────────────
async function fetchProducts() {
    showLoading(true);
    try {
        const res  = await fetch(API_BASE);
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message);
        allProducts = json.data || [];
        renderTable(allProducts);
        updateStats(allProducts);
    } catch (err) {
        showLoading(false);
        tbody.innerHTML = emptyRow(`Failed to load products: ${err.message}`);
    }
}

function showLoading(state) {
    if (state) {
        tbody.innerHTML = `
            <tr><td colspan="7" class="loading-state">
                <div class="loading-spinner"></div>
                <p>Memuat data produk...</p>
            </td></tr>`;
    }
}

// ── Render ─────────────────────────────────────────────────────────────────────
function renderTable(products) {
    if (products.length === 0) {
        tbody.innerHTML = emptyRow('Belum ada produk. Tambahkan produk baru untuk memulai.');
        return;
    }
    tbody.innerHTML = products.map(p => {
        const stockClass = p.stock > 10 ? 'stock-ok' : p.stock > 0 ? 'stock-low' : 'stock-out';
        const stockLabel = p.stock > 10 ? 'Ready' : p.stock > 0 ? 'Low' : 'Habis';
        return `
        <tr>
            <td class="td-id">#${p.id}</td>
            <td class="td-name">${esc(p.name)}</td>
            <td><span class="category-badge">${esc(p.category)}</span></td>
            <td class="td-price">${formatRupiah(p.price)}</td>
            <td><span class="stock-badge ${stockClass}">${p.stock} &mdash; ${stockLabel}</span></td>
            <td class="td-desc">${esc(p.description || '-')}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-ghost btn-sm" onclick="openEditModal(${p.id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="openDeleteModal(${p.id}, '${esc(p.name)}')">Hapus</button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function emptyRow(msg) {
    return `<tr><td colspan="7" class="empty-state"><p>${msg}</p></td></tr>`;
}

// ── Stats ──────────────────────────────────────────────────────────────────────
function updateStats(products) {
    const totalStock = products.reduce((s, p) => s + Number(p.stock), 0);
    const totalValue = products.reduce((s, p) => s + Number(p.price) * Number(p.stock), 0);
    const categories = new Set(products.map(p => p.category)).size;

    statTotal.textContent = products.length;
    statStock.textContent = totalStock.toLocaleString('id-ID');
    statValue.textContent = formatRupiah(totalValue);
    statCateg.textContent = categories;
}

// ── Search ─────────────────────────────────────────────────────────────────────
searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    const filtered = q
        ? allProducts.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.category.toLowerCase().includes(q) ||
            (p.description && p.description.toLowerCase().includes(q)))
        : allProducts;
    renderTable(filtered);
});

// ── Form Modal ─────────────────────────────────────────────────────────────────
function openCreateModal() {
    editMode = false;
    editId   = null;
    modalTitle.textContent = 'Tambah Produk Baru';
    saveBtn.textContent    = 'Simpan Produk';
    productForm.reset();
    clearFormErrors();
    openModal(formModal);
}

async function openEditModal(id) {
    try {
        const res  = await fetch(`${API_BASE}/${id}`);
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message);

        const p = json.data;
        editMode = true;
        editId   = id;
        modalTitle.textContent       = 'Edit Produk';
        saveBtn.textContent          = 'Perbarui Produk';
        document.getElementById('fName').value        = p.name;
        document.getElementById('fCategory').value   = p.category;
        document.getElementById('fPrice').value      = p.price;
        document.getElementById('fStock').value      = p.stock;
        document.getElementById('fDescription').value = p.description || '';
        clearFormErrors();
        openModal(formModal);
    } catch (err) {
        toast('Gagal memuat data produk: ' + err.message, 'error');
    }
}

// ── Save (create / update) ─────────────────────────────────────────────────────
productForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    const payload = {
        name:        document.getElementById('fName').value.trim(),
        category:    document.getElementById('fCategory').value.trim(),
        price:       parseFloat(document.getElementById('fPrice').value),
        stock:       parseInt(document.getElementById('fStock').value, 10),
        description: document.getElementById('fDescription').value.trim()
    };

    saveBtn.disabled    = true;
    saveBtn.textContent = editMode ? 'Memperbarui...' : 'Menyimpan...';

    try {
        const url    = editMode ? `${API_BASE}/${editId}` : API_BASE;
        const method = editMode ? 'PUT' : 'POST';
        const res    = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.status !== 'success') throw new Error(json.message);

        closeModal(formModal);
        toast(editMode ? 'Produk berhasil diperbarui.' : 'Produk berhasil ditambahkan.', 'success');
        fetchProducts();
    } catch (err) {
        toast('Gagal menyimpan: ' + err.message, 'error');
    } finally {
        saveBtn.disabled    = false;
        saveBtn.textContent = editMode ? 'Perbarui Produk' : 'Simpan Produk';
    }
});

// ── Delete ─────────────────────────────────────────────────────────────────────
function openDeleteModal(id, name) {
    deleteTarget      = id;
    deleteNameEl.textContent = name;
    openModal(confirmModal);
}

async function confirmDelete() {
    if (!deleteTarget) return;

    document.getElementById('confirmDeleteBtn').disabled    = true;
    document.getElementById('confirmDeleteBtn').textContent = 'Menghapus...';

    try {
        const res  = await fetch(`${API_BASE}/${deleteTarget}`, { method: 'DELETE' });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message);

        closeModal(confirmModal);
        toast('Produk berhasil dihapus.', 'success');
        fetchProducts();
    } catch (err) {
        toast('Gagal menghapus: ' + err.message, 'error');
    } finally {
        deleteTarget = null;
        document.getElementById('confirmDeleteBtn').disabled    = false;
        document.getElementById('confirmDeleteBtn').textContent = 'Ya, Hapus';
    }
}

// ── Validation ─────────────────────────────────────────────────────────────────
function validateForm() {
    clearFormErrors();
    let valid = true;

    const name       = document.getElementById('fName').value.trim();
    const category   = document.getElementById('fCategory').value.trim();
    const priceRaw   = document.getElementById('fPrice').value;
    const stockRaw   = document.getElementById('fStock').value;

    if (!name)               setError('fName',     'Nama produk wajib diisi.');
    if (!category)           setError('fCategory', 'Kategori wajib diisi.');
    if (priceRaw === '' || isNaN(priceRaw) || Number(priceRaw) < 0)
                             setError('fPrice',    'Harga harus berupa angka positif.');
    if (stockRaw === '' || isNaN(stockRaw) || !Number.isInteger(Number(stockRaw)) || Number(stockRaw) < 0)
                             setError('fStock',    'Stok harus berupa bilangan bulat positif.');

    document.querySelectorAll('.form-group.has-error').forEach(() => { valid = false; });
    return document.querySelectorAll('.form-group.has-error').length === 0;
}

function setError(fieldId, msg) {
    const group = document.getElementById(fieldId).closest('.form-group');
    group.classList.add('has-error');
    group.querySelector('.field-error').textContent = msg;
}

function clearFormErrors() {
    document.querySelectorAll('.form-group.has-error').forEach(g => g.classList.remove('has-error'));
}

// ── Modal helpers ──────────────────────────────────────────────────────────────
function openModal(el)  { el.classList.add('active'); }
function closeModal(el) { el.classList.remove('active'); }

// close on backdrop click
[formModal, confirmModal].forEach(m => {
    m.addEventListener('click', e => {
        if (e.target === m) closeModal(m);
    });
});

// ── Toast ──────────────────────────────────────────────────────────────────────
function toast(message, type = 'info') {
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `<span class="toast-dot"></span>${message}`;
    toastCont.appendChild(el);
    setTimeout(() => {
        el.style.opacity  = '0';
        el.style.transform = 'translateY(8px)';
        el.style.transition = 'all .25s ease';
        setTimeout(() => el.remove(), 260);
    }, 3000);
}

// ── Utility ────────────────────────────────────────────────────────────────────
function formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID', { minimumFractionDigits: 0 });
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
