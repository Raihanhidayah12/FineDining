<?php
session_start();
include '../includes/config.php';

// Memeriksa apakah pengguna sudah login dan memiliki peran admin
// Memastikan hanya admin yang dapat mengakses halaman ini
if (!isset($_SESSION['id_user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Memeriksa apakah username tersedia, memberikan fallback jika tidak
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';

// Mendapatkan ID admin dari sesi
$id_admin = $_SESSION['id_user'];

// Inisialisasi variabel pesan untuk memberikan umpan balik kepada pengguna
$message = '';

// Mengambil semua nomor meja yang sudah digunakan dan statusnya
// Digunakan untuk mengelola ketersediaan meja di bagian manajemen area
$used_tables = [];
$result = $conn->query("SELECT nomor_meja, tersedia FROM area");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $used_tables[$row['nomor_meja']] = $row['tersedia'];
    }
    $result->free();
}

// Menangani pengiriman formulir untuk menambahkan menu baru
// CRUD: Operasi Create untuk menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nama_menu = filter_input(INPUT_POST, 'nama_menu', FILTER_SANITIZE_STRING);
    $harga = filter_input(INPUT_POST, 'harga', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $deskripsi = filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_STRING);
    $kategori = filter_input(INPUT_POST, 'kategori', FILTER_SANITIZE_STRING);
    $stok = filter_input(INPUT_POST, 'stok', FILTER_SANITIZE_NUMBER_INT);
    $tersedia = filter_input(INPUT_POST, 'tersedia', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT) ? 1 : 0;

    // Memvalidasi kolom yang diperlukan
    if (empty($nama_menu) || empty($harga) || empty($kategori) || $stok === null || $stok < 0) {
        $message = '<div class="alert alert-danger">Nama menu, harga, kategori, dan stok harus diisi dengan benar!</div>';
    } else {
        $gambar_menu = null;
        // Menangani unggahan file untuk gambar menu
        if (isset($_FILES['gambar_menu']) && $_FILES['gambar_menu']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $upload_dir = '../img/';
            $max_file_size = 50 * 1024 * 1024; // 50MB dalam byte

            error_log("Upload attempt: " . print_r($_FILES['gambar_menu'], true));

            if ($_FILES['gambar_menu']['error'] !== UPLOAD_ERR_OK) {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server (50MB).',
                    UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas formulir.',
                    UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
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

        // Jika tidak ada pesan kesalahan, masukkan data ke database
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
                $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
            }
        }
    }
}

// Menangani pengiriman formulir untuk mengedit menu
// CRUD: Operasi Update untuk menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id_menu = filter_input(INPUT_POST, 'id_menu', FILTER_SANITIZE_NUMBER_INT);
    $nama_menu = filter_input(INPUT_POST, 'nama_menu', FILTER_SANITIZE_STRING);
    $harga = filter_input(INPUT_POST, 'harga', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $deskripsi = filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_STRING);
    $kategori = filter_input(INPUT_POST, 'kategori', FILTER_SANITIZE_STRING);
    $stok = filter_input(INPUT_POST, 'stok', FILTER_SANITIZE_NUMBER_INT);
    $tersedia = filter_input(INPUT_POST, 'tersedia', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT) ? 1 : 0;

    // Memvalidasi kolom yang diperlukan
    if (empty($nama_menu) || empty($harga) || empty($kategori) || $stok === null || $stok < 0) {
        $message = '<div class="alert alert-danger">Nama menu, harga, kategori, dan stok harus diisi dengan benar!</div>';
    } else {
        $gambar_menu = null;
        // Menangani unggahan file untuk gambar menu baru
        if (isset($_FILES['gambar_menu']) && $_FILES['gambar_menu']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $upload_dir = '../img/';
            $max_file_size = 50 * 1024 * 1024;

            error_log("Edit upload attempt: " . print_r($_FILES['gambar_menu'], true));

            if ($_FILES['gambar_menu']['error'] !== UPLOAD_ERR_OK) {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server (50MB).',
                    UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas formulir.',
                    UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
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

        // Jika tidak ada pesan kesalahan, perbarui data di database
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
                        // Jika stok nol, otomatis set tersedia ke 0
                        $stmt_tersedia = $conn->prepare("UPDATE menu SET tersedia = 0 WHERE id_menu = ?");
                        if ($stmt_tersedia) {
                            $stmt_tersedia->bind_param("i", $id_menu);
                            $stmt_tersedia->execute();
                            $stmt_tersedia->close();
                        } else {
                            $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
                        }
                    }
                    $message = '<div class="alert alert-success">Menu berhasil diperbarui!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Gagal memperbarui menu: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
            }
        }
    }
}

// Menangani pengiriman formulir untuk restock menu
// CRUD: Operasi Update untuk stok menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restock') {
    $id_menu = filter_input(INPUT_POST, 'id_menu', FILTER_SANITIZE_NUMBER_INT);
    $restock_amount = filter_input(INPUT_POST, 'restock_amount', FILTER_SANITIZE_NUMBER_INT);

    // Memvalidasi jumlah restock
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
            $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
        }
    }
}

