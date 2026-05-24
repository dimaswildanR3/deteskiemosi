@extends('layouts.admin')

@section('main-content')

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        Real-time Emotion Monitoring
    </h1>

    <span class="text-muted">
        <i class="fas fa-clock"></i>
        Session: {{ $session->session_name ?? '-' }}
    </span>
</div>

@if (session('success'))
<div class="alert alert-success border-left-success alert-dismissible fade show">
    {{ session('success') }}
</div>
@endif

{{-- ================= KPI ================= --}}
<div class="row">

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body text-center">
                <div class="h3 font-weight-bold text-gray-800">
                    {{ $widget['monitoring'] ?? 0 }}
                </div>
                <div class="text-xs font-weight-bold text-primary text-uppercase">
                    Total Captures
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body text-center">
                <div class="h3 font-weight-bold text-success">
                    {{ $widget['positive_rate'] ?? 0 }}%
                </div>
                <div class="text-xs font-weight-bold text-success text-uppercase">
                    Positive Rate
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body text-center">
                <div class="h3 font-weight-bold text-info">
                    {{ $widget['avg_sentiment'] ?? 0 }}
                </div>
                <div class="text-xs font-weight-bold text-info text-uppercase">
                    Avg. Sentiment
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ================= CHART ================= --}}
<div class="row">

    {{-- PIE CHART --}}
    <div class="col-lg-5 mb-4">
        <div class="card shadow">
            <div class="card-header">
                Sentiment Distribution
            </div>

            <div class="card-body">
                <div style="height:300px;">
                    <canvas id="pieChart"></canvas>
                </div>

                <div class="text-center mt-3">
                    <span class="text-success">
                        Positive: {{ $widget['positive_rate'] ?? 0 }}%
                    </span>
                    <br>
                    <span class="text-danger">
                        Negative: {{ $widget['negative_rate'] ?? 0 }}%
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- LINE CHART --}}
    <div class="col-lg-7 mb-4">
        <div class="card shadow">
            <div class="card-header">
                Expression Timeline
            </div>

            <div class="card-body">
                <div style="height:300px;">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ================= JS ================= --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // ================= DATA DARI CONTROLLER =================
    const positive = @json($widget['positive_rate'] ?? 0);
    const negative = @json($widget['negative_rate'] ?? 0);

    const labels = @json($widget['timeline_labels'] ?? []);
    const values = @json($widget['timeline_values'] ?? []);

    const safeLabels = labels.length ? labels : ["No Data"];
    const safeValues = values.length ? values : [0];

    // ================= PIE CHART =================
    new Chart(document.getElementById("pieChart"), {
        type: 'doughnut',
        data: {
            labels: ["Positive", "Negative"],
            datasets: [{
                data: [positive, negative],
                backgroundColor: ['#1cc88a', '#e74a3b']
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            maintainAspectRatio: false,
            cutout: '70%'
        }
    });

    // ================= LINE CHART =================
    new Chart(document.getElementById("lineChart"), {
        type: 'line',
        data: {
            labels: safeLabels,
            datasets: [{
                label: "Sentiment",
                data: safeValues,
                borderColor: "#6f42c1",
                backgroundColor: "rgba(111,66,193,0.1)",
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    min: -1,
                    max: 1,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

});
</script>

@endsection