@extends('layouts.admin')

@section('main-content')

<h1 class="h3 mb-4 text-gray-800">
    Monitoring Data Perkelas
</h1>

@foreach($sessions as $tanggal => $items)

<div class="card shadow mb-4">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">

        <h6 class="m-0 font-weight-bold text-primary">

            Tanggal :
            {{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}

        </h6>

        <span class="badge badge-primary">

            {{ count($items) }} Session

        </span>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered">

                <thead class="bg-primary text-white">

                    <tr>
                        <th width="50">No</th>
                        <th>Kelas</th>
                        <th>Session</th>
                        <th>Total Capture</th>
                        <th>Positive</th>
                        <th>Negative</th>
                        <th>Avg Sentiment</th>
                        <th>Status</th>
                        <th width="100">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($items as $item)

                    <tr>

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>

                            <strong>
                                {{ $item->class->nama_kelas ?? '-' }}
                            </strong>

                            <br>

                            <small class="text-muted">

                                {{ $item->class->mata_kuliah ?? '-' }}

                            </small>

                        </td>

                        <td>

                            {{ $item->session_name }}

                        </td>

                        <td>

                            {{ $item->total_captures }}

                        </td>

                        <td>

                            <span class="text-success font-weight-bold">

                                {{ $item->positive_rate }}%

                            </span>

                        </td>

                        <td>

                            <span class="text-danger font-weight-bold">

                                {{ 100 - $item->positive_rate }}%

                            </span>

                        </td>

                        <td>

                            {{ $item->avg_sentiment }}

                        </td>

                        <td>

                            @if($item->ended_at)

                                <span class="badge badge-success">
                                    Selesai
                                </span>

                            @else

                                <span class="badge badge-warning">
                                    Proses
                                </span>

                            @endif

                        </td>

                        <td>

                            <a href="{{ route('monitoring.view', $item->id) }}"
                               class="btn btn-sm btn-info">

                                View

                            </a>

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

@endforeach

@endsection