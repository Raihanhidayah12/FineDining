<?php
session_start();
include '../includes/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

error_log("cancel_reservation.php called: POST=" . print_r($_POST, true) . ", SESSION=" . print_r($_SESSION, true));

if (!isset($_SESSION['id_user']) || !isset($_POST['id_reservasi']) || !isset($_POST['csrf_token'])) {
    error_log("Invalid request: user_id=" . ($_SESSION['id_user'] ?? 'none') . ", id_reservasi=" . ($_POST['id_reservasi'] ?? 'none'));
    echo "Error: Invalid request.";
    exit();
}

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("CSRF token mismatch: sent=" . ($_POST['csrf_token'] ?? 'none') . ", expected=" . ($_SESSION['csrf_token'] ?? 'none'));
    echo "Error: Invalid CSRF token.";
    exit();
}

$reservation_id = intval($_POST['id_reservasi']);
$user_id = intval($_SESSION['id_user']);

// Query pemeriksaan disederhanakan untuk debugging
$stmt = $conn->prepare("
    SELECT r.id_area, r.payment_status, p.status_payment 
    FROM reservasi r 
    LEFT JOIN pembayaran p ON r.id_reservasi = p.id_reservasi 
    WHERE r.id_reservasi = ? AND r.id_user = ?
");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
error_log("Debug check: id_reservasi=$reservation_id, user_id=$user_id, result=" . print_r($result, true));
$stmt->close();

if ($result) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE reservasi SET payment_status = 'Cancelled' WHERE id_reservasi = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        error_log("Updated reservasi: " . $stmt->affected_rows . " rows");
        $stmt->close();

        $stmt = $conn->prepare("UPDATE pembayaran SET status_payment = 'Cancelled', jumlah_dibayar = 0, tanggal_bayar = NOW() WHERE id_reservasi = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        error_log("Updated pembayaran: " . $stmt->affected_rows . " rows");
        $stmt->close();

        $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = 'Cancelled' WHERE id_reservasi = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        error_log("Updated pesanan: " . $stmt->affected_rows . " rows");
        $stmt->close();

        $stmt = $conn->prepare("UPDATE area SET tersedia = 1 WHERE id_area = ?");
        $stmt->bind_param("i", $result['id_area']);
        $stmt->execute();
        error_log("Updated area: " . $stmt->affected_rows . " rows");
        $stmt->close();

        $conn->commit();
        error_log("Cancellation committed for id_reservasi=$reservation_id");
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        echo "Success";
    } catch (Exception $e) {
        $conn->rollback();
        error_log("DB Error: " . $e->getMessage() . ", Code: " . $e->getCode());
        echo "Error: Database error - " . htmlspecialchars($e->getMessage());
    }
} else {
    error_log("Cancellation failed: Reservation not found or not cancellable, id_reservasi=$reservation_id");
    echo "Error: Reservation not found or not cancellable.";
}
?>