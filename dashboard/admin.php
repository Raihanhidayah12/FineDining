<?php
session_start();
include '../includes/config.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['id_user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Check if username is set, provide a fallback if not
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';

// Get admin ID from session
$id_admin = $_SESSION['id_user'];

// Initialize message
$message = '';

// Fetch all used table numbers and their status
$used_tables = [];
$result = $conn->query("SELECT nomor_meja, tersedia FROM area");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $used_tables[$row['nomor_meja']] = $row['tersedia'];
    }
    $result->free();
}

// Handle form submission for adding a new menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nama_menu = filter_input(INPUT_POST, 'nama_menu', FILTER_SANITIZE_STRING);
    $harga = filter_input(INPUT_POST, 'harga', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $deskripsi = filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_STRING);
    $kategori = filter_input(INPUT_POST, 'kategori', FILTER_SANITIZE_STRING);
    $stok = filter_input(INPUT_POST, 'stok', FILTER_SANITIZE_NUMBER_INT);
    $tersedia = filter_input(INPUT_POST, 'tersedia', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT) ? 1 : 0;

    if (empty($nama_menu) || empty($harga) || empty($kategori) || $stok === null || $stok < 0) {
        $message = '<div class="alert alert-danger">Nama menu, harga, kategori, dan stok harus diisi dengan benar!</div>';
    } else {
        $gambar_menu = null;
        if (isset($_FILES['gambar_menu']) && $_FILES['gambar_menu']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $upload_dir = '../img/';
            $max_file_size = 50 * 1024 * 1024; // 50MB in bytes

            error_log("Upload attempt: " . print_r($_FILES['gambar_menu'], true));

            if ($_FILES['gambar_menu']['error'] !== UPLOAD_ERR_OK) {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server (50MB).',
                    UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form.',
                    UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara tidak ditemukan.',
                    UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
                    UPLOAD_ERR_EXTENSION => 'Ekstensi file tidak diizinkan.'
                ];
                $message = '<div class="alert alert-danger">Upload error: ' . ($upload_errors[$_FILES['gambar_menu']['error']] ?? 'Unknown error') . '</div>';
            } elseif ($_FILES['gambar_menu']['size'] > $max_file_size) {
                $message = '<div class="alert alert-danger">Ukuran file melebihi 50MB!</div>';
            } else {
                if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                    $message = '<div class="alert alert-danger">Gagal membuat direktori gambar!</div>';
                } else {
                    $file_name = uniqid() . '_' . basename($_FILES['gambar_menu']['name']);
                    $file_path = $upload_dir . $file_name;
                    $file_type = mime_content_type($_FILES['gambar_menu']['tmp_name']);

                    if (!in_array($file_type, $allowed_types)) {
                        $message = '<div class="alert alert-danger">Tipe file tidak didukung! Gunakan JPEG, PNG, atau GIF.</div>';
                    } elseif (!move_uploaded_file($_FILES['gambar_menu']['tmp_name'], $file_path)) {
                        $message = '<div class="alert alert-danger">Gagal mengunggah gambar!</div>';
                    } else {
                        $gambar_menu = 'img/' . $file_name;
                    }
                }
            }
        }

        if (empty($message)) {
            $stmt = $conn->prepare("INSERT INTO menu (nama_menu, harga, deskripsi, kategori, stok, tersedia, id_admin, gambar_menu) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sdssiiis", $nama_menu, $harga, $deskripsi, $kategori, $stok, $tersedia, $id_admin, $gambar_menu);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Menu berhasil ditambahkan!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Gagal menambahkan menu: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
            }
        }
    }
}

// Handle form submission for editing a menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id_menu = filter_input(INPUT_POST, 'id_menu', FILTER_SANITIZE_NUMBER_INT);
    $nama_menu = filter_input(INPUT_POST, 'nama_menu', FILTER_SANITIZE_STRING);
    $harga = filter_input(INPUT_POST, 'harga', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $deskripsi = filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_STRING);
    $kategori = filter_input(INPUT_POST, 'kategori', FILTER_SANITIZE_STRING);
    $stok = filter_input(INPUT_POST, 'stok', FILTER_SANITIZE_NUMBER_INT);
    $tersedia = filter_input(INPUT_POST, 'tersedia', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT) ? 1 : 0;

    if (empty($nama_menu) || empty($harga) || empty($kategori) || $stok === null || $stok < 0) {
        $message = '<div class="alert alert-danger">Nama menu, harga, kategori, dan stok harus diisi dengan benar!</div>';
    } else {
        $gambar_menu = null;
        if (isset($_FILES['gambar_menu']) && $_FILES['gambar_menu']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $upload_dir = '../img/';
            $max_file_size = 50 * 1024 * 1024; // 50MB in bytes

            error_log("Edit upload attempt: " . print_r($_FILES['gambar_menu'], true));

            if ($_FILES['gambar_menu']['error'] !== UPLOAD_ERR_OK) {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server (50MB).',
                    UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form.',
                    UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara tidak ditemukan.',
                    UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
                    UPLOAD_ERR_EXTENSION => 'Ekstensi file tidak diizinkan.'
                ];
                $message = '<div class="alert alert-danger">Upload error: ' . ($upload_errors[$_FILES['gambar_menu']['error']] ?? 'Unknown error') . '</div>';
            } elseif ($_FILES['gambar_menu']['size'] > $max_file_size) {
                $message = '<div class="alert alert-danger">Ukuran file melebihi 50MB!</div>';
            } else {
                if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                    $message = '<div class="alert alert-danger">Gagal membuat direktori gambar!</div>';
                } else {
                    $file_name = uniqid() . '_' . basename($_FILES['gambar_menu']['name']);
                    $file_path = $upload_dir . $file_name;
                    $file_type = mime_content_type($_FILES['gambar_menu']['tmp_name']);

                    if (!in_array($file_type, $allowed_types)) {
                        $message = '<div class="alert alert-danger">Tipe file tidak didukung! Gunakan JPEG, PNG, atau GIF.</div>';
                    } elseif (!move_uploaded_file($_FILES['gambar_menu']['tmp_name'], $file_path)) {
                        $message = '<div class="alert alert-danger">Gagal mengunggah gambar!</div>';
                    } else {
                        $gambar_menu = 'img/' . $file_name;
                    }
                }
            }
        }

        if (empty($message)) {
            $query = "UPDATE menu SET nama_menu = ?, harga = ?, deskripsi = ?, kategori = ?, stok = ?, tersedia = ?";
            $params = [$nama_menu, $harga, $deskripsi, $kategori, $stok, $tersedia];
            $types = "sdssii";
            if ($gambar_menu) {
                $query .= ", gambar_menu = ?";
                $params[] = $gambar_menu;
                $types .= "s";
            }
            $query .= " WHERE id_menu = ?";
            $params[] = $id_menu;
            $types .= "i";

            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    if ($stok == 0) {
                        $stmt_tersedia = $conn->prepare("UPDATE menu SET tersedia = 0 WHERE id_menu = ?");
                        if ($stmt_tersedia) {
                            $stmt_tersedia->bind_param("i", $id_menu);
                            $stmt_tersedia->execute();
                            $stmt_tersedia->close();
                        } else {
                            $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
                        }
                    }
                    $message = '<div class="alert alert-success">Menu berhasil diperbarui!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Gagal memperbarui menu: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
            }
        }
    }
}

