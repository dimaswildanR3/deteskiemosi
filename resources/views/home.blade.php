@extends('layouts.admin')

@section('main-content')

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">{{ __('Real-time Emotion Monitoring') }}</h1>
        <span class="text-muted">
            <i class="fas fa-clock"></i> Session: 5 minutes
        </span>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex gap-2">
    <button id="btnDashboardDeteksi"
    class="btn btn-success shadow-sm mr-2"
    data-toggle="modal"
    data-target="#modalDeteksi">

    <i class="fas fa-camera"></i>
    Deteksi Kamera

</button>

        <button class="btn btn-primary shadow-sm mr-2">
            <i class="fas fa-file-alt"></i> Laporan
        </button>

        <a href="{{ route('clear.data') }}"
   class="btn btn-warning shadow-sm"
   onclick="return confirm('Yakin ingin menghapus data?')">

    <i class="fas fa-bolt"></i> Flash
</a>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i>
    {{ session('success') }}

    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

<div class="row">

    {{-- Total Captures --}}
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body text-center">
                <div class="h3 mb-0 font-weight-bold text-gray-800">5</div>
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Total Captures
                </div>
            </div>
        </div>
    </div>

    {{-- Positive Rate --}}
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body text-center">
                <div class="h3 mb-0 font-weight-bold text-success">60%</div>
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                    Positive Rate
                </div>
            </div>
        </div>
    </div>

    {{-- Avg Sentiment --}}
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body text-center">
                <div class="h3 mb-0 font-weight-bold" style="color: #6f42c1;">
                    23
                </div>
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                    Avg. Sentiment
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row">

    {{-- Pie Chart --}}
    <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Sentiment Distribution
                </h6>
            </div>

            <div class="card-body">

                <div class="chart-pie pt-4 pb-2" style="position: relative; height: 300px;">
                    <canvas id="mySentimentPieChart"></canvas>
                </div>

                <div class="mt-4 text-center small">
                    <span class="mr-3">
                        <i class="fas fa-circle text-success"></i>
                        Positive: 60% (3)
                    </span>

                    <span class="mr-3">
                        <i class="fas fa-circle text-danger"></i>
                        Negative: 40% (2)
                    </span>
                </div>

            </div>
        </div>
    </div>

    {{-- Line Chart --}}
    <div class="col-lg-7 mb-4">
        <div class="card shadow mb-4">

            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    Expression Timeline
                </h6>

                <span class="badge badge-info px-3 py-2">
                    Live Monitoring
                </span>
            </div>

            <div class="card-body">
                <div class="chart-area" style="position: relative; height: 300px;">
                    <canvas id="myExpressionLineChart"></canvas>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- Captured Images --}}
<div class="row">

    <div class="col-lg-12 mb-4">

        <div class="card shadow mb-4">

            <div class="card-header py-3 d-flex justify-content-between align-items-center">

                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-camera"></i> Captured Moments
                </h6>

                <button class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash"></i> Clear
                </button>

            </div>

            <div class="card-body">

                <div class="row text-center">

                    <div class="col-md-4 mb-3">
                        <img src="https://via.placeholder.com/300x200"
                            class="img-fluid rounded shadow-sm mb-2"
                            alt="0:00">

                        <p class="mb-1 font-weight-bold">0:00</p>

                        <span class="badge badge-success px-3 py-2">
                            😊 Positive
                        </span>
                    </div>

                    <div class="col-md-4 mb-3">
                        <img src="https://via.placeholder.com/300x200"
                            class="img-fluid rounded shadow-sm mb-2"
                            alt="1:00">

                        <p class="mb-1 font-weight-bold">1:00</p>

                        <span class="badge badge-success px-3 py-2">
                            😊 Positive
                        </span>
                    </div>

                    <div class="col-md-4 mb-3">
                        <img src="https://via.placeholder.com/300x200"
                            class="img-fluid rounded shadow-sm mb-2"
                            alt="2:00">

                        <p class="mb-1 font-weight-bold">2:00</p>

                        <span class="badge badge-danger px-3 py-2">
                            😟 Negative
                        </span>
                    </div>

                </div>

            </div>
        </div>
    </div>

</div>


{{-- MODAL DETEKSI --}}
<div class="modal fade" id="modalDeteksi" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-md">

    <form action="{{ route('start.detection') }}" method="POST">
            @csrf

            <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
    <input type="hidden" name="user_name" value="{{ auth()->user()->name }}">

            <div class="modal-content border-0 shadow-lg rounded-lg overflow-hidden">

                {{-- HEADER --}}
                <div class="modal-header border-0 text-white"
                    style="background: linear-gradient(135deg, #1cc88a, #17a673);">

                    <div>
                        <h4 class="modal-title font-weight-bold mb-1">
                            <i class="fas fa-camera-retro mr-2"></i>
                            Emotion Detection
                        </h4>

                        <small class="opacity-75">
                            Monitoring ekspresi mahasiswa secara realtime
                        </small>
                    </div>

                    <button type="button"
                            class="close text-white"
                            data-dismiss="modal"
                            style="opacity:1;">
                        <span style="font-size: 28px;">&times;</span>
                    </button>
                </div>

                {{-- BODY --}}
                <div class="modal-body p-4">

                    {{-- ICON --}}
                    <div class="text-center mb-4">

                        <div style="
                            width: 90px;
                            height: 90px;
                            margin:auto;
                            border-radius:50%;
                            background: rgba(28,200,138,0.1);
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">

                            <i class="fas fa-video"
                            style="
                                font-size:40px;
                                color:#1cc88a;
                            "></i>

                        </div>

                    </div>

                    {{-- TEXT --}}
                    <div class="text-center mb-4">

                        <h5 class="font-weight-bold text-gray-800">
                            Pilih Kelas Monitoring
                        </h5>

                        <p class="text-muted mb-0">
                            Sistem akan membuka kamera dan memulai
                            analisis ekspresi wajah otomatis.
                        </p>

                    </div>
                    <div class="text-center mb-4">

<!-- <video id="previewKamera"
    autoplay
    playsinline
    muted
    style="
        width:100%;
        max-height:350px;
        border-radius:15px;
        background:#000;
    ">
</video> -->

</div>
                    {{-- SELECT --}}
                    <div class="form-group">

                        <label class="font-weight-bold text-dark mb-2">
                            <i class="fas fa-users mr-1 text-success"></i>
                            Kelas
                        </label>

                        <select name="kelas" class="form-control custom-select" required
                                style="
                                    height:50px;
                                    border-radius:12px;
                                    border:1px solid #dfe6e9;
                                "
                                required>
                                @foreach ($classes as $class)

<option value="{{ $class->id }}">
    {{ $class->nama_kelas }}
</option>

@endforeach

                        </select>

                    </div>

                    {{-- ALERT --}}
                    <div class="alert border-0 mt-4"
                        style="
                            background:#f8f9fc;
                            border-radius:12px;
                        ">

                        <div class="d-flex align-items-start">

                            <i class="fas fa-info-circle text-primary mt-1 mr-2"></i>

                            <small class="text-muted">
                                Pastikan webcam aktif dan pencahayaan cukup
                                agar sistem dapat mendeteksi emosi secara akurat.
                            </small>

                        </div>

                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="modal-footer border-0 px-4 pb-4">

                <button type="button"
        id="btnMulaiDeteksi"
        class="btn text-white px-4 py-2 shadow"
        style="
            background: linear-gradient(135deg, #1cc88a, #17a673);
            border:none;
            border-radius:10px;
            min-width:160px;
            font-weight:600;
        ">

    <i class="fas fa-play-circle mr-2"></i>
    Mulai Deteksi

</button>

                    <!-- <button type="submit"
                            class="btn text-white px-4 py-2 shadow"
                            style="
                                background: linear-gradient(135deg, #1cc88a, #17a673);
                                border:none;
                                border-radius:10px;
                                min-width:160px;
                                font-weight:600;
                            ">

                        <i class="fas fa-play-circle mr-2"></i>
                        Mulai Deteksi

                    </button> -->

                </div>

            </div>

        </form>

    </div>

</div>
@endsection


{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
{{-- Chart.js --}}
<script>
document.addEventListener("DOMContentLoaded", function () {

    const btnDashboard = document.getElementById("btnDashboardDeteksi");
    const btnMulai = document.getElementById("btnMulaiDeteksi");

    let cameraStream = null;
    let isDetecting = false;

    /*
    |--------------------------------------------------------------------------
    | START DETEKSI
    |--------------------------------------------------------------------------
    */

    btnMulai.addEventListener("click", async function () {

        try {

            // buka kamera (TAPI TIDAK DITAMPILKAN)
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });

            isDetecting = true;

            // tutup modal
            $("#modalDeteksi").modal("hide");

            // ubah tombol dashboard jadi STOP
            btnDashboard.innerHTML = `
                <i class="fas fa-stop-circle"></i>
                Stop Deteksi
            `;

            btnDashboard.classList.remove("btn-success");
            btnDashboard.classList.add("btn-danger");

            btnDashboard.removeAttribute("data-toggle");
            btnDashboard.removeAttribute("data-target");

            console.log("Kamera aktif (background)");

            /*
            |--------------------------------------------------------------------------
            | JALANKAN PYTHON (nanti di sini)
            |--------------------------------------------------------------------------
            */

            // fetch("/start-python");

        } catch (err) {

            console.log("CAMERA ERROR:", err);

            alert(err.name + " - " + err.message);
        }

    });

    /*
    |--------------------------------------------------------------------------
    | STOP DETEKSI
    |--------------------------------------------------------------------------
    */

    btnDashboard.addEventListener("click", function (e) {

        if (!isDetecting) return;

        e.preventDefault();

        // stop kamera
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }

        isDetecting = false;

        // balikin tombol
        btnDashboard.innerHTML = `
            <i class="fas fa-camera"></i>
            Deteksi Kamera
        `;

        btnDashboard.classList.remove("btn-danger");
        btnDashboard.classList.add("btn-success");

        btnDashboard.setAttribute("data-toggle", "modal");
        btnDashboard.setAttribute("data-target", "#modalDeteksi");

        console.log("Kamera dimatikan");

        /*
        |--------------------------------------------------------------------------
        | STOP PYTHON (nanti di sini)
        |--------------------------------------------------------------------------
        */

        // fetch("/stop-python");

    });

});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    // 1. Data Dummy
    const sentimentData = {
        positive: 3,
        negative: 2
    };

    const timelineLabels = ["0:00", "1:00", "2:00", "3:00", "4:00", "5:00"];
    const timelineValues = [45, 65, -20, 30, -35, 50];

    // 2. Pie/Doughnut Chart
    const ctxPie = document.getElementById("mySentimentPieChart").getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ["Positive", "Negative"],
            datasets: [{
                data: [sentimentData.positive, sentimentData.negative],
                backgroundColor: ['#1cc88a', '#e74a3b'],
                hoverBackgroundColor: ['#17a673', '#be2617'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '70%' // Membuat efek lubang di tengah
        }
    });

    // 3. Line Chart
    const ctxLine = document.getElementById("myExpressionLineChart").getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: timelineLabels,
            datasets: [{
                label: "Sentiment Score",
                data: timelineValues,
                lineTension: 0.3,
                backgroundColor: "rgba(111, 66, 193, 0.05)",
                borderColor: "#6f42c1",
                pointRadius: 3,
                pointBackgroundColor: "#6f42c1",
                pointBorderColor: "#6f42c1",
                pointHoverRadius: 5,
                pointHoverBackgroundColor: "#5a32a3",
                pointHoverBorderColor: "#5a32a3",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                fill: true,
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                },
                y: {
                    min: -100,
                    max: 100,
                    ticks: {
                        stepSize: 50
                    },
                    grid: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const btnDashboard = document.getElementById("btnDashboardDeteksi");
    const btnMulai = document.getElementById("btnMulaiDeteksi");
    const form = document.querySelector("#modalDeteksi form");

    let cameraStream = null;
    let isDetecting = false;

    // =========================
    // START CAMERA + KIRIM KE LARAVEL
    // =========================
    btnMulai?.addEventListener("click", async function (e) {
        e.preventDefault();

        try {
            // 1. CAMERA
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });

            isDetecting = true;

            console.log("Camera ON");

            // 2. CLOSE MODAL
            $("#modalDeteksi").modal("hide");

            // 3. KIRIM KE CONTROLLER
            const formData = new FormData(form);

            const response = await fetch(form.action, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json"
                },
                body: formData
            });

            // DEBUG RESPONSE
            const text = await response.text();
            console.log("RAW RESPONSE:", text);

            // kalau JSON
            let result;
            try {
                result = JSON.parse(text);
                console.log("SUCCESS:", result);
            } catch (e) {
                console.warn("Bukan JSON dari controller");
            }

            // 4. UPDATE BUTTON
            setRunning();

        } catch (err) {
            console.error("ERROR:", err);
            alert(err.message);
        }
    });

    // =========================
    // STOP DETECTION
    // =========================
    btnDashboard?.addEventListener("click", function (e) {

        if (!isDetecting) return;

        e.preventDefault();

        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }

        isDetecting = false;

        fetch("/stop-detection", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            }
        });

        setIdle();

        console.log("STOP DETECTION");
    });

    // =========================
    // UI STATE
    // =========================
    function setRunning() {
        btnDashboard.innerHTML = `
            <i class="fas fa-stop-circle"></i> Stop Deteksi
        `;
        btnDashboard.classList.remove("btn-success");
        btnDashboard.classList.add("btn-danger");

        btnDashboard.removeAttribute("data-toggle");
        btnDashboard.removeAttribute("data-target");
    }

    function setIdle() {
        btnDashboard.innerHTML = `
            <i class="fas fa-camera"></i> Deteksi Kamera
        `;
        btnDashboard.classList.remove("btn-danger");
        btnDashboard.classList.add("btn-success");

        btnDashboard.setAttribute("data-toggle", "modal");
        btnDashboard.setAttribute("data-target", "#modalDeteksi");
    }

});
</script>