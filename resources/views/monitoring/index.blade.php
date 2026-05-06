@extends('layouts.admin')

@section('main-content')

<h1 class="h3 mb-4 text-gray-800">Monitoring Data Perkelas</h1>

<div class="card shadow">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>No</th>
                        <th>Kelas</th>
                        <th>Tanggal</th>
                        <th>Positive</th>
                        <th>Negative</th>
                        <!-- <th>Status</th> -->
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $key => $item)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $item['nama'] }}</td>
                        <td>{{ $item['tanggal'] }}</td>
                        <td>{{ $item['positive'] }}</td>
                        <td>{{ $item['negative'] }}</td>
                        <!-- <td>
                            <span class="badge badge-{{ $item['status'] == 'Selesai' ? 'success' : 'warning' }}">
                                {{ $item['status'] }}
                            </span>
                        </td> -->
                        <td>
                            <a href="{{ route('monitoring.view', $item['id']) }}" class="btn btn-sm btn-info">
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

@endsection