// Menangani penghapusan menu
// CRUD: Operasi Delete untuk menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id_menu = filter_input(INPUT_POST, 'id_menu', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT gambar_menu FROM menu WHERE id_menu = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_menu);
        $stmt->execute();
        $result = $stmt->get_result();
        $menu = $result->fetch_assoc();
        $stmt->close();

        // Menghapus gambar terkait jika ada
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
            $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
        }
    }
}

// Menangani pengiriman formulir untuk menambahkan area baru
// CRUD: Operasi Create untuk area
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_area') {
    $nama_area = filter_input(INPUT_POST, 'nama_area', FILTER_SANITIZE_STRING);
    $nomor_meja = filter_input(INPUT_POST, 'nomor_meja', FILTER_SANITIZE_NUMBER_INT);
    $deskripsi = filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_STRING);
    $kapasitas = filter_input(INPUT_POST, 'kapasitas', FILTER_SANITIZE_NUMBER_INT);
    $tersedia = filter_input(INPUT_POST, 'tersedia', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT) ? 1 : 0;

    // Memvalidasi kolom yang diperlukan dan memastikan nomor meja valid
    if (empty($nama_area) || empty($nomor_meja) || $kapasitas === null || $kapasitas <= 0 || !in_array($nomor_meja, range(1, 36))) {
        $message = '<div class="alert alert-danger">Nama area, nomor meja, dan kapasitas harus diisi dengan benar! Nomor meja harus antara 1-36.</div>';
    } else {
        // Memeriksa apakah nomor meja sudah digunakan
        $check_stmt = $conn->prepare("SELECT id_area FROM area WHERE nomor_meja = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("i", $nomor_meja);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            if ($result->num_rows > 0) {
                $message = '<div class="alert alert-danger">Nomor meja sudah digunakan!</div>';
            } else {
                $gambar_area = null;
                // Menangani unggahan file untuk gambar area
                if (isset($_FILES['gambar_area']) && $_FILES['gambar_area']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $upload_dir = '../img/';
                    $max_file_size = 50 * 1024 * 1024;

                    error_log("Area upload attempt: " . print_r($_FILES['gambar_area'], true));

                    if ($_FILES['gambar_area']['error'] !== UPLOAD_ERR_OK) {
                        $upload_errors = [
                            UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server (50MB).',
                            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas formulir.',
                            UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
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

                // Jika tidak ada pesan kesalahan, masukkan data area ke database
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
                        $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
                    }
                }
            }
            $check_stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
        }
    }
}

// Menangani pengiriman formulir untuk mengedit area
// CRUD: Operasi Update untuk area
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_area') {
    $id_area = filter_input(INPUT_POST, 'id_area', FILTER_SANITIZE_NUMBER_INT);
    $nama_area = filter_input(INPUT_POST, 'nama_area', FILTER_SANITIZE_STRING);
    $nomor_meja = filter_input(INPUT_POST, 'nomor_meja', FILTER_SANITIZE_NUMBER_INT);
    $deskripsi = filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_STRING);
    $kapasitas = filter_input(INPUT_POST, 'kapasitas', FILTER_SANITIZE_NUMBER_INT);
    $tersedia = filter_input(INPUT_POST, 'tersedia', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT) ? 1 : 0;

    // Memvalidasi kolom yang diperlukan dan memastikan nomor meja valid
    if (empty($nama_area) || empty($nomor_meja) || $kapasitas === null || $kapasitas <= 0 || !in_array($nomor_meja, range(1, 36))) {
        $message = '<div class="alert alert-danger">Nama area, nomor meja, dan kapasitas harus diisi dengan benar! Nomor meja harus antara 1-36.</div>';
    } else {
        // Memeriksa apakah nomor meja sudah digunakan oleh area lain
        $stmt = $conn->prepare("SELECT id_area FROM area WHERE nomor_meja = ? AND id_area != ?");
        if ($stmt) {
            $stmt->bind_param("ii", $nomor_meja, $id_area);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $message = '<div class="alert alert-danger">Nomor meja sudah digunakan oleh area lain!</div>';
            } else {
                $gambar_area = null;
                // Menangani unggahan file untuk gambar area baru
                if (isset($_FILES['gambar_area']) && $_FILES['gambar_area']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $upload_dir = '../img/';
                    $max_file_size = 50 * 1024 * 1024;

                    error_log("Edit area upload attempt: " . print_r($_FILES['gambar_area'], true));

                    if ($_FILES['gambar_area']['error'] !== UPLOAD_ERR_OK) {
                        $upload_errors = [
                            UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server (50MB).',
                            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas formulir.',
                            UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
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

                // Jika tidak ada pesan kesalahan, perbarui data area di database
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
                        $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
                    }
                }
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
        }
    }
}

// Menangani penghapusan area
// CRUD: Operasi Delete untuk area
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_area') {
    $id_area = filter_input(INPUT_POST, 'id_area', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT gambar_area FROM area WHERE id_area = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_area);
        $stmt->execute();
        $result = $stmt->get_result();
        $area = $result->fetch_assoc();
        $stmt->close();

        // Menghapus gambar terkait jika ada
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
            $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
        }
    }
}