// Handle form submission for restocking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restock') {
    $id_menu = filter_input(INPUT_POST, 'id_menu', FILTER_SANITIZE_NUMBER_INT);
    $restock_amount = filter_input(INPUT_POST, 'restock_amount', FILTER_SANITIZE_NUMBER_INT);

    if ($restock_amount <= 0) {
        $message = '<div class="alert alert-danger">Jumlah restock harus lebih dari 0!</div>';
    } else {
        $stmt = $conn->prepare("UPDATE menu SET stok = stok + ?, tersedia = 1 WHERE id_menu = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $restock_amount, $id_menu);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Stok berhasil ditambahkan!</div>';
            } else {
                $message = '<div class="alert alert-danger">Gagal menambahkan stok: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
        }
    }
}

// Handle menu deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id_menu = filter_input(INPUT_POST, 'id_menu', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT gambar_menu FROM menu WHERE id_menu = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_menu);
        $stmt->execute();
        $result = $stmt->get_result();
        $menu = $result->fetch_assoc();
        $stmt->close();

        if ($menu && !empty($menu['gambar_menu']) && file_exists('../' . $menu['gambar_menu'])) {
            unlink('../' . $menu['gambar_menu']);
        }

        $stmt = $conn->prepare("DELETE FROM menu WHERE id_menu = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id_menu);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Menu berhasil dihapus!</div>';
            } else {
                $message = '<div class="alert alert-danger">Gagal menghapus menu: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
        }
    }
}

// Handle form submission for adding a new area
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_area') {
    $nama_area = filter_input(INPUT_POST, 'nama_area', FILTER_SANITIZE_STRING);
    $nomor_meja = filter_input(INPUT_POST, 'nomor_meja', FILTER_SANITIZE_NUMBER_INT);
    $deskripsi = filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_STRING);
    $kapasitas = filter_input(INPUT_POST, 'kapasitas', FILTER_SANITIZE_NUMBER_INT);
    $tersedia = filter_input(INPUT_POST, 'tersedia', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT) ? 1 : 0;

    if (empty($nama_area) || empty($nomor_meja) || $kapasitas === null || $kapasitas <= 0 || !in_array($nomor_meja, range(1, 36))) {
        $message = '<div class="alert alert-danger">Nama area, nomor meja, dan kapasitas harus diisi dengan benar! Nomor meja harus antara 1-36.</div>';
    } else {
        $check_stmt = $conn->prepare("SELECT id_area FROM area WHERE nomor_meja = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("i", $nomor_meja);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            if ($result->num_rows > 0) {
                $message = '<div class="alert alert-danger">Nomor meja sudah digunakan!</div>';
            } else {
                $gambar_area = null;
                if (isset($_FILES['gambar_area']) && $_FILES['gambar_area']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $upload_dir = '../img/';
                    $max_file_size = 50 * 1024 * 1024;

                    error_log("Area upload attempt: " . print_r($_FILES['gambar_area'], true));

                    if ($_FILES['gambar_area']['error'] !== UPLOAD_ERR_OK) {
                        $upload_errors = [
                            UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server (50MB).',
                            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form.',
                            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara tidak ditemukan.',
                            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
                            UPLOAD_ERR_EXTENSION => 'Ekstensi file tidak diizinkan.'
                        ];
                        $message = '<div class="alert alert-danger">Upload error: ' . ($upload_errors[$_FILES['gambar_area']['error']] ?? 'Unknown error') . '</div>';
                    } elseif ($_FILES['gambar_area']['size'] > $max_file_size) {
                        $message = '<div class="alert alert-danger">Ukuran file melebihi 50MB!</div>';
                    } else {
                        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                            $message = '<div class="alert alert-danger">Gagal membuat direktori gambar!</div>';
                        } else {
                            $file_name = uniqid() . '_' . basename($_FILES['gambar_area']['name']);
                            $file_path = $upload_dir . $file_name;
                            $file_type = mime_content_type($_FILES['gambar_area']['tmp_name']);

                            if (!in_array($file_type, $allowed_types)) {
                                $message = '<div class="alert alert-danger">Tipe file tidak didukung! Gunakan JPEG, PNG, atau GIF.</div>';
                            } elseif (!move_uploaded_file($_FILES['gambar_area']['tmp_name'], $file_path)) {
                                $message = '<div class="alert alert-danger">Gagal mengunggah gambar!</div>';
                            } else {
                                $gambar_area = 'img/' . $file_name;
                            }
                        }
                    }
                }

                if (empty($message)) {
                    $insert_stmt = $conn->prepare("INSERT INTO area (nama_area, nomor_meja, deskripsi, kapasitas, tersedia, id_admin, gambar_area) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("sssiiis", $nama_area, $nomor_meja, $deskripsi, $kapasitas, $tersedia, $id_admin, $gambar_area);
                        if ($insert_stmt->execute()) {
                            $message = '<div class="alert alert-success">Area berhasil ditambahkan!</div>';
                        } else {
                            $message = '<div class="alert alert-danger">Gagal menambahkan area: ' . $insert_stmt->error . '</div>';
                        }
                        $insert_stmt->close();
                    } else {
                        $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
                    }
                }
            }
            $check_stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
        }
    }
}

