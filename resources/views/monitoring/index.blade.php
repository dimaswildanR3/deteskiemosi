@extends('layouts.admin')

@section('main-content')

<h1 class="h3 mb-4 text-gray-800">
    Monitoring Data Perkelas
</h1>

<div class="card shadow mb-4">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered">

                <thead class="bg-primary text-white">

                    <tr>
                        <th width="50">No</th>
                        <th>Kelas</th>
                        <th>Dosen</th>
                        <th>Total Mahasiswa</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                        <th>Status</th>
                        <th width="100">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($sessions as $item)

                    <tr>

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>
                            <strong>{{ $item->nama_kelas }}</strong>
                        </td>

                        <td>
                            {{ $item->dosen }}
                        </td>

                        <td>
                            {{ $item->total_mahasiswa }}
                        </td>

                        <td>
                            {{ \Carbon\Carbon::parse($item->waktu_mulai)->format('d M Y H:i') }}
                        </td>

                        <td>
                            {{ $item->waktu_selesai
                                ? \Carbon\Carbon::parse($item->waktu_selesai)->format('d M Y H:i')
                                : '-' }}
                        </td>

                        <td>

                            @if($item->waktu_selesai)

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

@endsection