// Menangani perubahan peran pengguna
// CRUD: Operasi Update untuk peran pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $id_user = filter_input(INPUT_POST, 'id_user', FILTER_SANITIZE_NUMBER_INT);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    // Memastikan peran yang dipilih valid
    if (!in_array($role, ['admin', 'chef', 'kasir'])) {
        $message = '<div class="alert alert-danger">Peran tidak valid!</div>';
    } else {
        $stmt = $conn->prepare("UPDATE user SET role = ? WHERE id_user = ?");
        if ($stmt) {
            $stmt->bind_param("si", $role, $id_user);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Peran pengguna berhasil diperbarui!</div>';
            } else {
                $message = '<div class="alert alert-danger">Gagal memperbarui peran: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Kesalahan database: ' . $conn->error . '</div>';
        }
    }
}

// Mengambil data statistik
$total_pendapatan = 0;
$total_pesanan = 0;
$pelanggan_baru = 0;
$pesanan_dibatalkan = 0;

// Total pesanan (hanya yang selesai)
$result = $conn->query("SELECT COUNT(*) as total FROM pesanan WHERE status_pesanan = 'selesai'");
if ($result) {
    $row = $result->fetch_assoc();
    $total_pesanan = $row['total'];
    $result->free();
}

// Total pendapatan (dari pesanan yang bukan cancelled)
$result = $conn->query("SELECT SUM(p.jumlah * m.harga) as total FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.status_pesanan != 'Cancelled'");
if ($result) {
    $row = $result->fetch_assoc();
    $total_pendapatan = $row['total'] ?? 0;
    $result->free();
}

// Pelanggan baru (tidak berubah)
$bulan_ini = date('Y-m');
$result = $conn->query("SELECT COUNT(*) as total FROM customer WHERE DATE_FORMAT(tanggal_daftar, '%Y-%m') = '$bulan_ini'");
if ($result) {
    $row = $result->fetch_assoc();
    $pelanggan_baru = $row['total'];
    $result->free();
}

// Pesanan dibatalkan
$result = $conn->query("SELECT COUNT(*) as total FROM pesanan WHERE status_pesanan = 'Cancelled'");
if ($result) {
    $row = $result->fetch_assoc();
    $pesanan_dibatalkan = $row['total'];
    $result->free();
}

// Mengambil pesanan terbaru (hanya yang masih dipesan/pending)
$recent_orders = [];
$status_column_exists = false;
$id_customer_column_exists = false;

$column_check = $conn->query("SHOW COLUMNS FROM pesanan LIKE 'status_pesanan'");
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
    $query .= ", p.status_pesanan";
} else {
    $query .= ", CASE WHEN p.tanggal_selesai IS NULL THEN 'Pending' ELSE 'selesai' END as status_pesanan";
}
$query .= " FROM pesanan p";
if ($id_customer_column_exists) {
    $query .= " JOIN customer c ON p.id_customer = c.id_customer";
}
$query .= " JOIN menu m ON p.id_menu = m.id_menu";
$query .= " LEFT JOIN area a ON p.id_area = a.id_area";
if ($status_column_exists) {
    $query .= " WHERE p.status_pesanan = 'dipesan'";
} else {
    $query .= " WHERE p.tanggal_selesai IS NULL";
}
$query .= " ORDER BY p.tanggal_pesanan ";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
    $result->free();
}

// Mengambil pesanan yang dibatalkan
$cancelled_orders = [];
if ($status_column_exists) {
    $query = "SELECT p.id_pesanan, ";
    if ($id_customer_column_exists) {
        $query .= "c.nama_lengkap, ";
    } else {
        $query .= "'Unknown Customer' as nama_lengkap, ";
    }
    $query .= "m.nama_menu as menu, (p.jumlah * m.harga) as total_harga, a.nama_area, a.nomor_meja, p.status_pesanan 
              FROM pesanan p";
    if ($id_customer_column_exists) {
        $query .= " JOIN customer c ON p.id_customer = c.id_customer";
    }
    $query .= " JOIN menu m ON p.id_menu = m.id_menu 
                LEFT JOIN area a ON p.id_area = a.id_area 
                WHERE p.status_pesanan = 'Cancelled' 
                ORDER BY p.tanggal_pesanan DESC LIMIT 5";

    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cancelled_orders[] = $row;
        }
        $result->free();
    }
}

// Mengambil pengguna dan pelanggan untuk manajemen akun
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

// Mengambil area untuk daftar area
$areas = [];
$result = $conn->query("SELECT * FROM area ORDER BY nama_area");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $areas[] = $row;
    }
    $result->free();
}

// Menentukan bagian aktif
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Mengambil item menu untuk pengeditan atau tampilan
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

// Mengambil area untuk pengeditan atau tampilan
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

// Memperbarui status tersedia untuk item dengan stok nol
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
 /* Mengatur gaya umum dengan tema gelap */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', sans-serif;
}

body {
  background: #1a1a1a;
  color: #ffffff; /* Mengatur semua teks menjadi putih */
  min-height: 100vh;
  display: flex;
}

.sidebar {
  width: 250px;
  background: #2c2c2c;
  box-shadow: 2px 0 5px rgba(0, 0, 0, 0.5);
  padding: 20px;
  height: 100vh;
  position: fixed;
  transition: width 0.3s;
}