// Handle form submission for editing an area
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_area') {
    $id_area = filter_input(INPUT_POST, 'id_area', FILTER_SANITIZE_NUMBER_INT);
    $nama_area = filter_input(INPUT_POST, 'nama_area', FILTER_SANITIZE_STRING);
    $nomor_meja = filter_input(INPUT_POST, 'nomor_meja', FILTER_SANITIZE_NUMBER_INT);
    $deskripsi = filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_STRING);
    $kapasitas = filter_input(INPUT_POST, 'kapasitas', FILTER_SANITIZE_NUMBER_INT);
    $tersedia = filter_input(INPUT_POST, 'tersedia', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT) ? 1 : 0;

    if (empty($nama_area) || empty($nomor_meja) || $kapasitas === null || $kapasitas <= 0 || !in_array($nomor_meja, range(1, 36))) {
        $message = '<div class="alert alert-danger">Nama area, nomor meja, dan kapasitas harus diisi dengan benar! Nomor meja harus antara 1-36.</div>';
    } else {
        $stmt = $conn->prepare("SELECT id_area FROM area WHERE nomor_meja = ? AND id_area != ?");
        if ($stmt) {
            $stmt->bind_param("ii", $nomor_meja, $id_area);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $message = '<div class="alert alert-danger">Nomor meja sudah digunakan oleh area lain!</div>';
            } else {
                $gambar_area = null;
                if (isset($_FILES['gambar_area']) && $_FILES['gambar_area']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $upload_dir = '../img/';
                    $max_file_size = 50 * 1024 * 1024;

                    error_log("Edit area upload attempt: " . print_r($_FILES['gambar_area'], true));

                    if ($_FILES['gambar_area']['error'] !== UPLOAD_ERR_OK) {
                        $upload_errors = [
                            UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server (50MB).',
                            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form.',
                            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara tidak ditemukan.',
                            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
                            UPLOAD_ERR_EXTENSION => 'Ekstensi file tidak diizinkan.'
                        ];
                        $message = '<div class="alert alert-danger">Upload error: ' . ($upload_errors[$_FILES['gambar_area']['error']] ?? 'Unknown error') . '</div>';
                    } elseif ($_FILES['gambar_area']['size'] > $max_file_size) {
                        $message = '<div class="alert alert-danger">Ukuran file melebihi 50MB!</div>';
                    } else {
                        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                            $message = '<div class="alert alert-danger">Gagal membuat direktori gambar!</div>';
                        } else {
                            $file_name = uniqid() . '_' . basename($_FILES['gambar_area']['name']);
                            $file_path = $upload_dir . $file_name;
                            $file_type = mime_content_type($_FILES['gambar_area']['tmp_name']);

                            if (!in_array($file_type, $allowed_types)) {
                                $message = '<div class="alert alert-danger">Tipe file tidak didukung! Gunakan JPEG, PNG, atau GIF.</div>';
                            } elseif (!move_uploaded_file($_FILES['gambar_area']['tmp_name'], $file_path)) {
                                $message = '<div class="alert alert-danger">Gagal mengunggah gambar!</div>';
                            } else {
                                $gambar_area = 'img/' . $file_name;
                            }
                        }
                    }
                }

                if (empty($message)) {
                    $query = "UPDATE area SET nama_area = ?, nomor_meja = ?, deskripsi = ?, kapasitas = ?, tersedia = ?";
                    $params = [$nama_area, $nomor_meja, $deskripsi, $kapasitas, $tersedia];
                    $types = "sssii";
                    if ($gambar_area) {
                        $query .= ", gambar_area = ?";
                        $params[] = $gambar_area;
                        $types .= "s";
                    }
                    $query .= " WHERE id_area = ?";
                    $params[] = $id_area;
                    $types .= "i";

                    $stmt_update = $conn->prepare($query);
                    if ($stmt_update) {
                        $stmt_update->bind_param($types, ...$params);
                        if ($stmt_update->execute()) {
                            $message = '<div class="alert alert-success">Area berhasil diperbarui!</div>';
                        } else {
                            $message = '<div class="alert alert-danger">Gagal memperbarui area: ' . $stmt_update->error . '</div>';
                        }
                        $stmt_update->close();
                    } else {
                        $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
                    }
                }
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
        }
    }
}

// Handle area deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_area') {
    $id_area = filter_input(INPUT_POST, 'id_area', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT gambar_area FROM area WHERE id_area = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_area);
        $stmt->execute();
        $result = $stmt->get_result();
        $area = $result->fetch_assoc();
        $stmt->close();

        if ($area && !empty($area['gambar_area']) && file_exists('../' . $area['gambar_area'])) {
            unlink('../' . $area['gambar_area']);
        }

        $stmt = $conn->prepare("DELETE FROM area WHERE id_area = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id_area);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Area berhasil dihapus!</div>';
            } else {
                $message = '<div class="alert alert-danger">Gagal menghapus area: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
        }
    }
}

// Fetch statistics data
$total_pendapatan = 0;
$total_pesanan = 0;
$pelanggan_baru = 0;
$rating = 0;

$result = $conn->query("SELECT COUNT(*) as total FROM pesanan");
if ($result) {
    $row = $result->fetch_assoc();
    $total_pesanan = $row['total'];
    $result->free();
}

$result = $conn->query("SELECT SUM(p.jumlah * m.harga) as total FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu");
if ($result) {
    $row = $result->fetch_assoc();
    $total_pendapatan = $row['total'] ?? 0;
    $result->free();
}

$bulan_ini = date('Y-m');
$result = $conn->query("SELECT COUNT(*) as total FROM customer WHERE DATE_FORMAT(tanggal_daftar, '%Y-%m') = '$bulan_ini'");
if ($result) {
    $row = $result->fetch_assoc();
    $pelanggan_baru = $row['total'];
    $result->free();
}

$result = $conn->query("SHOW TABLES LIKE 'review'");
if ($result && $result->num_rows > 0) {
    $result->free();
    $rating_result = $conn->query("SELECT AVG(rating) as avg_rating FROM review");
    if ($rating_result) {
        $row = $rating_result->fetch_assoc();
        $rating = round($row['avg_rating'] ?? 0, 1);
        $rating_result->free();
    }
}

// Fetch recent orders
$recent_orders = [];
$status_column_exists = false;
$id_customer_column_exists = false;

$column_check = $conn->query("SHOW COLUMNS FROM pesanan LIKE 'status'");
if ($column_check && $column_check->num_rows > 0) {
    $status_column_exists = true;
    $column_check->free();
}

