<?php
/**
 * Helper Functions
 */

function sanitize(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatCurrency(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function formatDate(string $date): string
{
    return date('M d, Y h:i A', strtotime($date));
}

function generateSKU(string $category): string
{
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $category), 0, 3));
    return $prefix . '-' . strtoupper(substr(uniqid(), -6));
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getStockStatusBadge(string $status): string
{
    return match ($status) {
        'in_stock'     => '<span class="badge badge-success">In Stock</span>',
        'low_stock'    => '<span class="badge badge-warning">Low Stock</span>',
        'out_of_stock' => '<span class="badge badge-danger">Out of Stock</span>',
        default        => '<span class="badge">Unknown</span>',
    };
}

function getRoleBadge(string $role): string
{
    return match ($role) {
        'admin' => '<span class="badge badge-primary">Admin</span>',
        'staff' => '<span class="badge badge-info">Staff</span>',
        default => '<span class="badge">Unknown</span>',
    };
}

function getStatusBadge(string $status): string
{
    return match ($status) {
        'active'   => '<span class="badge badge-success">Active</span>',
        'inactive' => '<span class="badge badge-danger">Inactive</span>',
        default    => '<span class="badge">Unknown</span>',
    };
}