.sidebar h2 {
  color: #ffffff; /* Teks putih */
  font-size: 20px;
  margin-bottom: 30px;
  text-align: center;
  padding: 5px 0; /* Menambahkan padding untuk memastikan teks terlihat */
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
  color: #ffffff; /* Teks putih */
  text-decoration: none;
  border-radius: 5px;
  transition: background 0.3s;
  min-height: 40px; /* Memastikan tinggi minimum untuk teks */
}

.sidebar ul li a:hover,
.sidebar ul li a.active {
  background: #f1c40f;
  color: #1a1a1a; /* Teks hitam saat hover/active */
}

.sidebar ul li a i {
  margin-right: 10px;
  color: #ffffff; /* Ikon putih */
}

.main-content {
  margin-left: 250px;
  padding: 20px;
  width: calc(100% - 250px);
  transition: margin-left 0.3s, width 0.3s;
  color: #ffffff; /* Teks putih */
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #2c2c2c;
  padding: 15px 30px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
  margin-bottom: 20px;
}

.header h1 {
  font-size: 24px;
  color: #ffffff; /* Teks putih */
  padding: 5px 0; /* Menambahkan padding untuk memastikan teks terlihat */
}

.header .user {
  display: flex;
  align-items: center;
}

.header .user i {
  margin-left: 10px;
  color: #ffffff; /* Ikon putih */
}

.menu-card,
.area-card {
  position: relative;
  background: #333;
  transition: transform 0.2s;
  color: #ffffff; /* Teks putih */
}

.menu-card:hover,
.area-card:hover {
  transform: translateY(-5px);
}

.restock-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 10;
}