$column_check = $conn->query("SHOW COLUMNS FROM pesanan LIKE 'id_customer'");
if ($column_check && $column_check->num_rows > 0) {
    $id_customer_column_exists = true;
    $column_check->free();
}

$query = "SELECT p.id_pesanan, ";
if ($id_customer_column_exists) {
    $query .= "c.nama_lengkap, ";
} else {
    $query .= "'Unknown Customer' as nama_lengkap, ";
}
$query .= "m.nama_menu as menu, (p.jumlah * m.harga) as total_harga, a.nama_area, a.nomor_meja";
if ($status_column_exists) {
    $query .= ", p.status";
} else {
    $query .= ", CASE 
        WHEN p.tanggal_selesai IS NOT NULL THEN 'Completed' 
        ELSE 'Pending' 
    END as status";
}
$query .= " FROM pesanan p";
if ($id_customer_column_exists) {
    $query .= " JOIN customer c ON p.id_customer = c.id_customer";
}
$query .= " JOIN menu m ON p.id_menu = m.id_menu";
$query .= " LEFT JOIN area a ON p.id_area = a.id_area"; // Mengubah join ke area langsung dari pesanan
$query .= " ORDER BY p.tanggal_pesanan DESC LIMIT 5";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
    $result->free();
}

// Fetch users and customers for Kelola Akun
$users = [];
$result = $conn->query("SELECT * FROM user");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

$customers = [];
$result = $conn->query("SELECT * FROM customer");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    $result->free();
}

// Fetch areas for Daftar Area
$areas = [];
$result = $conn->query("SELECT * FROM area ORDER BY nama_area");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $areas[] = $row;
    }
    $result->free();
}

// Determine active section
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Fetch menu item for editing or viewing
$edit_menu = null;
$view_menu = null;
if ($section === 'edit_menu' || $section === 'view_menu') {
    $id_menu = filter_input(INPUT_GET, 'id_menu', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT * FROM menu WHERE id_menu = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_menu);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $edit_menu = $result->fetch_assoc();
            $view_menu = $edit_menu;
        }
        $stmt->close();
    }
}

// Fetch area for editing or viewing
$edit_area = null;
$view_area = null;
if ($section === 'edit_area' || $section === 'view_area') {
    $id_area = filter_input(INPUT_GET, 'id_area', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT * FROM area WHERE id_area = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_area);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $edit_area = $result->fetch_assoc();
            $view_area = $edit_area;
        }
        $stmt->close();
    }
}

// Update tersedia status for zero stock items
$stmt = $conn->prepare("UPDATE menu SET tersedia = 0 WHERE stok = 0 AND tersedia = 1");
if ($stmt) {
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Dashboard Admin FineDining">
  <title>Admin Dashboard - FineDining</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #f4f4f9;
      color: #333;
      min-height: 100vh;
      display: flex;
    }

    .sidebar {
      width: 250px;
      background: #fff;
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
      padding: 20px;
      height: 100vh;
      position: fixed;
    }

    .sidebar h2 {
      color: #333;
      font-size: 20px;
      margin-bottom: 30px;
      text-align: center;
    }

    .sidebar ul {
      list-style: none;
    }

    .sidebar ul li {
      margin-bottom: 10px;
    }

    .sidebar ul li a {
      display: flex;
      align-items: center;
      padding: 10px;
      color: #333;
      text-decoration: none;
      border-radius: 5px;
      transition: background 0.3s;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background: #f1c40f;
      color: #fff;
    }

    .sidebar ul li a i {
      margin-right: 10px;
    }

    .main-content {
      margin-left: 250px;
      padding: 20px;
      width: calc(100% - 250px);
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #fff;
      padding: 15px 30px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
    }

    .header h1 {
      font-size: 24px;
      color: #333;
    }

    .header .user {
      display: flex;
      align-items: center;
    }

    .header .user i {
      margin-left: 10px;
      color: #333;
    }

    .menu-card, .area-card {
      position: relative;
      transition: transform 0.2s;
    }

    .menu-card:hover, .area-card:hover {
      transform: translateY(-5px);
    }

    .restock-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 10;
    }

    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1055;
    }

    .table-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 5px;
      max-width: 600px;
      margin-top: 10px;
    }

    .table-cell {
      border: 2px solid #ccc;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      background-color: #fff;
      transition: background-color 0.3s;
    }

    .table-cell:hover:not(.selected-red):not(.selected-green) {
      background-color: #f0f0f0;
    }

    .table-cell.selected {
      background-color: #ffd700;
      border-color: #ffd700;
    }

    .table-cell.selected-red {
      background-color: #ff0000;
      border-color: #ff0000;
      color: #fff;
      cursor: not-allowed;
    }

    .table-cell.selected-green {
      background-color: #00ff00;
      border-color: #00ff00;
      color: #fff;
      cursor: not-allowed;
    }
  </style>
