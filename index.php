<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management &mdash; Praktikum Web Programming</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ── Header ──────────────────────────────────────────────────────────────── -->
<header class="app-header">
    <div class="header-inner">
        <a class="header-brand" href="#">
            <span>PM</span>
            Product Management
        </a>
        <span class="header-meta">REST API &mdash; PHP &amp; MySQL</span>
    </div>
</header>

<!-- ── Main ────────────────────────────────────────────────────────────────── -->
<main class="main-content">

    <!-- Page title -->
    <div class="page-title">
        <h1>Manajemen Produk</h1>
        <p>Kelola data produk.</p>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-label">Total Produk</span>
            <span class="stat-value" id="statTotal">-</span>
            <span class="stat-sub">item terdaftar</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Total Stok</span>
            <span class="stat-value" id="statStock">-</span>
            <span class="stat-sub">unit tersedia</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Nilai Inventori</span>
            <span class="stat-value" style="font-size:18px" id="statValue">-</span>
            <span class="stat-sub">estimasi total nilai</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Kategori</span>
            <span class="stat-value" id="statCateg">-</span>
            <span class="stat-sub">kategori produk</span>
        </div>
    </div>

    <!-- Table card -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <h2>Daftar Produk</h2>
                <p>Klik Edit atau Hapus pada baris untuk mengelola data</p>
            </div>
            <div class="toolbar">
                <div class="search-wrap">
                    <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="searchInput" placeholder="Cari produk...">
                </div>
                <button class="btn btn-ghost btn-sm" onclick="fetchProducts()">Refresh</button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah Produk
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <tr>
                        <td colspan="7" class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Memuat data produk...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ── Form Modal ───────────────────────────────────────────────────────────── -->
<div class="modal-backdrop" id="formModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Produk Baru</h3>
            <button class="modal-close" onclick="closeModal(document.getElementById('formModal'))">&times;</button>
        </div>
        <form id="productForm" novalidate>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label for="fName">Nama Produk <span class="required">*</span></label>
                        <input type="text" id="fName" placeholder="Contoh: Laptop ProBook 14" maxlength="150">
                        <span class="field-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="fCategory">Kategori <span class="required">*</span></label>
                        <input type="text" id="fCategory" placeholder="Contoh: Electronics" maxlength="100">
                        <span class="field-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="fPrice">Harga (Rp) <span class="required">*</span></label>
                        <input type="number" id="fPrice" placeholder="0" min="0" step="1000">
                        <span class="field-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="fStock">Stok <span class="required">*</span></label>
                        <input type="number" id="fStock" placeholder="0" min="0" step="1">
                        <span class="field-error"></span>
                    </div>
                    <div class="form-group full">
                        <label for="fDescription">Deskripsi</label>
                        <textarea id="fDescription" rows="3" placeholder="Deskripsi singkat produk..."></textarea>
                        <span class="field-error"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal(document.getElementById('formModal'))">Batal</button>
                <button type="submit" class="btn btn-primary" id="saveBtn">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Confirm Delete Modal ─────────────────────────────────────────────────── -->
<div class="modal-backdrop" id="confirmModal">
    <div class="modal confirm-dialog">
        <div class="confirm-body">
            <div class="confirm-icon">!</div>
            <h3>Hapus Produk?</h3>
            <p>Anda akan menghapus produk <strong id="deleteName"></strong>. Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal(document.getElementById('confirmModal'))">Batal</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- ── Toast container ──────────────────────────────────────────────────────── -->
<div class="toast-container" id="toastContainer"></div>

<script>
    const API_BASE = '<?= rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/') ?>/api/index.php?resource=products';
</script>
<script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
