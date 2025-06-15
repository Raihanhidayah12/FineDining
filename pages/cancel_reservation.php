<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['id_user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
$user_id = intval($_SESSION['id_user']);

// Validate reservation belongs to user and is pending
$stmt = $conn->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_reservasi = ? AND status_payment = 'Pending Payment'");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$check = $stmt->get_result();
$stmt->close();

if ($check && $check->num_rows > 0) {
    $conn->begin_transaction();
    try {
        $stmt_update = $conn->prepare("UPDATE pembayaran SET status_payment = 'Cancelled', tanggal_bayar = NOW() WHERE id_reservasi = ?");
        $stmt_update->bind_param("i", $reservation_id);
        $stmt_update->execute();
        $stmt_update->close();

        $stmt_reservasi = $conn->prepare("UPDATE reservasi SET payment_status = 'Cancelled', tanggal_bayar = NOW() WHERE id_reservasi = ?");
        $stmt_reservasi->bind_param("i", $reservation_id);
        $stmt_reservasi->execute();
        $stmt_reservasi->close();

        $conn->commit();
        echo json_encode(['success' => 'Reservation cancelled']);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to cancel reservation: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid reservation or already processed']);
}
?>