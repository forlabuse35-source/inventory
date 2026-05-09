<?php
$pageTitle = 'Sales / Billing';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_sale') {
    $productId = intval($_POST['product_id'] ?? 0);
    $quantity  = intval($_POST['quantity'] ?? 0);
    $notes     = trim($_POST['notes'] ?? '');

    if ($productId <= 0 || $quantity <= 0) {
        setFlash('error', 'Please select a product and enter a valid quantity.');
        header('Location: sales.php');
        exit;
    }

    // Get product price
    $product = $db->prepare('SELECT * FROM products WHERE id = :id');
    $product->execute([':id' => $productId]);
    $product = $product->fetch();

    if (!$product) {
        setFlash('error', 'Product not found.');
        header('Location: sales.php');
        exit;
    }

    // Check stock
    $stock = $db->prepare('SELECT quantity FROM inventory_stock WHERE product_id = :pid');
    $stock->execute([':pid' => $productId]);
    $stockQty = (int) $stock->fetchColumn();

    if ($quantity > $stockQty) {
        setFlash('error', "Insufficient stock. Only $stockQty units available.");
        header('Location: sales.php');
        exit;
    }

    $totalAmount = $product['base_price'] * $quantity;

    try {
        $db->beginTransaction();

        // Record sale
        $stmt = $db->prepare('
            INSERT INTO sales_transactions (product_id, quantity, unit_price, total_amount, processed_by, notes)
            VALUES (:pid, :qty, :price, :total, :uid, :notes)
        ');
        $stmt->execute([
            ':pid'   => $productId,
            ':qty'   => $quantity,
            ':price' => $product['base_price'],
            ':total' => $totalAmount,
            ':uid'   => $_SESSION['user_id'],
            ':notes' => $notes,
        ]);

        // Deduct stock
        $db->prepare('UPDATE inventory_stock SET quantity = quantity - :qty WHERE product_id = :pid')
           ->execute([':qty' => $quantity, ':pid' => $productId]);

        // Update stock status
        $updated = $db->prepare('SELECT quantity, low_stock_threshold FROM inventory_stock WHERE product_id = :pid');
        $updated->execute([':pid' => $productId]);
        $row = $updated->fetch();
        if ($row['quantity'] <= 0) {
            $status = 'out_of_stock';
        } elseif ($row['quantity'] <= $row['low_stock_threshold']) {
            $status = 'low_stock';
        } else {
            $status = 'in_stock';
        }
        $db->prepare('UPDATE inventory_stock SET status = :s WHERE product_id = :pid')
           ->execute([':s' => $status, ':pid' => $productId]);

        $db->commit();
        setFlash('success', 'Sale recorded successfully. Total: ' . formatCurrency($totalAmount));
    } catch (PDOException $e) {
        $db->rollBack();
        setFlash('error', 'Failed to process sale.');
    }

    header('Location: sales.php');
    exit;
}

