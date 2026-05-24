<?php
// ============================================================
// FILE: terima_data.php
// Upload ke cPanel: public_html/deteksie/api/terima_data.php
// ============================================================

// --- CONFIG DATABASE (sesuaikan dengan cPanel kamu) ---
$API_KEY = "susksesTA";   // samakan dengan di Python
$DB_HOST = "localhost";
$DB_NAME = "deteksie_db";          // nama database di cPanel
$DB_USER = "deteksie_user";        // username MySQL di cPanel
$DB_PASS = "password_db";          // password MySQL di cPanel

// --- Izinkan request dari mana saja (CORS) ---
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// --- Baca body JSON dari Python ---
$body = json_decode(file_get_contents("php://input"), true);

// --- Validasi API Key ---
if (!$body || $body["api_key"] !== $API_KEY) {
    http_response_code(403);
    echo json_encode(["status" => "error", "pesan" => "API key tidak valid"]);
    exit;
}

// --- Koneksi ke MySQL ---
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "pesan" => "Gagal konek DB: " . $e->getMessage()]);
    exit;
}

// --- Router berdasarkan nilai 'aksi' ---
switch ($body["aksi"]) {

    // ── Buat sesi baru ──────────────────────────────────────
    case "mulai_sesi":
        $st = $pdo->prepare(
            "INSERT INTO sessions (nama_kelas, dosen, waktu_mulai)
             VALUES (?, ?, NOW())"
        );
        $st->execute([$body["nama_kelas"], $body["dosen"]]);
        echo json_encode([
            "status"     => "ok",
            "session_id" => $pdo->lastInsertId()
        ]);
        break;

    // ── Simpan hasil deteksi emosi ──────────────────────────
    case "kirim_deteksi":
        $st = $pdo->prepare(
            "INSERT INTO detections (session_id, nomor_mahasiswa, label, confidence, timestamp)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $st->execute([
            $body["session_id"],
            $body["nomor_mahasiswa"],
            $body["label"],
            $body["confidence"]
        ]);

        // Update tabel summary secara otomatis
        update_summary($pdo, $body["session_id"]);

        echo json_encode(["status" => "ok"]);
        break;

    // ── Simpan snapshot gambar wajah ────────────────────────
    case "kirim_snapshot":
        $upload_dir = dirname(__DIR__) . "/uploads/snapshots/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $fname     = "snap_" . time() . "_mhs" . intval($body["nomor_mahasiswa"]) . ".jpg";
        $full_path = $upload_dir . $fname;
        $rel_path  = "uploads/snapshots/" . $fname;

        file_put_contents($full_path, base64_decode($body["gambar_base64"]));

        $st = $pdo->prepare(
            "INSERT INTO snapshots (session_id, nomor_mahasiswa, label, file_path, timestamp)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $st->execute([
            $body["session_id"],
            $body["nomor_mahasiswa"],
            $body["label"],
            $rel_path
        ]);

        echo json_encode(["status" => "ok", "file" => $rel_path]);
        break;

    default:
        http_response_code(400);
        echo json_encode(["status" => "error", "pesan" => "Aksi tidak dikenal"]);
        break;
}

// ── Helper: Update tabel summary ───────────────────────────
function update_summary($pdo, $session_id) {
    $st = $pdo->prepare(
        "SELECT
            SUM(label = 'POSITIF') AS total_positif,
            SUM(label = 'NEGATIF') AS total_negatif,
            COUNT(*) AS total
         FROM detections
         WHERE session_id = ?"
    );
    $st->execute([$session_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $total          = max(1, intval($row["total"]));
    $total_positif  = intval($row["total_positif"]);
    $total_negatif  = intval($row["total_negatif"]);
    $persen_positif = round(($total_positif / $total) * 100, 2);
    $persen_negatif = round(($total_negatif / $total) * 100, 2);

    // Upsert: insert jika belum ada, update jika sudah ada
    $st = $pdo->prepare(
        "INSERT INTO summary
            (session_id, total_positif, total_negatif, persen_positif, persen_negatif, diperbarui_pada)
         VALUES (?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            total_positif   = VALUES(total_positif),
            total_negatif   = VALUES(total_negatif),
            persen_positif  = VALUES(persen_positif),
            persen_negatif  = VALUES(persen_negatif),
            diperbarui_pada = NOW()"
    );
    $st->execute([$session_id, $total_positif, $total_negatif, $persen_positif, $persen_negatif]);
}
?>
