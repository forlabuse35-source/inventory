<?php
$pageTitle = 'Stock Management';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle stock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $productId = intval($_POST['product_id'] ?? 0);
    $quantity  = intval($_POST['quantity'] ?? 0);

    if ($action === 'add_stock' && $productId > 0 && $quantity > 0) {
        $stmt = $db->prepare('
            UPDATE inventory_stock
            SET quantity = quantity + :qty, last_restock_date = NOW()
            WHERE product_id = :pid
        ');
        $stmt->execute([':qty' => $quantity, ':pid' => $productId]);
        updateStockStatus($db, $productId);
        setFlash('success', "Added $quantity units to stock.");
        header('Location: stock.php');
        exit;
    }

    if ($action === 'remove_stock' && $productId > 0 && $quantity > 0) {
        // Check current stock
        $current = $db->prepare('SELECT quantity FROM inventory_stock WHERE product_id = :pid');
        $current->execute([':pid' => $productId]);
        $currentQty = (int) $current->fetchColumn();

        if ($quantity > $currentQty) {
            setFlash('error', "Cannot remove $quantity units. Only $currentQty available.");
        } else {
            $stmt = $db->prepare('
                UPDATE inventory_stock
                SET quantity = quantity - :qty
                WHERE product_id = :pid
            ');
            $stmt->execute([':qty' => $quantity, ':pid' => $productId]);
            updateStockStatus($db, $productId);
            setFlash('success', "Removed $quantity units from stock.");
        }
        header('Location: stock.php');
        exit;
    }

    if ($action === 'update_threshold' && $productId > 0) {
        $threshold = intval($_POST['threshold'] ?? 10);
        $stmt = $db->prepare('UPDATE inventory_stock SET low_stock_threshold = :t WHERE product_id = :pid');
        $stmt->execute([':t' => max(1, $threshold), ':pid' => $productId]);
        updateStockStatus($db, $productId);
        setFlash('success', 'Low stock threshold updated.');
        header('Location: stock.php');
        exit;
    }
}

function updateStockStatus(PDO $db, int $productId): void
{
    $stmt = $db->prepare('SELECT quantity, low_stock_threshold FROM inventory_stock WHERE product_id = :pid');
    $stmt->execute([':pid' => $productId]);
    $row = $stmt->fetch();
    if (!$row) return;

    if ($row['quantity'] <= 0) {
        $status = 'out_of_stock';
    } elseif ($row['quantity'] <= $row['low_stock_threshold']) {
        $status = 'low_stock';
    } else {
        $status = 'in_stock';
    }

    $db->prepare('UPDATE inventory_stock SET status = :s WHERE product_id = :pid')
       ->execute([':s' => $status, ':pid' => $productId]);
}

// Fetch stock data
$stockItems = $db->query('
    SELECT ist.*, p.name AS product_name, p.sku, p.base_price
    FROM inventory_stock ist
    JOIN products p ON ist.product_id = p.id
    ORDER BY ist.status ASC, p.name ASC
')->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <div class="toolbar">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control" id="stockSearch" placeholder="Search stock...">
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <?php if (empty($stockItems)): ?>
            <div class="empty-state">
                <i class="fas fa-warehouse"></i>
                <p>No stock records found. Add products first.</p>
            </div>
        <?php else: ?>
        <table class="table" id="stockTable">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Threshold</th>
                    <th>Status</th>
                    <th>Last Restock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockItems as $item): ?>
                <tr>
                    <td><code><?= sanitize($item['sku']) ?></code></td>
                    <td><strong><?= sanitize($item['product_name']) ?></strong></td>
                    <td><?= number_format($item['quantity']) ?></td>
                    <td><?= number_format($item['low_stock_threshold']) ?></td>
                    <td><?= getStockStatusBadge($item['status']) ?></td>
                    <td><?= $item['last_restock_date'] ? formatDate($item['last_restock_date']) : '<span style="color:var(--gray-400)">Never</span>' ?></td>
                    <td class="actions">
                        <button class="btn btn-sm btn-success" onclick='openStockModal("add", <?= json_encode($item) ?>)'>
                            <i class="fas fa-plus"></i> Add
                        </button>
                        <button class="btn btn-sm btn-warning" onclick='openStockModal("remove", <?= json_encode($item) ?>)'>
                            <i class="fas fa-minus"></i> Remove
                        </button>
                        <button class="btn btn-sm btn-outline" onclick='openThresholdModal(<?= json_encode($item) ?>)'>
                            <i class="fas fa-sliders"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Remove Stock Modal -->
<div class="modal-overlay" id="stockModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="stockModalTitle">Add Stock</h3>
            <button class="modal-close" onclick="closeModal('stockModal')">&times;</button>
        </div>
        <form method="POST" id="stockForm">
            <input type="hidden" name="action" id="stockAction">
            <input type="hidden" name="product_id" id="stockProductId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Product</label>
                    <input type="text" class="form-control" id="stockProductName" readonly style="background: var(--gray-50);">
                </div>
                <div class="form-group">
                    <label class="form-label">Current Quantity</label>
                    <input type="text" class="form-control" id="stockCurrentQty" readonly style="background: var(--gray-50);">
                </div>
                <div class="form-group">
                    <label class="form-label" id="stockQtyLabel">Quantity to Add *</label>
                    <input type="number" name="quantity" id="stockQuantity" class="form-control" placeholder="Enter quantity" min="1" required>
                    <div class="form-error"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('stockModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="stockSubmitBtn"><i class="fas fa-check"></i> Confirm</button>
            </div>
        </form>
    </div>
</div>

<!-- Threshold Modal -->
<div class="modal-overlay" id="thresholdModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Set Low Stock Threshold</h3>
            <button class="modal-close" onclick="closeModal('thresholdModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_threshold">
            <input type="hidden" name="product_id" id="thresholdProductId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Product</label>
                    <input type="text" class="form-control" id="thresholdProductName" readonly style="background: var(--gray-50);">
                </div>
                <div class="form-group">
                    <label class="form-label">Low Stock Threshold *</label>
                    <input type="number" name="threshold" id="thresholdValue" class="form-control" min="1" required>
                    <div class="form-error"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('thresholdModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStockModal(type, item) {
    document.getElementById('stockAction').value      = type === 'add' ? 'add_stock' : 'remove_stock';
    document.getElementById('stockProductId').value    = item.product_id;
    document.getElementById('stockProductName').value  = item.product_name;
    document.getElementById('stockCurrentQty').value   = item.quantity;
    document.getElementById('stockQuantity').value     = '';

    if (type === 'add') {
        document.getElementById('stockModalTitle').textContent = 'Add Stock';
        document.getElementById('stockQtyLabel').textContent   = 'Quantity to Add *';
        document.getElementById('stockSubmitBtn').className    = 'btn btn-success';
        document.getElementById('stockSubmitBtn').innerHTML    = '<i class="fas fa-plus"></i> Add Stock';
    } else {
        document.getElementById('stockModalTitle').textContent = 'Remove Stock';
        document.getElementById('stockQtyLabel').textContent   = 'Quantity to Remove *';
        document.getElementById('stockSubmitBtn').className    = 'btn btn-warning';
        document.getElementById('stockSubmitBtn').innerHTML    = '<i class="fas fa-minus"></i> Remove Stock';
    }

    openModal('stockModal');
}

function openThresholdModal(item) {
    document.getElementById('thresholdProductId').value   = item.product_id;
    document.getElementById('thresholdProductName').value = item.product_name;
    document.getElementById('thresholdValue').value       = item.low_stock_threshold;
    openModal('thresholdModal');
}

initTableSearch('stockSearch', 'stockTable');
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