/* Menyesuaikan tampilan toast */
.toast {
  background-color: white;
  color: black;
  border: 1px solid #dee2e6;
  border-radius: 5px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.toast-header {
  background-color: #fff3cd; /* Warna kuning terang */
  color: black;
  border-bottom: 1px solid #dee2e6;
}

.toast-body {
  background-color: white;
  color: black;
  padding: 15px;
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
  border: 2px solid #555;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  background-color: #444;
  color: #ffffff; /* Teks putih */
  transition: background-color 0.3s;
}

.table-cell:hover:not(.selected-red):not(.selected-green) {
  background-color: #555;
}

.table-cell.selected {
  background-color: #f1c40f;
  border-color: #f1c40f;
  color: #1a1a1a; /* Teks hitam saat dipilih */
}

.table-cell.selected-red {
  background-color: #e74c3c;
  border-color: #e74c3c;
  color: #ffffff; /* Teks putih */
  cursor: not-allowed;
}

.table-cell.selected-green {
  background-color: #2ecc71;
  border-color: #2ecc71;
  color: #ffffff; /* Teks putih */
  cursor: not-allowed;
}

/* Membuat desain responsif */
@media (max-width: 768px) {
  .sidebar {
    width: 80px;
  }

  .sidebar h2 {
    font-size: 16px;
  }

  .sidebar ul li a {
    justify-content: center;
  }

  .sidebar ul li a span {
    display: none;
  }

  .main-content {
    margin-left: 80px;
    width: calc(100% - 80px);
  }

  .table-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 576px) {
  .table-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .card-img-top {
    height: 100px !important;
  }
}

.table-dark {
  color: #ffffff; /* Teks putih */
}

.table-dark th,
.table-dark td {
  color: #ffffff; /* Pastikan header dan isi tabel putih */
}

/* Pastikan semua elemen form memiliki teks putih */
.form-control,
.form-select {
  background-color: #2c2c2c;
  color: #ffffff;
}

/* Pastikan placeholder teks putih */
.form-control::placeholder {
  color: #ffffff;
  opacity: 0.7;
}

/* Pastikan teks di dalam card putih */
.card-text,
.card-title {
  color: #ffffff;
}

/* Mengubah warna teks <small> menjadi putih */
small {
  color: #ffffff !important; /* Memaksa warna putih untuk mengatasi konflik Bootstrap */
}

/* Menambahkan properti tambahan untuk small jika diperlukan */
small.text-muted {
  color: #ffffff !important; /* Mengganti warna muted menjadi putih */
}
  </style>
</head>
<body>
  <!-- Menampilkan notifikasi toast untuk stok habis -->
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
      <li><a href="?section=dashboard" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i><span>Statistik</span></a></li>
      <li><a href="?section=kelola_menu" class="<?php echo $section === 'kelola_menu' ? 'active' : ''; ?>"><i class="fas fa-utensils"></i><span>Kelola Menu</span></a></li>
      <li><a href="?section=menu" class="<?php echo $section === 'menu' ? 'active' : ''; ?>"><i class="fas fa-book"></i><span>Daftar Menu</span></a></li>
      <li><a href="?section=kelola_area" class="<?php echo $section === 'kelola_area' ? 'active' : ''; ?>"><i class="fas fa-map"></i><span>Kelola Area</span></a></li>
      <li><a href="?section=daftar_area" class="<?php echo $section === 'daftar_area' ? 'active' : ''; ?>"><i class="fas fa-list"></i><span>Daftar Area</span></a></li>
      <li><a href="?section=kelola_akun" class="<?php echo $section === 'kelola_akun' ? 'active' : ''; ?>"><i class="fas fa-users"></i><span>Kelola Akun</span></a></li>
      <li><a href="?section=cancelled_orders" class="<?php echo $section === 'cancelled_orders' ? 'active' : ''; ?>"><i class="fas fa-times-circle"></i><span>Pesanan Dibatalkan</span></a></li>
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
        elseif ($section === 'cancelled_orders') echo 'Pesanan Dibatalkan';
        ?>
      </h1>
      <div class="user">
        Admin
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </div>

    <?php echo $message; ?>

<?php if ($section === 'dashboard'): ?>
    <div class="stats d-flex gap-3 mb-4 flex-wrap">
        <div class="card flex-fill text-center bg-dark text-light">
            <div class="card-body">
                <h3 class="card-title">Total Pendapatan</h3>
                <p class="card-text fs-3 fw-bold">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></p>
                <small class="text-muted">+<?php echo round(($total_pendapatan / 1000000) * 100, 0); ?>% dari bulan lalu</small>
            </div>
        </div>
        <div class="card flex-fill text-center bg-dark text-light">
            <div class="card-body">
                <h3 class="card-title">Total Pesanan</h3>
                <p class="card-text fs-3 fw-bold"><?php echo $total_pesanan; ?></p>
                <small class="text-muted">+<?php echo round(($total_pesanan / 100) * 100, 0); ?>% dari bulan lalu</small>
            </div>
        </div>
        <div class="card flex-fill text-center bg-dark text-light">
            <div class="card-body">
                <h3 class="card-title">Pelanggan Baru</h3>
                <p class="card-text fs-3 fw-bold"><?php echo $pelanggan_baru; ?></p>
                <small class="text-muted">+<?php echo $pelanggan_baru > 0 ? $pelanggan_baru * 10 : 0; ?>% dari bulan lalu</small>
            </div>
        </div>
        <div class="card flex-fill text-center bg-dark text-light">
            <div class="card-body">
                <h3 class="card-title">Pesanan Dibatalkan</h3>
                <p class="card-text fs-3 fw-bold"><?php echo $pesanan_dibatalkan; ?></p>
                <small class="text-muted">+<?php echo $pesanan_dibatalkan > 0 ? $pesanan_dibatalkan * 5 : 0; ?>% dari bulan lalu</small>
            </div>
        </div>
    </div>

    <div class="recent-orders">
        <h3>Pesanan Terbaru</h3>
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Menu</th>
                    <th>Area</th>
                    <th>Nomor Meja</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Mengambil 5 pesanan terbaru dengan urutan teratas sebagai yang paling baru
                $recent_orders = [];
                $query = "SELECT p.id_pesanan, m.nama_menu as menu, a.nama_area, a.nomor_meja, (p.jumlah * m.harga) as total_harga, p.status_pesanan
                          FROM pesanan p
                          JOIN menu m ON p.id_menu = m.id_menu
                          LEFT JOIN area a ON p.id_area = a.id_area
                          WHERE p.status_pesanan = 'dipesan'
                          ORDER BY p.tanggal_pesanan DESC LIMIT 5";

                $result = $conn->query($query);
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $recent_orders[] = $row;
                    }
                    $result->free();
                }

                if (empty($recent_orders)): ?>
                    <tr><td colspan="6" class="text-center">Belum ada pesanan.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['id_pesanan']); ?></td>
                        <td><?php echo htmlspecialchars($order['menu']); ?></td>
                        <td><?php echo htmlspecialchars($order['nama_area'] ?? 'Tidak ada area'); ?></td>
                        <td><?php echo htmlspecialchars($order['nomor_meja'] ?? 'Tidak ada nomor'); ?></td>
                        <td>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($order['status_pesanan']); ?></td>
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
        <input type="text" class="form-control bg-dark text-light" id="nama_menu" name="nama_menu" required>
      </div>
      <div class="mb-3">
        <label for="harga" class="form-label">Harga (Rp)</label>
        <input type="number" class="form-control bg-dark text-light" id="harga" name="harga" step="0.01" required>
      </div>
      <div class="mb-3">
        <label for="kategori" class="form-label">Kategori</label>
        <select class="form-select bg-dark text-light" id="kategori" name="kategori" required>
          <option value="">Pilih Kategori</option>
          <option value="Makanan">Makanan</option>
          <option value="Minuman">Minuman</option>
          <option value="Dessert">Dessert</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="deskripsi" class="form-label">Deskripsi</label>
        <textarea class="form-control bg-dark text-light" id="deskripsi" name="deskripsi"></textarea>
      </div>
      <div class="mb-3">
        <label for="stok" class="form-label">Stok Awal</label>
        <input type="number" class="form-control bg-dark text-light" id="stok" name="stok" required min="0">
      </div>
      <div class="mb-3">
        <label for="tersedia" class="form-label">Status Ketersediaan</label>
        <select class="form-select bg-dark text-light" id="tersedia" name="tersedia" required>
          <option value="1">Tersedia</option>
          <option value="0">Tidak Tersedia</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="gambar_menu" class="form-label">Gambar Menu (Max 50MB, JPEG/PNG/GIF)</label>
        <input type="file" class="form-control bg-dark text-light" id="gambar_menu" name="gambar_menu" accept="image/jpeg,image/png,image/gif">
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
        <input type="text" class="form-control bg-dark text-light" id="nama_menu" name="nama_menu" value="<?php echo htmlspecialchars($edit_menu['nama_menu']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="harga" class="form-label">Harga (Rp)</label>
        <input type="number" class="form-control bg-dark text-light" id="harga" name="harga" step="0.01" value="<?php echo htmlspecialchars($edit_menu['harga']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="kategori" class="form-label">Kategori</label>
        <select class="form-select bg-dark text-light" id="kategori" name="kategori" required>
          <option value="Makanan" <?php echo $edit_menu['kategori'] === 'Makanan' ? 'selected' : ''; ?>>Makanan</option>
          <option value="Minuman" <?php echo $edit_menu['kategori'] === 'Minuman' ? 'selected' : ''; ?>>Minuman</option>
          <option value="Dessert" <?php echo $edit_menu['kategori'] === 'Dessert' ? 'selected' : ''; ?>>Dessert</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="deskripsi" class="form-label">Deskripsi</label>
        <textarea class="form-control bg-dark text-light" id="deskripsi" name="deskripsi"><?php echo htmlspecialchars($edit_menu['deskripsi']); ?></textarea>
      </div>
      <div class="mb-3">
        <label for="stok" class="form-label">Stok</label>
        <input type="number" class="form-control bg-dark text-light" id="stok" name="stok" value="<?php echo htmlspecialchars($edit_menu['stok']); ?>" required min="0">
      </div>
      <div class="mb-3">
        <label for="tersedia" class="form-label">Status Ketersediaan</label>
        <select class="form-select bg-dark text-light" id="tersedia" name="tersedia" required>
          <option value="1" <?php echo $edit_menu['tersedia'] ? 'selected' : ''; ?>>Tersedia</option>
          <option value="0" <?php echo !$edit_menu['tersedia'] ? 'selected' : ''; ?>>Tidak Tersedia</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="gambar_menu" class="form-label">Gambar Menu (Max 50MB, Biarkan kosong jika tidak ingin mengganti)</label>
        <input type="file" class="form-control bg-dark text-light" id="gambar_menu" name="gambar_menu" accept="image/jpeg,image/png,image/gif">
        <?php
        $image_path = !empty($edit_menu['gambar_menu']) && $edit_menu['gambar_menu'] !== '0' ? '../' . $edit_menu['gambar_menu'] : '';
        if (!empty($image_path) && file_exists($image_path)): ?>
        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($edit_menu['nama_menu']); ?>" class="mt-2" style="max-width: 200px;">
        <?php else: ?>
        <div class="bg-dark text-center mt-2 border" style="max-width: 200px; height: 100px; display: flex; align-items: center; justify-content: center;">
          <span class="text-muted"><?php echo !empty($edit_menu['gambar_menu']) ? 'Gambar tidak ditemukan: ' . htmlspecialchars($edit_menu['gambar_menu']) : 'Tidak ada gambar'; ?></span>
        </div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="?section=menu" class="btn btn-secondary">Kembali</a>
    </form>

    <?php elseif ($section === 'view_menu' && $view_menu): ?>
    <h3>Detail Menu</h3>
    <div class="card mb-4 bg-dark text-light">
      <div class="card-body">
        <?php
        $image_path = !empty($view_menu['gambar_menu']) && $view_menu['gambar_menu'] !== '0' ? '../' . $view_menu['gambar_menu'] : '';
        if (!empty($image_path) && file_exists($image_path)): ?>
        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($view_menu['nama_menu']); ?>" class="mb-3" style="max-width: 300px; border-radius: 5px;">
        <?php else: ?>
        <div class="bg-dark text-center mb-3 border" style="width: 300px; height: 200px; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
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
    // Mengambil semua item menu dan mengelompokkannya berdasarkan kategori
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
        <div class="card h-100 menu-card position-relative bg-dark text-light">
          <?php if ($menu['stok'] == 0): ?>
          <form method="POST" action="" class="restock-btn">
            <input type="hidden" name="action" value="restock">
            <input type="hidden" name="id_menu" value="<?php echo $menu['id_menu']; ?>">
            <div class="input-group input-group-sm">
              <input type="number" name="restock_amount" class="form-control bg-dark text-light" placeholder="Jumlah" min="1" required>
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
            <div class="bg-dark text-center border" style="height: 150px; display: flex; align-items: center; justify-content: center;">
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
        <input type="text" class="form-control bg-dark text-light" id="nama_area" name="nama_area" required>
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
        <textarea class="form-control bg-dark text-light" id="deskripsi" name="deskripsi"></textarea>
      </div>
      <div class="mb-3">
        <label for="kapasitas" class="form-label">Kapasitas (Jumlah Orang)</label>
        <input type="number" class="form-control bg-dark text-light" id="kapasitas" name="kapasitas" required min="1">
      </div>
      <div class="mb-3">
        <label for="tersedia" class="form-label">Status Ketersediaan</label>
        <select class="form-select bg-dark text-light" id="tersedia" name="tersedia" required>
          <option value="1">Tersedia</option>
          <option value="0">Penuh</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="gambar_area" class="form-label">Gambar Area (Max 50MB, JPEG/PNG/GIF)</label>
        <input type="file" class="form-control bg-dark text-light" id="gambar_area" name="gambar_area" accept="image/jpeg,image/png,image/gif">
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
        <input type="text" class="form-control bg-dark text-light" id="nama_area" name="nama_area" value="<?php echo htmlspecialchars($edit_area['nama_area']); ?>" required>
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
        <textarea class="form-control bg-dark text-light" id="deskripsi" name="deskripsi"><?php echo htmlspecialchars($edit_area['deskripsi']); ?></textarea>
      </div>
      <div class="mb-3">
        <label for="kapasitas" class="form-label">Kapasitas (Jumlah Orang)</label>
        <input type="number" class="form-control bg-dark text-light" id="kapasitas" name="kapasitas" value="<?php echo htmlspecialchars($edit_area['kapasitas']); ?>" required min="1">
      </div>
      <div class="mb-3">
        <label for="tersedia" class="form-label">Status Ketersediaan</label>
        <select class="form-select bg-dark text-light" id="tersedia" name="tersedia" required>
          <option value="1" <?php echo $edit_area['tersedia'] ? 'selected' : ''; ?>>Tersedia</option>
          <option value="0" <?php echo !$edit_area['tersedia'] ? 'selected' : ''; ?>>Penuh</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="gambar_area" class="form-label">Gambar Area (Max 50MB, Biarkan kosong jika tidak ingin mengganti)</label>
        <input type="file" class="form-control bg-dark text-light" id="gambar_area" name="gambar_area" accept="image/jpeg,image/png,image/gif">
        <?php
        $image_path = !empty($edit_area['gambar_area']) && $edit_area['gambar_area'] !== '0' ? '../' . $edit_area['gambar_area'] : '';
        if (!empty($image_path) && file_exists($image_path)): ?>
        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($edit_area['nama_area']); ?>" class="mt-2" style="max-width: 200px;">
        <?php else: ?>
        <div class="bg-dark text-center mt-2 border" style="max-width: 200px; height: 100px; display: flex; align-items: center; justify-content: center;">
          <span class="text-muted"><?php echo !empty($edit_area['gambar_area']) ? 'Gambar tidak ditemukan: ' . htmlspecialchars($edit_area['gambar_area']) : 'Tidak ada gambar'; ?></span>
        </div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="?section=daftar_area" class="btn btn-secondary">Kembali</a>
    </form>

