<?php
session_start();
header('Content-Type: application/json');
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi session
    if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in!']);
        exit;
    }

    $id_user = (int)$_SESSION['id_user'];
    $tanggal = !empty($_POST['date']) ? date('Y-m-d', strtotime(str_replace('/', '-', $_POST['date']))) : null;
    $waktu = !empty($_POST['time']) ? date('H:i:00', strtotime(str_replace(' ', '', $_POST['time']))) : null;
    $lokasi_meja = !empty($_POST['nomor_meja']) ? $conn->real_escape_string($_POST['nomor_meja']) : null;
    $nama_area = !empty($_POST['nama_area']) ? $conn->real_escape_string(urldecode($_POST['nama_area'])) : null;

    // Ambil id_area dan kapasitas dari tabel area berdasarkan nomor_meja dan nama_area
    $id_area = null;
    $kapasitas = null;
    if ($nama_area && $lokasi_meja) {
        $area_query = $conn->prepare("SELECT id_area, kapasitas FROM area WHERE nama_area = ? AND nomor_meja = ? LIMIT 1");
        if ($area_query) {
            $area_query->bind_param("ss", $nama_area, $lokasi_meja);
            $area_query->execute();
            $result = $area_query->get_result();
            if ($area_row = $result->fetch_assoc()) {
                $id_area = (int)$area_row['id_area'];
                $kapasitas = (int)$area_row['kapasitas'];
            }
            $area_query->close();
        }
    }
    if ($id_area === null || $kapasitas === null) {
        file_put_contents('debug.log', 'Invalid area or table: ' . $nama_area . ', ' . $lokasi_meja . PHP_EOL, FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Invalid seating area or table!']);
        exit;
    }

    // Ambil id_customer dari tabel customer berdasarkan id_user
    $id_customer = null;
    $customer_query = $conn->prepare("SELECT id_customer FROM customer WHERE id_user = ? LIMIT 1");
    if ($customer_query) {
        $customer_query->bind_param("i", $id_user);
        $customer_query->execute();
        $result = $customer_query->get_result();
        if ($customer_row = $result->fetch_assoc()) {
            $id_customer = (int)$customer_row['id_customer'];
        }
        $customer_query->close();
    }
    if ($id_customer === null) {
        file_put_contents('debug.log', 'Invalid customer for id_user: ' . $id_user . PHP_EOL, FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Customer not found!']);
        exit;
    }

    file_put_contents('debug.log', 'Received: ' . print_r($_POST, true) . PHP_EOL, FILE_APPEND);

    if ($id_customer === null || $tanggal === null || $waktu === null || $lokasi_meja === null || $id_area === null) {
        file_put_contents('debug.log', 'Validation failed: Missing fields' . PHP_EOL, FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi!']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Simpan data reservasi
        $sql = "INSERT INTO reservasi (id_user, id_customer, tanggal, waktu, lokasi_meja, id_area, jumlah_orang, payment_status, catatan_tambahan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending Payment', ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Error preparing statement: ' . $conn->error);
        }
        $catatan_tambahan = !empty($_POST['specialRequests']) ? $conn->real_escape_string($_POST['specialRequests']) : null;
        $stmt->bind_param("iisssiss", $id_user, $id_customer, $tanggal, $waktu, $lokasi_meja, $id_area, $kapasitas, $catatan_tambahan);
        if (!$stmt->execute()) {
            throw new Exception('Error executing statement: ' . $stmt->error);
        }
        $last_id = $conn->insert_id;
        $stmt->close();

        // Ambil id_area dari reservasi untuk pesanan
        $reservasi_query = $conn->query("SELECT id_area FROM reservasi WHERE id_reservasi = $last_id");
        $id_area_from_reservasi = $reservasi_query ? (int)$reservasi_query->fetch_assoc()['id_area'] : $id_area;

        // Hitung total tagihan
        $total_tagihan = 0;
        $food_items = explode(',', $_POST['food']);
        $food_qty = explode(',', $_POST['food_qty']);
        $drinks_items = explode(',', $_POST['drinks']);
        $drinks_qty = explode(',', $_POST['drinks_qty']);
        $dessert_items = explode(',', $_POST['dessert']);
        $dessert_qty = explode(',', $_POST['dessert_qty']);

        $all_items = array_merge($food_items, $drinks_items, $dessert_items);
        $all_qty = array_merge($food_qty, $drinks_qty, $dessert_qty);

        for ($i = 0; $i < count($all_items); $i++) {
            $item = trim($all_items[$i]);
            $qty = isset($all_qty[$i]) ? (int)$all_qty[$i] : 0;
            if (!empty($item) && $qty > 0) {
                $menu_query = $conn->prepare("SELECT harga FROM menu WHERE nama_menu = ? LIMIT 1");
                if ($menu_query) {
                    $menu_query->bind_param("s", $item);
                    $menu_query->execute();
                    $result = $menu_query->get_result();
                    if ($menu_row = $result->fetch_assoc()) {
                        $total_tagihan += (float)$menu_row['harga'] * $qty;
                    }
                    $menu_query->close();
                }
            }
        }

        // Simpan data pesanan
        $existing_orders = [];
        for ($i = 0; $i < count($all_items); $i++) {
            $item = trim($all_items[$i]);
            $qty = isset($all_qty[$i]) ? (int)$all_qty[$i] : 0;
            if (!empty($item) && $qty > 0) {
                if (!isset($existing_orders[$item])) {
                    $existing_orders[$item] = 0;
                }
                $existing_orders[$item] += $qty;

                $sql = "SELECT id_menu FROM menu WHERE nama_menu = ? LIMIT 1";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    throw new Exception('Error preparing menu statement: ' . $conn->error);
                }
                $stmt->bind_param("s", $item);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($menu_row = $result->fetch_assoc()) {
                    $id_menu = (int)$menu_row['id_menu'];
                    $sql = "INSERT INTO pesanan (id_reservasi, id_menu, jumlah, id_area, tanggal_pesanan) VALUES (?, ?, ?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE jumlah = VALUES(jumlah)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        throw new Exception('Error preparing pesanan statement: ' . $conn->error);
                    }
                    $stmt->bind_param("iiii", $last_id, $id_menu, $existing_orders[$item], $id_area_from_reservasi);
                    if (!$stmt->execute()) {
                        throw new Exception('Error executing pesanan statement: ' . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    file_put_contents('debug.log', 'Menu not found: ' . $item . PHP_EOL, FILE_APPEND);
                }
            }
        }

        // Simpan data pembayaran ke tabel pembayaran
        $metode_bayar = 'Tunai'; // Default, bisa diubah berdasarkan input
        $status_payment = 'Pending Payment'; // Default
        $id_kasir = null; // Default, bisa diisi dari session kasir jika ada
        $jumlah_dibayar = 0; // Initial amount, deposit will be handled in pembayaran.php

        $sql = "INSERT INTO pembayaran (id_reservasi, total_tagihan, metode_bayar, status_payment, tanggal_bayar, id_kasir, jumlah_dibayar) 
                VALUES (?, ?, ?, ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Error preparing pembayaran statement: ' . $conn->error);
        }
        $stmt->bind_param("idssii", $last_id, $total_tagihan, $metode_bayar, $status_payment, $id_kasir, $jumlah_dibayar);
        if (!$stmt->execute()) {
            throw new Exception('Error executing pembayaran statement: ' . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        file_put_contents('debug.log', 'Success: Reservation saved with ID ' . $last_id . ' Total Tagihan: ' . $total_tagihan . PHP_EOL, FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Reservasi berhasil disimpan!', 'id_reservasi' => $last_id, 'total_tagihan' => $total_tagihan]);
    } catch (Exception $e) {
        $conn->rollback();
        file_put_contents('debug.log', 'Error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }

    $conn->close();
}
?>