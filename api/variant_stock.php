<?php
/**
 * API — Devolve variantes/stock de um produto
 * GET ?product_id=X&color=Y      → tamanhos disponíveis para essa cor
 * GET ?product_id=X&all=1        → todas as variantes agrupadas (POS)
 */
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$productId = (int)($_GET['product_id'] ?? 0);
if (!$productId) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id obrigatório.']);
    exit;
}

$db = getDB();

if (isset($_GET['all'])) {
    // Todas as variantes (POS)
    $stmt = $db->prepare("
        SELECT pv.id AS variant_id, pv.color, pv.size, pv.stock,
               COALESCE(pv.price, p.base_price) AS price
        FROM   product_variants pv
        JOIN   products p ON p.id = pv.product_id
        WHERE  pv.product_id = ?
        ORDER  BY pv.color, CAST(pv.size AS UNSIGNED)
    ");
    $stmt->execute([$productId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// Tamanhos para uma cor específica
$color = trim($_GET['color'] ?? '');
if (!$color) {
    http_response_code(400);
    echo json_encode(['error' => 'color obrigatório.']);
    exit;
}

$stmt = $db->prepare("
    SELECT id AS variant_id, size, stock
    FROM   product_variants
    WHERE  product_id = ? AND color = ?
    ORDER  BY CAST(size AS UNSIGNED)
");
$stmt->execute([$productId, $color]);
echo json_encode($stmt->fetchAll());
