@extends('layouts.admin')

@section('main-content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Real-time Emotion Monitoring') }}</h1>
        <span class="text-muted"><i class="fas fa-clock"></i> Session: 5 minutes</span>
    </div>

    @if (session('success'))
    <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body text-center">
                    <div class="h3 mb-0 font-weight-bold text-gray-800">5</div>
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Captures</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body text-center">
                    <div class="h3 mb-0 font-weight-bold text-success">60%</div>
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Positive Rate</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body text-center">
                    <div class="h3 mb-0 font-weight-bold text-info" style="color: #6f42c1 !important;">23</div>
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg. Sentiment</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sentiment Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="position: relative; height: 300px;">
                        <canvas id="mySentimentPieChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Positive: 60% (3)
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-danger"></i> Negative: 40% (2)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Expression Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="position: relative; height: 300px;">
                        <canvas id="myExpressionLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-camera"></i> Captured Moments</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <img src="https://via.placeholder.com/150" class="img-fluid rounded mb-2" alt="0:00">
                            <p class="mb-1">0:00</p>
                            <span class="badge badge-success px-3">😊 Positive</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <img src="https://via.placeholder.com/150" class="img-fluid rounded mb-2" alt="1:00">
                            <p class="mb-1">1:00</p>
                            <span class="badge badge-success px-3">😊 Positive</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <img src="https://via.placeholder.com/150" class="img-fluid rounded mb-2" alt="2:00">
                            <p class="mb-1">2:00</p>
                            <span class="badge badge-danger px-3">😟 Negative</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

{{-- Pakai @push jika di layout admin.blade.php kamu ada @stack('js') --}}
{{-- Jika tidak ada, tetap gunakan @section('scripts') --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
