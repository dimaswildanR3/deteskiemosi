<?php
// ============================================================
// FILE: index.php (REST API STYLE)
// ============================================================

$API_KEY = "susksesTA";

$DB_HOST = "localhost";
$DB_NAME = "deteksie_db";
$DB_USER = "deteksie_user";
$DB_PASS = "password_db";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ambil JSON
$body = json_decode(file_get_contents("php://input"), true);

if (!$body) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "JSON kosong"]);
    exit;
}

// API KEY
if (!isset($body["api_key"]) || $body["api_key"] !== $API_KEY) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "API key salah"]);
    exit;
}

// DB CONNECT
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}

// ambil path endpoint
$uri = $_SERVER['REQUEST_URI'];

// ============================================================
// ROUTE: START SESSION
// POST /api/session/start
// ============================================================
if (strpos($uri, "session/start") !== false) {

    $st = $pdo->prepare(
        "INSERT INTO sessions (nama_kelas, dosen, waktu_mulai)
         VALUES (?, ?, NOW())"
    );

    $st->execute([
        $body["nama_kelas"],
        $body["dosen"]
    ]);

    echo json_encode([
        "status" => "ok",
        "session_id" => $pdo->lastInsertId()
    ]);
    exit;
}

// ============================================================
// ROUTE: STORE DETECTION
// POST /api/store
// ============================================================
if (strpos($uri, "store") !== false) {

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

    update_summary($pdo, $body["session_id"]);

    echo json_encode(["status" => "ok"]);
    exit;
}

// ============================================================
// ROUTE: SNAPSHOT (OPTIONAL)
/// bisa dipakai kalau kamu kirim snapshot
// ============================================================
if (strpos($uri, "snapshot") !== false) {

    $upload_dir = __DIR__ . "/../uploads/snapshots/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $fname = "snap_" . time() . "_mhs" . intval($body["nomor_mahasiswa"]) . ".jpg";

    $full_path = $upload_dir . $fname;
    $rel_path = "uploads/snapshots/" . $fname;

    file_put_contents(
        $full_path,
        base64_decode($body["gambar_base64"])
    );

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

    echo json_encode([
        "status" => "ok",
        "file" => $rel_path
    ]);
    exit;
}

// ============================================================
// ROUTE: STOP SESSION
// ============================================================
if (strpos($uri, "session/stop") !== false) {

    echo json_encode([
        "status" => "ok",
        "message" => "session stopped (no DB action yet)"
    ]);
    exit;
}

// ============================================================
// NOT FOUND
// ============================================================
http_response_code(404);
echo json_encode([
    "status" => "error",
    "message" => "endpoint tidak ditemukan"
]);

// ============================================================
// SUMMARY FUNCTION
// ============================================================
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

    $total = max(1, intval($row["total"]));
    $positif = intval($row["total_positif"]);
    $negatif = intval($row["total_negatif"]);

    $p_pos = round(($positif / $total) * 100, 2);
    $p_neg = round(($negatif / $total) * 100, 2);

    $st = $pdo->prepare(
        "INSERT INTO summary
            (session_id, total_positif, total_negatif, persen_positif, persen_negatif, diperbarui_pada)
         VALUES (?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            total_positif = VALUES(total_positif),
            total_negatif = VALUES(total_negatif),
            persen_positif = VALUES(persen_positif),
            persen_negatif = VALUES(persen_negatif),
            diperbarui_pada = NOW()"
    );

    $st->execute([
        $session_id,
        $positif,
        $negatif,
        $p_pos,
        $p_neg
    ]);
}
?>