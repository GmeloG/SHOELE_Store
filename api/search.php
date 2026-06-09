<?php
/**
 * API — Pesquisa de produtos em tempo real
 * GET ?q=termo  → devolve até 8 produtos com id, nome, preço, thumb, url
 */
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$db   = getDB();
$term = '%' . $q . '%';

$stmt = $db->prepare("
    SELECT p.id, p.brand, p.model, p.base_price,
           (SELECT filename FROM product_images
            WHERE product_id = p.id ORDER BY sort_order, id LIMIT 1) AS thumb
    FROM   products p
    WHERE  p.active = 1
      AND  (p.brand LIKE ? OR p.model LIKE ? OR CONCAT(p.brand,' ',p.model) LIKE ?)
    ORDER  BY
      CASE WHEN CONCAT(p.brand,' ',p.model) LIKE ? THEN 0 ELSE 1 END,
      p.brand, p.model
    LIMIT  8
");
$stmt->execute([$term, $term, $term, $term]);

$results = array_map(static function (array $p): array {
    return [
        'id'    => $p['id'],
        'name'  => $p['brand'] . ' ' . $p['model'],
        'price' => number_format((float)$p['base_price'], 2, ',', '.') . ' €',
        'thumb' => $p['thumb'] ? BASE_URL.'/uploads/produtos/' . $p['thumb'] : null,
        'url'   => BASE_URL.'/produto.php?id=' . $p['id'],
    ];
}, $stmt->fetchAll());

echo json_encode($results);