<?php elseif ($section === 'view_area' && $view_area): ?>
    <h3>Detail Area</h3>
    <div class="card mb-4 bg-dark text-light">
      <div class="card-body">
        <?php
        // Memeriksa apakah gambar area tersedia dan valid
        $image_path = !empty($view_area['gambar_area']) && $view_area['gambar_area'] !== '0' ? '../' . $view_area['gambar_area'] : '';
        if (!empty($image_path) && file_exists($image_path)): ?>
        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($view_area['nama_area']); ?>" class="mb-3" style="max-width: 300px; border-radius: 5px;">
        <?php else: ?>
        <div class="bg-dark text-center mb-3 border" style="width: 300px; height: 200px; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
          <span class="text-muted"><?php echo !empty($view_area['gambar_area']) ? 'Gambar tidak ditemukan: ' . htmlspecialchars($view_area['gambar_area']) : 'Tidak ada gambar'; ?></span>
        </div>
        <?php endif; ?>
        <h5 class="card-title"><?php echo htmlspecialchars($view_area['nama_area']); ?></h5>
        <p class="card-text"><strong>Nomor Meja:</strong> <?php echo htmlspecialchars($view_area['nomor_meja']); ?></p>
        <p class="card-text"><strong>Kapasitas:</strong> <?php echo htmlspecialchars($view_area['kapasitas']); ?> orang</p>
        <p class="card-text"><strong>Deskripsi:</strong> <?php echo htmlspecialchars($view_area['deskripsi'] ?: 'Tidak ada deskripsi'); ?></p>
        <p class="card-text"><strong>Status:</strong> <span class="badge <?php echo $view_area['tersedia'] ? 'bg-success' : 'bg-danger'; ?>">
          <?php echo $view_area['tersedia'] ? 'Tersedia' : 'Penuh'; ?></span></p>
        <a href="?section=edit_area&id_area=<?php echo $view_area['id_area']; ?>" class="btn btn-primary">Edit</a>
        <a href="?section=daftar_area" class="btn btn-secondary">Kembali</a>
      </div>
    </div>

