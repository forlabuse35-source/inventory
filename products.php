<?php
$pageTitle = 'Products';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle form submissions via AJAX-style POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $price    = floatval($_POST['base_price'] ?? 0);
        $sku      = trim($_POST['sku'] ?? '') ?: generateSKU($category);

        if ($name === '' || $category === '' || $price <= 0) {
            setFlash('error', 'Please fill in all required fields.');
        } else {
            try {
                $stmt = $db->prepare('INSERT INTO products (sku, name, category, description, base_price, created_by) VALUES (:sku, :name, :category, :desc, :price, :uid)');
                $stmt->execute([
                    ':sku'   => $sku,
                    ':name'  => $name,
                    ':category' => $category,
                    ':desc'  => $desc,
                    ':price' => $price,
                    ':uid'   => $_SESSION['user_id'],
                ]);
                $productId = $db->lastInsertId();
                // Create inventory stock record
                $db->prepare('INSERT INTO inventory_stock (product_id, quantity, status) VALUES (:pid, 0, "out_of_stock")')
                   ->execute([':pid' => $productId]);
                setFlash('success', 'Product added successfully.');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setFlash('error', 'A product with this SKU already exists.');
                } else {
                    setFlash('error', 'Failed to add product.');
                }
            }
        }
        header('Location: products.php');
        exit;
    }

    if ($action === 'edit') {
        $id       = intval($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $price    = floatval($_POST['base_price'] ?? 0);
        $sku      = trim($_POST['sku'] ?? '');

        if ($id <= 0 || $name === '' || $category === '' || $price <= 0) {
            setFlash('error', 'Please fill in all required fields.');
        } else {
            try {
                $stmt = $db->prepare('UPDATE products SET sku = :sku, name = :name, category = :category, description = :desc, base_price = :price WHERE id = :id');
                $stmt->execute([
                    ':sku'   => $sku,
                    ':name'  => $name,
                    ':category' => $category,
                    ':desc'  => $desc,
                    ':price' => $price,
                    ':id'    => $id,
                ]);
                setFlash('success', 'Product updated successfully.');
            } catch (PDOException $e) {
                setFlash('error', 'Failed to update product.');
            }
        }
        header('Location: products.php');
        exit;
    }

    if ($action === 'delete' && isAdmin()) {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $db->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $id]);
                setFlash('success', 'Product deleted successfully.');
            } catch (PDOException $e) {
                setFlash('error', 'Cannot delete product. It may have associated sales records.');
            }
        }
        header('Location: products.php');
        exit;
    }
}

// Fetch products
$products = $db->query('
    SELECT p.*, u.full_name AS created_by_name
    FROM products p
    JOIN users u ON p.created_by = u.id
    ORDER BY p.created_at DESC
')->fetchAll();

// Unique categories for dropdown
$categories = $db->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="card">
    <div class="card-header">
        <div class="toolbar">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
            </div>
        </div>
        <button class="btn btn-primary" onclick="openModal('addProductModal')">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </div>
    <div class="table-responsive">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-cube"></i>
                <p>No products found. Add your first product to get started.</p>
            </div>
        <?php else: ?>
        <table class="table" id="productsTable">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Base Price</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><code><?= sanitize($p['sku']) ?></code></td>
                    <td><strong><?= sanitize($p['name']) ?></strong></td>
                    <td><?= sanitize($p['category']) ?></td>
                    <td><?= formatCurrency($p['base_price']) ?></td>
                    <td><?= formatDate($p['created_at']) ?></td>
                    <td class="actions">
                        <button class="btn btn-sm btn-outline" onclick='editProduct(<?= json_encode($p) ?>)'>
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <?php if (isAdmin()): ?>
                        <form method="POST" style="display:inline;" onsubmit="return false;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmAction('Delete this product?', () => this.closest('form').submit())">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal-overlay" id="addProductModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New Product</h3>
            <button class="modal-close" onclick="closeModal('addProductModal')">&times;</button>
        </div>
        <form method="POST" id="addProductForm">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Wireless Mouse" required>
                        <div class="form-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU (auto-generated if empty)</label>
                        <input type="text" name="sku" class="form-control" placeholder="e.g. ELE-A1B2C3">
                        <div class="form-error"></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <input type="text" name="category" class="form-control" placeholder="e.g. Electronics" list="categoryList" required>
                        <datalist id="categoryList">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= sanitize($cat) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <div class="form-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Base Price ($) *</label>
                        <input type="number" name="base_price" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
                        <div class="form-error"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" placeholder="Product description..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal-overlay" id="editProductModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Product</h3>
            <button class="modal-close" onclick="closeModal('editProductModal')">&times;</button>
        </div>
        <form method="POST" id="editProductForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editProductId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" id="editProductName" class="form-control" required>
                        <div class="form-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" id="editProductSku" class="form-control">
                        <div class="form-error"></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <input type="text" name="category" id="editProductCategory" class="form-control" list="categoryList" required>
                        <div class="form-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Base Price ($) *</label>
                        <input type="number" name="base_price" id="editProductPrice" class="form-control" step="0.01" min="0.01" required>
                        <div class="form-error"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="editProductDesc" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editProductModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Dialog -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Are you sure?</h3>
        <p id="confirmMessage">This action cannot be undone.</p>
        <div class="confirm-actions">
            <button class="btn btn-secondary" onclick="closeConfirm()">Cancel</button>
            <button class="btn btn-danger" id="confirmYes">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('editProductId').value       = product.id;
    document.getElementById('editProductName').value     = product.name;
    document.getElementById('editProductSku').value      = product.sku;
    document.getElementById('editProductCategory').value = product.category;
    document.getElementById('editProductPrice').value    = product.base_price;
    document.getElementById('editProductDesc').value     = product.description || '';
    openModal('editProductModal');
}

initTableSearch('productSearch', 'productsTable');
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
