<?php
/**
 * API POS — Regista uma venda em loja e atualiza stock
 */
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$items = $body['items'] ?? [];

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Carrinho vazio.']);
    exit;
}

$db = getDB();
$db->beginTransaction();

try {
    $total = 0.0;

    // Verificar stock de todos os itens antes de prosseguir
    foreach ($items as $item) {
        $variantId = (int)($item['variant_id'] ?? 0);
        $qty       = (int)($item['qty'] ?? 0);

        if ($variantId <= 0 || $qty <= 0) {
            throw new Exception('Dados de item inválidos.');
        }

        $stmt = $db->prepare('SELECT stock, COALESCE(pv.price, p.base_price) AS price FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE pv.id = ? FOR UPDATE');
        $stmt->execute([$variantId]);
        $variant = $stmt->fetch();

        if (!$variant) {
            throw new Exception("Variante {$variantId} não encontrada.");
        }
        if ($variant['stock'] < $qty) {
            throw new Exception("Stock insuficiente para variante {$variantId}. Disponível: {$variant['stock']}.");
        }

        $total += (float)$variant['price'] * $qty;
    }

    // Criar registo de venda
    $db->prepare('INSERT INTO sales (sale_type, total) VALUES (?, ?)')
       ->execute(['loja', round($total, 2)]);
    $saleId = (int)$db->lastInsertId();

    // Inserir itens e debitar stock
    foreach ($items as $item) {
        $variantId = (int)$item['variant_id'];
        $qty       = (int)$item['qty'];

        $stmt = $db->prepare('SELECT COALESCE(pv.price, p.base_price) AS price FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE pv.id = ?');
        $stmt->execute([$variantId]);
        $price = (float)$stmt->fetchColumn();

        $db->prepare('INSERT INTO sale_items (sale_id, variant_id, quantity, unit_price) VALUES (?, ?, ?, ?)')
           ->execute([$saleId, $variantId, $qty, $price]);

        $db->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ?')
           ->execute([$qty, $variantId]);

        $db->prepare('INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_id, reference_type, notes) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$variantId, 'saida_pos', -$qty, $saleId, 'sale', "Venda POS #{$saleId}"]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'sale_id' => $saleId,
        'total'   => round($total, 2),
        'message' => "Venda #{$saleId} registada com sucesso.",
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
