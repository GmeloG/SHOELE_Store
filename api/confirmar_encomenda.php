<?php
/**
 * API — Confirmar entrega de encomenda pelo cliente
 * POST JSON: { order_id: int, feedback: string, avaliacao: int }
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || currentRole() !== 'cliente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId   = (int)($data['order_id']  ?? 0);
$feedback  = trim($data['feedback']   ?? '');
$avaliacao = isset($data['avaliacao']) ? max(1, min(5, (int)$data['avaliacao'])) : null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de encomenda inválido.']);
    exit;
}

$userId = (int)currentUser()['id'];
$db     = getDB();

try {
    $stmt = $db->prepare("SELECT id, status, cliente_confirmou FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de base de dados. Verifica se a migração foi aplicada.']);
    exit;
}

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Encomenda não encontrada.']);
    exit;
}

if ($order['status'] !== 'entregue') {
    echo json_encode(['success' => false, 'message' => 'Só é possível confirmar encomendas com estado "Entregue".']);
    exit;
}

if ($order['cliente_confirmou'] ?? 0) {
    echo json_encode(['success' => false, 'message' => 'Encomenda já confirmada anteriormente.']);
    exit;
}

try {
    $db->prepare("UPDATE orders SET status = 'concluida', cliente_confirmou = 1, data_confirmacao = NOW(), feedback_cliente = ?, avaliacao = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$feedback ?: null, $avaliacao, $orderId]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao guardar confirmação. Verifica se a migração foi aplicada.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Encomenda marcada como concluída. Obrigado!']);
