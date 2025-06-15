<?php
include '../includes/config.php';

$nama_area = isset($_GET['nama_area']) ? urldecode($_GET['nama_area']) : '';
$tables = [];

if ($nama_area && $conn) {
    $stmt = $conn->prepare("SELECT nomor_meja, kapasitas, tersedia FROM area WHERE nama_area = ? ORDER BY nomor_meja");
    $stmt->bind_param("s", $nama_area);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tables[] = [
            'nomor_meja' => $row['nomor_meja'],
            'kapasitas' => $row['kapasitas'],
            'tersedia' => $row['tersedia']
        ];
    }
    $result->free();
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($tables);
?>