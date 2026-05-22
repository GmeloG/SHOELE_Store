<?php
/**
 * API do carrinho — responde a pedidos AJAX
 * Ações: add | update | remove | get
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

switch ($action) {
    case 'add':
        $variantId = (int)($body['variant_id'] ?? 0);
        $qty       = max(1, (int)($body['qty'] ?? 1));
        echo json_encode(cartAdd($variantId, $qty));
        break;

    case 'update':
        $variantId = (int)($body['variant_id'] ?? 0);
        $qty       = (int)($body['qty'] ?? 0);
        $result    = cartUpdate($variantId, $qty);
        $result['cart_count'] = cartCount();
        echo json_encode($result);
        break;

    case 'remove':
        $variantId = (int)($body['variant_id'] ?? 0);
        cartRemove($variantId);
        echo json_encode(['success' => true, 'cart_count' => cartCount()]);
        break;

    case 'get':
        echo json_encode([
            'success' => true,
            'items'   => cartItems(),
            'total'   => cartTotal(),
            'count'   => cartCount(),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
}
