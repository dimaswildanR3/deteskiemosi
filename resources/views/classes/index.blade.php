@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ $title ?? 'Data Kelas' }}</h1>

    <a href="{{ route('classes.create') }}" class="btn btn-primary mb-3">Tambah Kelas</a>

    @if (session('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Kelas</th>
                <th>Nama Kelas</th>
                <th>Mata Kuliah</th>
                <th>Dosen</th> 
                <th>Semester</th>
                <th>Tahun Ajaran</th>
                <th>Ruang</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($classes as $class)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $class->kode_kelas }}</td>
                    <td>{{ $class->nama_kelas }}</td>
                    <td>{{ $class->mata_kuliah }}</td>
                    <td>{{ $class->dosen->full_name ?? '-' }}</td>
                    <td>{{ $class->semester }}</td>
                    <td>{{ $class->tahun_ajaran }}</td>
                    <td>{{ $class->ruang }}</td>
                    <td>
                        <div class="d-flex">
                            <a href="{{ route('classes.edit', $class->id) }}" class="btn btn-sm btn-primary mr-2">Edit</a>

                            <form action="{{ route('classes.destroy', $class->id) }}" method="POST">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Yakin hapus kelas ini?')">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection