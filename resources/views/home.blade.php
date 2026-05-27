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

<a href="{{ route('monitoring.export') }}"
   class="btn btn-success shadow-sm mr-2">

    <i class="fas fa-file-excel"></i>
   Laporan

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
            <div class="h3 mb-0 font-weight-bold text-gray-800">
    {{ $widget['monitoring'] }}
</div>
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
            <div class="h3 mb-0 font-weight-bold text-success">
    {{ $widget['positive_rate'] }}%
</div>
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
    {{ number_format($widget['avg_sentiment'], 2) }}
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
    Positive: {{ $widget['positive_rate'] }}%
</span>

<span class="mr-3">
    <i class="fas fa-circle text-danger"></i>
    Negative: {{ $widget['negative_rate'] }}%
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

                <a href="{{ route('clear.data') }}"
   class="btn btn-sm btn-outline-danger shadow-sm"
   onclick="return confirm('Yakin ingin menghapus data?')">

    <i class="fas fa-trash"></i> Clear
</a>

            </div>

            <div class="card-body">

                <div class="row text-center">

                @foreach ($widget['latest_faces'] as $face)
<div class="col-md-4 mb-3">
<img src="{{ asset($face->file_path) }}"
     class="img-fluid rounded shadow-sm mb-2">

    <p class="mb-1 font-weight-bold">
    {{ \Carbon\Carbon::parse($face->created_at)->format('H:i') }}
    </p>

    <span class="badge {{ $face->label == 'POSITIF' ? 'badge-success' : 'badge-danger' }} px-3 py-2">
        {{ $face->label }}
    </span>
</div>
@endforeach

                </div>

            </div>
        </div>
    </div>

</div>


{{-- MODAL DETEKSI --}}
<div class="modal fade"
     id="modalDeteksi"
     tabindex="-1"
     aria-hidden="true"
     data-backdrop="static"
     data-keyboard="false">

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

                    <video id="previewKamera"
       autoplay
       playsinline
       muted
       style="width: 100%; border-radius: 12px;">
</video>
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
@endsection


{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
{{-- Chart.js --}}
<script>
document.addEventListener("DOMContentLoaded", function () {

    const sentimentData = {
        positive: {{ $widget['positive_rate'] ?? 0 }},
        negative: {{ $widget['negative_rate'] ?? 0 }}
    };

    const timelineLabels = @json($widget['timeline_labels']);
    const timelineValues = @json($widget['timeline_values']);

    // =========================
    // PIE CHART
    // =========================
    const ctxPie = document.getElementById("mySentimentPieChart").getContext('2d');

    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ["Positive", "Negative"],
            datasets: [{
                data: [
                    sentimentData.positive,
                    sentimentData.negative
                ],
                backgroundColor: ['#1cc88a', '#e74a3b'],
                hoverBackgroundColor: ['#17a673', '#be2617']
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            cutout: '70%'
        }
    });

    // =========================
    // LINE CHART (FIX UTAMA)
    // =========================
    const ctxLine = document.getElementById("myExpressionLineChart").getContext('2d');

    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: timelineLabels,
            datasets: [{
                label: "Sentiment Score",
                data: timelineValues,

                borderColor: "#6f42c1",
                backgroundColor: "rgba(111, 66, 193, 0.05)",

                fill: true,
                tension: 0.4, // 🔥 bikin tidak kaku / garis lurus
                spanGaps: true,

                pointRadius: 3,
                pointBackgroundColor: "#6f42c1"
            }]
        },
        options: {
            maintainAspectRatio: false,

            scales: {
                y: {
                    min: -100,
                    max: 100
                }
            },

            plugins: {
                legend: { display: false }
            }
        }
    });

});
</script>

<script>
    
</script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<!-- <script src="https://deteksiemosi.com/js/detection.js"></script> -->
<script src="{{ asset('js/detection.js') }}"></script>
<!-- <script defer src="https://unpkg.com/face-api.js"></script> -->