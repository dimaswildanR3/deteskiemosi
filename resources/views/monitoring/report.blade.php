@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">
        Laporan Sistem Monitoring Emosi
    </h1>

    {{-- SESSION --}}
    <div class="card shadow mb-4">

        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">
                Data Session
            </h6>
        </div>

        <div class="card-body table-responsive">

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kelas</th>
                        <th>Dosen</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Total Mahasiswa</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($sessions as $s)

                    <tr>
                        <td>{{ $s->id }}</td>
                        <td>{{ $s->nama_kelas }}</td>
                        <td>{{ $s->dosen }}</td>
                        <td>{{ $s->waktu_mulai }}</td>
                        <td>{{ $s->waktu_selesai }}</td>
                        <td>{{ $s->total_mahasiswa }}</td>
                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    {{-- SUMMARY --}}
    <div class="card shadow mb-4">

        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-success">
                Data Summary
            </h6>
        </div>

        <div class="card-body table-responsive">

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>Session</th>
                        <th>Positif</th>
                        <th>Negatif</th>
                        <th>% Positif</th>
                        <th>% Negatif</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($summaries as $s)

                    <tr>

                        <td>{{ $s->session_id }}</td>

                        <td>{{ $s->total_positif }}</td>

                        <td>{{ $s->total_negatif }}</td>

                        <td>{{ number_format($s->persen_positif, 2) }}%</td>

                        <td>{{ number_format($s->persen_negatif, 2) }}%</td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    {{-- DETECTIONS --}}
    <div class="card shadow mb-4">

        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-danger">
                Data Detection
            </h6>
        </div>

        <div class="card-body table-responsive">

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Session</th>
                        <th>Mahasiswa</th>
                        <th>Label</th>
                        <th>Confidence</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($detections as $d)

                    <tr>

                        <td>{{ $d->id }}</td>

                        <td>{{ $d->session_id }}</td>

                        <td>{{ $d->nomor_mahasiswa }}</td>

                        <td>{{ $d->label }}</td>

                        <td>{{ $d->confidence }}</td>

                        <td>{{ $d->timestamp }}</td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    {{-- FACE IMAGE --}}
    <div class="card shadow mb-4">

        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-info">
                Data Face Images
            </h6>
        </div>

        <div class="card-body table-responsive">

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Session</th>
                        <th>Mahasiswa</th>
                        <th>Label</th>
                        <th>Image</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($faceImages as $f)

                    <tr>

                        <td>{{ $f->id }}</td>

                        <td>{{ $f->session_id }}</td>

                        <td>{{ $f->nomor_mahasiswa }}</td>

                        <td>{{ $f->label }}</td>

                        <td>

                            <img
                                src="{{ asset($f->file_path) }}"
                                width="100"
                            >

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
<script>
    window.onload = function () {
        window.print();
    }
</script>