<?php elseif ($section === 'daftar_area'): ?>
    <h3>Daftar Area</h3>
    <?php
    // Mengambil semua area untuk ditampilkan
    if (empty($areas)): ?>
    <div class="alert alert-info">Belum ada area yang ditambahkan.</div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-3 g-4">
      <?php foreach ($areas as $area): ?>
      <div class="col">
        <div class="card h-100 area-card bg-dark text-light">
          <div class="card-body">
            <?php
            // Memeriksa apakah gambar area tersedia
            $image_path = !empty($area['gambar_area']) && $area['gambar_area'] !== '0' ? '../' . $area['gambar_area'] : '';
            if (!empty($image_path) && file_exists($image_path)): ?>
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($area['nama_area']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
            <?php else: ?>
            <div class="bg-dark text-center border" style="height: 150px; display: flex; align-items: center; justify-content: center;">
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

<?php elseif ($section === 'cancelled_orders'): ?>
    <h3>Pesanan Dibatalkan</h3>
    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Menu</th>
                <th>Area</th>
                <th>Nomor Meja</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $cancelled_orders = [];
            $query = "SELECT p.id_pesanan,m.nama_menu as menu, a.nama_area, a.nomor_meja, (p.jumlah * m.harga) as total_harga, p.status_pesanan
                      FROM pesanan p
                      JOIN menu m ON p.id_menu = m.id_menu
                      LEFT JOIN area a ON p.id_area = a.id_area
                      WHERE p.status_pesanan = 'cancelled'
                      ORDER BY p.tanggal_pesanan DESC";

            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $cancelled_orders[] = $row;
                }
                $result->free();
            }

            if (empty($cancelled_orders)): ?>
                <tr><td colspan="7" class="text-center">Belum ada pesanan yang dibatalkan.</td></tr>
            <?php else: ?>
                <?php foreach ($cancelled_orders as $order): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($order['id_pesanan']); ?></td>
                    <td><?php echo htmlspecialchars($order['menu']); ?></td>
                    <td><?php echo htmlspecialchars($order['nama_area'] ?? 'Tidak ada area'); ?></td>
                    <td><?php echo htmlspecialchars($order['nomor_meja'] ?? 'Tidak ada nomor'); ?></td>
                    <td>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></td>
                    <td><?php echo htmlspecialchars($order['status_pesanan']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>


<?php elseif ($section === 'kelola_akun'): ?>
    <h3>Kelola Akun</h3>
    <h4>Pengguna</h4>
    <?php
    // Menampilkan daftar pengguna dengan opsi untuk mengubah peran
    if (empty($users)): ?>
    <div class="alert alert-info">Belum ada pengguna yang terdaftar.</div>
    <?php else: ?>
    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>ID Pengguna</th>
                <th>Username</th>
                <th>Email</th>
                <th>Peran</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['id_user']); ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['role'] ?: 'customer'); ?></td>
                <td>
                    <form method="POST" action="" class="d-inline">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="id_user" value="<?php echo $user['id_user']; ?>">
                        <select name="role" class="form-select form-select-sm bg-dark text-light d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="admin" <?php echo ($user['role'] === 'admin' || (empty($user['role']) && false)) ? 'selected' : ''; ?>>Admin</option>
                            <option value="chef" <?php echo $user['role'] === 'chef' ? 'selected' : ''; ?>>Chef</option>
                            <option value="kasir" <?php echo $user['role'] === 'kasir' ? 'selected' : ''; ?>>Kasir</option>
                            <option value="customer" <?php echo empty($user['role']) || $user['role'] === 'Tidak ada peran' ? 'selected' : ''; ?>>Customer</option>
                        </select>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h4 class="mt-5">Pelanggan</h4>
    <?php
    // Menampilkan daftar pelanggan (tanpa peran)
    if (empty($customers)): ?>
    <div class="alert alert-info">Belum ada pelanggan yang terdaftar.</div>
    <?php else: ?>
    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>ID Pelanggan</th>
                <th>Nama Lengkap</th>
                <th>Email</th>
                <th>Nomor Telepon</th>
                <th>Tanggal Daftar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
            <tr>
                <td><?php echo htmlspecialchars($customer['id_customer']); ?></td>
                <td><?php echo htmlspecialchars($customer['nama_lengkap']); ?></td>
                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                <td><?php echo htmlspecialchars($customer['no_hp'] ?: 'Tidak ada'); ?></td>
                <td><?php echo htmlspecialchars($customer['tanggal_daftar']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
<?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Menangani pemilihan nomor meja
    function selectTable(element, isUsed, isCurrent = false) {
      if (isUsed && !isCurrent) {
        return; // Tidak bisa memilih meja yang sudah digunakan
      }
      const cells = document.querySelectorAll('.table-cell');
      cells.forEach(cell => {
        if (cell !== element && !cell.classList.contains('selected-red') && !cell.classList.contains('selected-green')) {
          cell.classList.remove('selected');
        }
      });
      if (!element.classList.contains('selected-red') && !element.classList.contains('selected-green')) {
        element.classList.toggle('selected');
        const nomorMejaInput = document.getElementById('nomor_meja');
        nomorMejaInput.value = element.classList.contains('selected') ? element.getAttribute('data-value') : '';
      }
    }

    // Menginisialisasi nomor meja yang sudah dipilih saat edit
    document.addEventListener('DOMContentLoaded', () => {
      const selectedCell = document.querySelector('.table-cell.selected');
      if (selectedCell) {
        const nomorMejaInput = document.getElementById('nomor_meja');
        nomorMejaInput.value = selectedCell.getAttribute('data-value');
      }

      // Menginisialisasi toast
      const toasts = document.querySelectorAll('.toast');
      toasts.forEach(toast => {
        new bootstrap.Toast(toast).show();
      });
    });

    setInterval(() => {
    location.reload(); // Merefresh halaman setiap 30 detik (sesuaikan waktunya)
}, 30000); // 30000 milidetik = 30 detik
  </script>
</body>
</html>