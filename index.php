<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Metrics
$totalProducts = $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalStock    = $db->query('SELECT COALESCE(SUM(quantity), 0) FROM inventory_stock')->fetchColumn();
$totalRevenue  = $db->query('SELECT COALESCE(SUM(total_amount), 0) FROM sales_transactions')->fetchColumn();
$lowStockCount = $db->query('SELECT COUNT(*) FROM inventory_stock WHERE status IN ("low_stock", "out_of_stock")')->fetchColumn();

// Recent sales
$recentSales = $db->query('
    SELECT st.*, p.name AS product_name, u.full_name AS staff_name
    FROM sales_transactions st
    JOIN products p ON st.product_id = p.id
    JOIN users u ON st.processed_by = u.id
    ORDER BY st.created_at DESC
    LIMIT 5
')->fetchAll();

// Low stock items
$lowStockItems = $db->query('
    SELECT ist.*, p.name AS product_name, p.sku
    FROM inventory_stock ist
    JOIN products p ON ist.product_id = p.id
    WHERE ist.status IN ("low_stock", "out_of_stock")
    ORDER BY ist.quantity ASC
    LIMIT 5
')->fetchAll();
?>

<!-- Metrics Cards -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon blue"><i class="fas fa-cube"></i></div>
        <div class="metric-info">
            <div class="metric-label">Total Products</div>
            <div class="metric-value"><?= number_format($totalProducts) ?></div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon green"><i class="fas fa-boxes-stacked"></i></div>
        <div class="metric-info">
            <div class="metric-label">Items in Stock</div>
            <div class="metric-value"><?= number_format($totalStock) ?></div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon yellow"><i class="fas fa-dollar-sign"></i></div>
        <div class="metric-info">
            <div class="metric-label">Total Revenue</div>
            <div class="metric-value"><?= formatCurrency($totalRevenue) ?></div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon red"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="metric-info">
            <div class="metric-label">Low Stock Alerts</div>
            <div class="metric-value"><?= number_format($lowStockCount) ?></div>
        </div>
    </div>
</div>

<!-- Two-column layout -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Recent Sales -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-receipt" style="margin-right:8px; color: var(--accent);"></i>Recent Sales</h3>
            <a href="sales.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-responsive">
            <?php if (empty($recentSales)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>No sales recorded yet.</p>
                </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSales as $sale): ?>
                    <tr>
                        <td><?= sanitize($sale['product_name']) ?></td>
                        <td><?= $sale['quantity'] ?></td>
                        <td><?= formatCurrency($sale['total_amount']) ?></td>
                        <td><?= formatDate($sale['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-triangle-exclamation" style="margin-right:8px; color: var(--danger);"></i>Low Stock Alerts</h3>
            <a href="stock.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-responsive">
            <?php if (empty($lowStockItems)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>All stock levels are healthy.</p>
                </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockItems as $item): ?>
                    <tr>
                        <td><?= sanitize($item['product_name']) ?></td>
                        <td><code><?= sanitize($item['sku']) ?></code></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= getStockStatusBadge($item['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .content > div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