// Fetch products with stock for POS
$posProducts = $db->query('
    SELECT p.id, p.name, p.sku, p.base_price, COALESCE(ist.quantity, 0) AS stock_qty
    FROM products p
    LEFT JOIN inventory_stock ist ON p.id = ist.product_id
    WHERE COALESCE(ist.quantity, 0) > 0
    ORDER BY p.name
')->fetchAll();

// Fetch sales history
$sales = $db->query('
    SELECT st.*, p.name AS product_name, p.sku, u.full_name AS staff_name
    FROM sales_transactions st
    JOIN products p ON st.product_id = p.id
    JOIN users u ON st.processed_by = u.id
    ORDER BY st.created_at DESC
')->fetchAll();
?>

<!-- POS Section -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cash-register" style="margin-right:8px; color: var(--accent);"></i>Point of Sale</h3>
    </div>
    <div class="card-body">
        <form method="POST" id="posForm">
            <input type="hidden" name="action" value="create_sale">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Select Product *</label>
                    <select name="product_id" id="posProduct" class="form-control" required>
                        <option value="">-- Choose a product --</option>
                        <?php foreach ($posProducts as $pp): ?>
                        <option value="<?= $pp['id'] ?>"
                                data-price="<?= $pp['base_price'] ?>"
                                data-stock="<?= $pp['stock_qty'] ?>"
                                data-name="<?= sanitize($pp['name']) ?>">
                            <?= sanitize($pp['name']) ?> (<?= sanitize($pp['sku']) ?>) — <?= formatCurrency($pp['base_price']) ?> — Stock: <?= $pp['stock_qty'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-error"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="quantity" id="posQuantity" class="form-control" placeholder="Enter quantity" min="1" required>
                    <div class="form-error" id="posQtyError"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes (optional)</label>
                <input type="text" name="notes" class="form-control" placeholder="e.g. Walk-in customer">
            </div>

            <div class="pos-summary" id="posSummary" style="display: none;">
                <div class="summary-row">
                    <span>Product:</span>
                    <span id="summaryProduct">-</span>
                </div>
                <div class="summary-row">
                    <span>Unit Price:</span>
                    <span id="summaryPrice">-</span>
                </div>
                <div class="summary-row">
                    <span>Quantity:</span>
                    <span id="summaryQty">-</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total Amount:</span>
                    <span id="summaryTotal">$0.00</span>
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="reset" class="btn btn-secondary" onclick="resetPOS()">
                    <i class="fas fa-rotate-left"></i> Reset
                </button>
                <button type="submit" class="btn btn-success" id="posSubmitBtn" disabled>
                    <i class="fas fa-check-circle"></i> Complete Sale
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Sales History -->
<div class="card">
    <div class="card-header">
        <div class="toolbar">
            <h3 class="card-title"><i class="fas fa-history" style="margin-right:8px; color: var(--accent);"></i>Sales History</h3>
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control" id="salesSearch" placeholder="Search sales...">
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <?php if (empty($sales)): ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <p>No sales recorded yet. Use the POS above to create your first sale.</p>
            </div>
        <?php else: ?>
        <table class="table" id="salesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Staff</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $i => $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><?= formatDate($s['created_at']) ?></td>
                    <td><strong><?= sanitize($s['product_name']) ?></strong></td>
                    <td><code><?= sanitize($s['sku']) ?></code></td>
                    <td><?= $s['quantity'] ?></td>
                    <td><?= formatCurrency($s['unit_price']) ?></td>
                    <td><strong><?= formatCurrency($s['total_amount']) ?></strong></td>
                    <td><?= sanitize($s['staff_name']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
const posProduct  = document.getElementById('posProduct');
const posQuantity = document.getElementById('posQuantity');
const posSummary  = document.getElementById('posSummary');
const posSubmit   = document.getElementById('posSubmitBtn');

function updatePOS() {
    const option   = posProduct.options[posProduct.selectedIndex];
    const price    = parseFloat(option.dataset.price || 0);
    const stock    = parseInt(option.dataset.stock || 0);
    const name     = option.dataset.name || '';
    const qty      = parseInt(posQuantity.value || 0);
    const qtyError = document.getElementById('posQtyError');

    if (posProduct.value && qty > 0) {
        posSummary.style.display = 'block';
        document.getElementById('summaryProduct').textContent = name;
        document.getElementById('summaryPrice').textContent   = '$' + price.toFixed(2);
        document.getElementById('summaryQty').textContent     = qty;
        document.getElementById('summaryTotal').textContent   = '$' + (price * qty).toFixed(2);

        if (qty > stock) {
            qtyError.textContent = 'Exceeds available stock (' + stock + ' units).';
            qtyError.classList.add('show');
            posQuantity.classList.add('is-invalid');
            posSubmit.disabled = true;
        } else {
            qtyError.classList.remove('show');
            posQuantity.classList.remove('is-invalid');
            posSubmit.disabled = false;
        }
    } else {
        posSummary.style.display = 'none';
        posSubmit.disabled = true;
    }
}

function resetPOS() {
    posSummary.style.display = 'none';
    posSubmit.disabled = true;
    posQuantity.value = '';
    posProduct.value  = '';
}

if (posProduct)  posProduct.addEventListener('change', updatePOS);
if (posQuantity) posQuantity.addEventListener('input', updatePOS);

posProduct.addEventListener('change', () => {
    const option = posProduct.options[posProduct.selectedIndex];
    const stock  = parseInt(option.dataset.stock || 0);
    posQuantity.max = stock;
    posQuantity.value = '';
    updatePOS();
});

initTableSearch('salesSearch', 'salesTable');
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