</head>
<body>
  <!-- Toast Notification -->
  <div class="toast-container">
    <?php
    $result = $conn->query("SELECT id_menu, nama_menu FROM menu WHERE stok = 0");
    while ($row = $result->fetch_assoc()): ?>
    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
      <div class="toast-header bg-warning text-dark">
        <strong class="me-auto">Peringatan Stok</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">
        Stok untuk <strong><?php echo htmlspecialchars($row['nama_menu']); ?></strong> telah habis! Silakan restok.
      </div>
    </div>
    <?php endwhile; $result->free(); ?>
  </div>

  <div class="sidebar">
    <h2>FineDining Admin</h2>
    <ul>
      <li><a href="?section=dashboard" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Statistik</a></li>
      <li><a href="?section=kelola_menu" class="<?php echo $section === 'kelola_menu' ? 'active' : ''; ?>"><i class="fas fa-utensils"></i> Kelola Menu</a></li>
      <li><a href="?section=menu" class="<?php echo $section === 'menu' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Daftar Menu</a></li>
      <li><a href="?section=kelola_area" class="<?php echo $section === 'kelola_area' ? 'active' : ''; ?>"><i class="fas fa-map"></i> Kelola Area</a></li>
      <li><a href="?section=daftar_area" class="<?php echo $section === 'daftar_area' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Daftar Area</a></li>
      <li><a href="?section=kelola_akun" class="<?php echo $section === 'kelola_akun' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Kelola Akun</a></li>
    </ul>
  </div>

  <div class="main-content">
    <div class="header">
      <h1>
        <?php
        if ($section === 'dashboard') echo 'Dashboard Admin';
        elseif ($section === 'kelola_menu') echo 'Kelola Menu';
        elseif ($section === 'menu') echo 'Menu';
        elseif ($section === 'kelola_area') echo 'Kelola Area';
        elseif ($section === 'daftar_area') echo 'Daftar Area';
        elseif ($section === 'kelola_akun') echo 'Kelola Akun';
        elseif ($section === 'edit_menu') echo 'Edit Menu';
        elseif ($section === 'view_menu') echo 'Detail Menu';
        elseif ($section === 'edit_area') echo 'Edit Area';
        elseif ($section === 'view_area') echo 'Detail Area';
        ?>
      </h1>
      <div class="user">
        Admin
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </div>

    <?php echo $message; ?>

    <?php if ($section === 'dashboard'): ?>
    <div class="stats d-flex gap-3 mb-4">
      <div class="card flex-fill text-center">
        <div class="card-body">
          <h3 class="card-title">Total Pendapatan</h3>
          <p class="card-text fs-3 fw-bold">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></p>
          <small class="text-muted">+<?php echo round(($total_pendapatan / 1000000) * 100, 0); ?>% dari bulan lalu</small>
        </div>
      </div>
      <div class="card flex-fill text-center">
        <div class="card-body">
          <h3 class="card-title">Total Pesanan</h3>
          <p class="card-text fs-3 fw-bold"><?php echo $total_pesanan; ?></p>
          <small class="text-muted">+<?php echo round(($total_pesanan / 100) * 100, 0); ?>% dari bulan lalu</small>
        </div>
      </div>
      <div class="card flex-fill text-center">
        <div class="card-body">
          <h3 class="card-title">Pelanggan Baru</h3>
          <p class="card-text fs-3 fw-bold"><?php echo $pelanggan_baru; ?></p>
          <small class="text-muted">+<?php echo $pelanggan_baru > 0 ? $pelanggan_baru * 10 : 0; ?>% dari bulan lalu</small>
        </div>
      </div>
      <div class="card flex-fill text-center">
        <div class="card-body">
          <h3 class="card-title">Rating</h3>
          <p class="card-text fs-3 fw-bold"><?php echo $rating; ?></p>
          <small class="text-muted">+<?php echo $rating > 0 ? 0.2 : 0; ?> dari bulan lalu</small>
        </div>
      </div>
    </div>

    <div class="recent-orders">
        <h3>Pesanan Terbaru</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Pelanggan</th>
                    <th>Menu</th>
                    <th>Area</th>
                    <th>Nomor Meja</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                <tr><td colspan="7" class="text-center">Belum ada pesanan.</td></tr>
                <?php else: ?>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($order['id_pesanan']); ?></td>
                    <td><?php echo htmlspecialchars($order['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($order['menu']); ?></td>
                    <td><?php echo htmlspecialchars($order['nama_area'] ?? 'Tidak ada area'); ?></td>
                    <td><?php echo htmlspecialchars($order['nomor_meja'] ?? 'Tidak ada nomor'); ?></td>
                    <td>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></td>
                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($section === 'kelola_menu'): ?>
    <h3>Tambah Menu Baru</h3>
    <form method="POST" action="" enctype="multipart/form-data" class="mt-4">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
      <div class="mb-3">
        <label for="nama_menu" class="form-label">Nama Menu</label>
        <input type="text" class="form-control" id="nama_menu" name="nama_menu" required>
      </div>
      <div class="mb-3">
        <label for="harga" class="form-label">Harga (Rp)</label>
        <input type="number" class="form-control" id="harga" name="harga" step="0.01" required>
      </div>
      <div class="mb-3">
        <label for="kategori" class="form-label">Kategori</label>
        <select class="form-select" id="kategori" name="kategori" required>
          <option value="">Pilih Kategori</option>
          <option value="Makanan">Makanan</option>
          <option value="Minuman">Minuman</option>
          <option value="Dessert">Dessert</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="deskripsi" class="form-label">Deskripsi</label>
        <textarea class="form-control" id="deskripsi" name="deskripsi"></textarea>
      </div>
      <div class="mb-3">
        <label for="stok" class="form-label">Stok Awal</label>
        <input type="number" class="form-control" id="stok" name="stok" required min="0">
      </div>
      <div class="mb-3">
        <label for="tersedia" class="form-label">Status Ketersediaan</label>
        <select class="form-select" id="tersedia" name="tersedia" required>
          <option value="1">Tersedia</option>
          <option value="0">Tidak Tersedia</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="gambar_menu" class="form-label">Gambar Menu (Max 50MB, JPEG/PNG/GIF)</label>
        <input type="file" class="form-control" id="gambar_menu" name="gambar_menu" accept="image/jpeg,image/png,image/gif">
      </div>
      <button type="submit" class="btn btn-primary">Tambah Menu</button>
    </form>

    <?php elseif ($section === 'edit_menu' && $edit_menu): ?>
    <h3>Edit Menu</h3>
    <form method="POST" action="" enctype="multipart/form-data" class="mt-4">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id_menu" value="<?php echo htmlspecialchars($edit_menu['id_menu']); ?>">
      <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
      <div class="mb-3">
        <label for="nama_menu" class="form-label">Nama Menu</label>
        <input type="text" class="form-control" id="nama_menu" name="nama_menu" value="<?php echo htmlspecialchars($edit_menu['nama_menu']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="harga" class="form-label">Harga (Rp)</label>
        <input type="number" class="form-control" id="harga" name="harga" step="0.01" value="<?php echo htmlspecialchars($edit_menu['harga']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="kategori" class="form-label">Kategori</label>
        <select class="form-select" id="kategori" name="kategori" required>
          <option value="Makanan" <?php echo $edit_menu['kategori'] === 'Makanan' ? 'selected' : ''; ?>>Makanan</option>
          <option value="Minuman" <?php echo $edit_menu['kategori'] === 'Minuman' ? 'selected' : ''; ?>>Minuman</option>
          <option value="Dessert" <?php echo $edit_menu['kategori'] === 'Dessert' ? 'selected' : ''; ?>>Dessert</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="deskripsi" class="form-label">Deskripsi</label>
        <textarea class="form-control" id="deskripsi" name="deskripsi"><?php echo htmlspecialchars($edit_menu['deskripsi']); ?></textarea>
      </div>
      <div class="mb-3">
        <label for="stok" class="form-label">Stok</label>
        <input type="number" class="form-control" id="stok" name="stok" value="<?php echo htmlspecialchars($edit_menu['stok']); ?>" required min="0">
      </div>
      <div class="mb-3">
        <label for="tersedia" class="form-label">Status Ketersediaan</label>
        <select class="form-select" id="tersedia" name="tersedia" required>
          <option value="1" <?php echo $edit_menu['tersedia'] ? 'selected' : ''; ?>>Tersedia</option>
          <option value="0" <?php echo !$edit_menu['tersedia'] ? 'selected' : ''; ?>>Tidak Tersedia</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="gambar_menu" class="form-label">Gambar Menu (Max 50MB, Biarkan kosong jika tidak ingin mengganti)</label>
        <input type="file" class="form-control" id="gambar_menu" name="gambar_menu" accept="image/jpeg,image/png,image/gif">
        <?php
        $image_path = !empty($edit_menu['gambar_menu']) && $edit_menu['gambar_menu'] !== '0' ? '../' . $edit_menu['gambar_menu'] : '';
        if (!empty($image_path) && file_exists($image_path)): ?>
        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($edit_menu['nama_menu']); ?>" class="mt-2" style="max-width: 200px;">
        <?php else: ?>
        <div class="bg-light text-center mt-2" style="max-width: 200px; height: 100px; display: flex; align-items: center; justify-content: center;">
          <span class="text-muted"><?php echo !empty($edit_menu['gambar_menu']) ? 'Gambar tidak ditemukan: ' . htmlspecialchars($edit_menu['gambar_menu']) : 'Tidak ada gambar'; ?></span>
        </div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="?section=menu" class="btn btn-secondary">Kembali</a>
    </form>

    <?php elseif ($section === 'view_menu' && $view_menu): ?>
    <h3>Detail Menu</h3>
    <div class="card mb-4">
      <div class="card-body">
        <?php
        $image_path = !empty($view_menu['gambar_menu']) && $view_menu['gambar_menu'] !== '0' ? '../' . $view_menu['gambar_menu'] : '';
        if (!empty($image_path) && file_exists($image_path)): ?>
        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($view_menu['nama_menu']); ?>" class="mb-3" style="max-width: 300px; border-radius: 5px;">
        <?php else: ?>
        <div class="bg-light text-center mb-3" style="width: 300px; height: 200px; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
          <span class="text-muted"><?php echo !empty($view_menu['gambar_menu']) ? 'Gambar tidak ditemukan: ' . htmlspecialchars($view_menu['gambar_menu']) : 'Tidak ada gambar'; ?></span>
        </div>
        <?php endif; ?>
        <h5 class="card-title"><?php echo htmlspecialchars($view_menu['nama_menu']); ?></h5>
        <p class="card-text"><strong>Harga:</strong> Rp <?php echo number_format($view_menu['harga'], 0, ',', '.'); ?></p>
        <p class="card-text"><strong>Kategori:</strong> <?php echo htmlspecialchars($view_menu['kategori']); ?></p>
        <p class="card-text"><strong>Deskripsi:</strong> <?php echo htmlspecialchars($view_menu['deskripsi'] ?: 'Tidak ada deskripsi'); ?></p>
        <p class="card-text"><strong>Stok:</strong> <?php echo htmlspecialchars($view_menu['stok']); ?></p>
        <p class="card-text"><strong>Status:</strong> <span class="badge <?php echo $view_menu['tersedia'] ? 'bg-success' : 'bg-danger'; ?>">
          <?php echo $view_menu['tersedia'] ? 'Tersedia' : 'Tidak Tersedia'; ?></span></p>
        <a href="?section=edit_menu&id_menu=<?php echo $view_menu['id_menu']; ?>" class="btn btn-primary">Edit</a>
        <a href="?section=menu" class="btn btn-secondary">Kembali</a>
      </div>
    </div>

    <?php elseif ($section === 'menu'): ?>
    <h3>Daftar Menu</h3>
    <?php
    $menu_items = [];
    $result = $conn->query("SELECT * FROM menu ORDER BY kategori, nama_menu");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $menu_items[$row['kategori']][] = $row;
        }
        $result->free();
    } else {
        echo '<div class="alert alert-danger">Gagal mengambil data menu: ' . htmlspecialchars($conn->error) . '</div>';
    }
    $categories = ['Makanan', 'Minuman', 'Dessert'];
    ?>

    <?php if (empty($menu_items)): ?>
    <div class="alert alert-info">Belum ada menu yang ditambahkan.</div>
    <?php else: ?>
    <?php foreach ($categories as $category): ?>
    <?php if (isset($menu_items[$category]) && !empty($menu_items[$category])): ?>
    <h4 class="mt-4"><?php echo htmlspecialchars($category); ?></h4>
    <div class="row row-cols-1 row-cols-md-3 g-4">
      <?php foreach ($menu_items[$category] as $menu): ?>
      <div class="col">
        <div class="card h-100 menu-card position-relative">
          <?php if ($menu['stok'] == 0): ?>
          <form method="POST" action="" class="restock-btn">
            <input type="hidden" name="action" value="restock">
            <input type="hidden" name="id_menu" value="<?php echo $menu['id_menu']; ?>">
            <div class="input-group input-group-sm">
              <input type="number" name="restock_amount" class="form-control" placeholder="Jumlah" min="1" required>
              <button type="submit" class="btn btn-warning btn-sm">Restok</button>
            </div>
          </form>
          <?php endif; ?>
          <div class="card-body">
            <?php
            $image_path = !empty($menu['gambar_menu']) && $menu['gambar_menu'] !== '0' ? '../' . $menu['gambar_menu'] : '';
            if (!empty($image_path) && file_exists($image_path)): ?>
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($menu['nama_menu']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
            <?php else: ?>
            <div class="bg-light text-center" style="height: 150px; display: flex; align-items: center; justify-content: center;">
              <span class="text-muted"><?php echo !empty($menu['gambar_menu']) ? 'Gambar tidak ditemukan: ' . htmlspecialchars($menu['gambar_menu']) : 'Tidak ada gambar'; ?></span>
            </div>
            <?php endif; ?>
            <h5 class="card-title mt-2"><?php echo htmlspecialchars($menu['nama_menu']); ?></h5>
            <p class="card-text"><strong>Harga:</strong> Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></p>
            <p class="card-text"><strong>Stok:</strong> <?php echo htmlspecialchars($menu['stok']); ?></p>
            <p class="card-text"><strong>Status:</strong> <span class="badge <?php echo $menu['tersedia'] ? 'bg-success' : 'bg-danger'; ?>">
              <?php echo $menu['tersedia'] ? 'Tersedia' : 'Tidak Tersedia'; ?></span></p>
            <div class="d-flex gap-2">
              <a href="?section=view_menu&id_menu=<?php echo $menu['id_menu']; ?>" class="btn btn-info btn-sm">Lihat</a>
              <a href="?section=edit_menu&id_menu=<?php echo $menu['id_menu']; ?>" class="btn btn-primary btn-sm">Edit</a>
              <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus menu ini?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_menu" value="<?php echo $menu['id_menu']; ?>">
                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php elseif ($section === 'kelola_area'): ?>
    <h3>Tambah Area Baru</h3>
    <form method="POST" action="" enctype="multipart/form-data" class="mt-4">
      <input type="hidden" name="action" value="add_area">
      <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
      <div class="mb-3">
        <label for="nama_area" class="form-label">Nama Area</label>
        <input type="text" class="form-control" id="nama_area" name="nama_area" required>
      </div>
      <div class="mb-3">
        <label for="nomor_meja" class="form-label">Pilih Nomor Meja</label>
        <div class="table-grid" id="table-grid">
          <?php for ($i = 1; $i <= 36; $i++): ?>
            <div class="table-cell <?php echo isset($used_tables[$i]) ? ($used_tables[$i] ? 'selected-green' : 'selected-red') : ''; ?>" 
                 data-value="<?php echo $i; ?>" 
                 onclick="selectTable(this, <?php echo isset($used_tables[$i]) ? 'true' : 'false'; ?>)">
              <?php echo $i; ?>
            </div>
          <?php endfor; ?>
        </div>
        <input type="hidden" id="nomor_meja" name="nomor_meja" required>
      </div>
      <div class="mb-3">
        <label for="deskripsi" class="form-label">Deskripsi</label>
        <textarea class="form-control" id="deskripsi" name="deskripsi"></textarea>
      </div>
      <div class="mb-3">
        <label for="kapasitas" class="form-label">Kapasitas (Jumlah Orang)</label>
        <input type="number" class="form-control" id="kapasitas" name="kapasitas" required min="1">
      </div>
      <div class="mb-3">
        <label for="tersedia" class="form-label">Status Ketersediaan</label>
        <select class="form-select" id="tersedia" name="tersedia" required>
          <option value="1">Tersedia</option>
          <option value="0">Penuh</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="gambar_area" class="form-label">Gambar Area (Max 50MB, JPEG/PNG/GIF)</label>
        <input type="file" class="form-control" id="gambar_area" name="gambar_area" accept="image/jpeg,image/png,image/gif">
      </div>
      <button type="submit" class="btn btn-primary">Tambah Area</button>
    </form>

    <?php elseif ($section === 'edit_area' && $edit_area): ?>
    <h3>Edit Area</h3>
    <form method="POST" action="" enctype="multipart/form-data" class="mt-4">
      <input type="hidden" name="action" value="edit_area">
      <input type="hidden" name="id_area" value="<?php echo htmlspecialchars($edit_area['id_area']); ?>">
      <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
      <div class="mb-3">
        <label for="nama_area" class="form-label">Nama Area</label>
        <input type="text" class="form-control" id="nama_area" name="nama_area" value="<?php echo htmlspecialchars($edit_area['nama_area']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="nomor_meja" class="form-label">Pilih Nomor Meja</label>
        <div class="table-grid" id="table-grid">
          <?php for ($i = 1; $i <= 36; $i++): ?>
            <div class="table-cell <?php echo isset($used_tables[$i]) ? ($used_tables[$i] ? 'selected-green' : 'selected-red') : ''; ?> 
                  <?php echo $edit_area['nomor_meja'] == $i ? 'selected' : ''; ?>" 
                  data-value="<?php echo $i; ?>" 
                  onclick="selectTable(this, <?php echo isset($used_tables[$i]) ? 'true' : 'false'; ?>, <?php echo $edit_area['nomor_meja'] == $i ? 'true' : 'false'; ?>)">
              <?php echo $i; ?>
            </div>
          <?php endfor; ?>
        </div>
        <input type="hidden" id="nomor_meja" name="nomor_meja" value="<?php echo htmlspecialchars($edit_area['nomor_meja']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="deskripsi" class="form-label">Deskripsi</label>
        <textarea class="form-control" id="deskripsi" name="deskripsi"><?php echo htmlspecialchars($edit_area['deskripsi']); ?></textarea>
      </div>
      <div class="mb-3">
        <label for="kapasitas" class="form-label">Kapasitas (Jumlah Orang)</label>
        <input type="number" class="form-control" id="kapasitas" name="kapasitas" value="<?php echo htmlspecialchars($edit_area['kapasitas']); ?>" required min="1">
      </div>
      <div class="mb-3">
        <label for="tersedia" class="form-label">Status Ketersediaan</label>
        <select class="form-select" id="tersedia" name="tersedia" required>
          <option value="1" <?php echo $edit_area['tersedia'] ? 'selected' : ''; ?>>Tersedia</option>
          <option value="0" <?php echo !$edit_area['tersedia'] ? 'selected' : ''; ?>>Penuh</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="gambar_area" class="form-label">Gambar Area (Max 50MB, Biarkan kosong jika tidak ingin mengganti)</label>
        <input type="file" class="form-control" id="gambar_area" name="gambar_area" accept="image/jpeg,image/png,image/gif">
        <?php
        $image_path = !empty($edit_area['gambar_area']) && $edit_area['gambar_area'] !== '0' ? '../' . $edit_area['gambar_area'] : '';
        if (!empty($image_path) && file_exists($image_path)): ?>
        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($edit_area['nama_area']); ?>" class="mt-2" style="max-width: 200px;">
        <?php else: ?>
        <div class="bg-light text-center mt-2" style="max-width: 200px; height: 100px; display: flex; align-items: center; justify-content: center;">
          <span class="text-muted"><?php echo !empty($edit_area['gambar_area']) ? 'Gambar tidak ditemukan: ' . htmlspecialchars($edit_area['gambar_area']) : 'Tidak ada gambar'; ?></span>
        </div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="?section=daftar_area" class="btn btn-secondary">Kembali</a>
    </form>

    <?php elseif ($section === 'view_area' && $view_area): ?>
    <h3>Detail Area</h3>
    <div class="card mb-4">
      <div class="card-body">
        <?php
        $image_path = !empty($view_area['gambar_area']) && $view_area['gambar_area'] !== '0' ? '../' . $view_area['gambar_area'] : '';
        if (!empty($image_path) && file_exists($image_path)): ?>
        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($view_area['nama_area']); ?>" class="mb-3" style="max-width: 300px; border-radius: 5px;">
        <?php else: ?>
        <div class="bg-light text-center mb-3" style="width: 300px; height: 200px; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
          <span class="text-muted"><?php echo !empty($view_area['gambar_area']) ? 'Gambar tidak ditemukan: ' . htmlspecialchars($view_area['gambar_area']) : 'Tidak ada gambar'; ?></span>
        </div>
        <?php endif; ?>
        <h5 class="card-title"><?php echo htmlspecialchars($view_area['nama_area']); ?></h5>
        <p class="card-text"><strong>Nomor Meja:</strong> <?php echo htmlspecialchars($view_area['nomor_meja']); ?></p>
        <p class="card-text"><strong>Deskripsi:</strong> <?php echo htmlspecialchars($view_area['deskripsi'] ?: 'Tidak ada deskripsi'); ?></p>
        <p class="card-text"><strong>Kapasitas:</strong> <?php echo htmlspecialchars($view_area['kapasitas']); ?> orang</p>
        <p class="card-text"><strong>Status:</strong> <span class="badge <?php echo $view_area['tersedia'] ? 'bg-success' : 'bg-danger'; ?>">
          <?php echo $view_area['tersedia'] ? 'Tersedia' : 'Penuh'; ?></span></p>
        <a href="?section=edit_area&id_area=<?php echo $view_area['id_area']; ?>" class="btn btn-primary">Edit</a>
        <a href="?section=daftar_area" class="btn btn-secondary">Kembali</a>
      </div>
    </div>

    <?php elseif ($section === 'daftar_area'): ?>
    <h3>Daftar Area</h3>
    <?php if (empty($areas)): ?>
    <div class="alert alert-info">Belum ada area yang ditambahkan.</div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-3 g-4">
      <?php foreach ($areas as $area): ?>
      <div class="col">
        <div class="card h-100 area-card">
          <div class="card-body">
            <?php
            $image_path = !empty($area['gambar_area']) && $area['gambar_area'] !== '0' ? '../' . $area['gambar_area'] : '';
            if (!empty($image_path) && file_exists($image_path)): ?>
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($area['nama_area']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
            <?php else: ?>
            <div class="bg-light text-center" style="height: 150px; display: flex; align-items: center; justify-content: center;">
              <span class="text-muted"><?php echo !empty($area['gambar_area']) ? 'Gambar tidak ditemukan: ' . htmlspecialchars($area['gambar_area']) : 'Tidak ada gambar'; ?></span>
            </div>
            <?php endif; ?>
            <h5 class="card-title mt-2"><?php echo htmlspecialchars($area['nama_area']); ?></h5>
            <p class="card-text"><strong>Nomor Meja:</strong> <?php echo htmlspecialchars($area['nomor_meja']); ?></p>
            <p class="card-text"><strong>Kapasitas:</strong> <?php echo htmlspecialchars($area['kapasitas']); ?> orang</p>
            <p class="card-text"><strong>Status:</strong> <span class="badge <?php echo $area['tersedia'] ? 'bg-success' : 'bg-danger'; ?>">
              <?php echo $area['tersedia'] ? 'Tersedia' : 'Penuh'; ?></span></p>
            <div class="d-flex gap-2">
              <a href="?section=view_area&id_area=<?php echo $area['id_area']; ?>" class="btn btn-info btn-sm">Lihat</a>
              <a href="?section=edit_area&id_area=<?php echo $area['id_area']; ?>" class="btn btn-primary btn-sm">Edit</a>
              <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus area ini?');">
                <input type="hidden" name="action" value="delete_area">
                <input type="hidden" name="id_area" value="<?php echo $area['id_area']; ?>">
                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php elseif ($section === 'kelola_akun'): ?>
    <h3>Data Pengguna</h3>
    <div class="user-table mb-4">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID User</th>
            <th>Nama</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Aktif</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr><td colspan="6" class="text-center">Belum ada pengguna.</td></tr>
          <?php else: ?>
          <?php foreach ($users as $user): ?>
          <tr>
            <td><?php echo htmlspecialchars($user['id_user']); ?></td>
            <td><?php echo htmlspecialchars($user['nama'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars($user['role']); ?></td>
            <td><?php echo $user['aktif'] ? 'Ya' : 'Tidak'; ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h3>Data Pelanggan</h3>
    <div class="customer-table">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID Customer</th>
            <th>Nama Lengkap</th>
            <th>Email</th>
            <th>No HP</th>
            <th>Tanggal Daftar</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
          <tr><td colspan="5" class="text-center">Belum ada pelanggan.</td></tr>
          <?php else: ?>
          <?php foreach ($customers as $customer): ?>
          <tr>
            <td><?php echo htmlspecialchars($customer['id_customer']); ?></td>
            <td><?php echo htmlspecialchars($customer['nama_lengkap']); ?></td>
            <td><?php echo htmlspecialchars($customer['email']); ?></td>
            <td><?php echo htmlspecialchars($customer['no_hp']); ?></td>
            <td><?php echo htmlspecialchars($customer['tanggal_daftar']); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var toasts = document.querySelectorAll('.toast');
      toasts.forEach(function (toast) {
        new bootstrap.Toast(toast).show();
      });
    });

    function selectTable(element, isUsed, isCurrent = false) {
      if (isUsed && !isCurrent) {
        return;
      }
      document.querySelectorAll('.table-cell').forEach(cell => cell.classList.remove('selected'));
      element.classList.add('selected');
      document.getElementById('nomor_meja').value = element.getAttribute('data-value');
    }
  </script>
</body>
</html>