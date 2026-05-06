@extends('layouts.admin')

@section('main-content')
<h1 class="h3 mb-4 text-gray-800">Tambah Kelas</h1>

<div class="card">
    <div class="card-body">

        <form action="{{ route('classes.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label>Kode Kelas</label>
                <input type="text" name="kode_kelas" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Nama Kelas</label>
                <input type="text" name="nama_kelas" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Mata Kuliah</label>
                <input type="text" name="mata_kuliah" class="form-control" required>
            </div>

            <div class="form-group">
    <label>Dosen</label>
    <select name="dosen_id" class="form-control" required>
        <option value="">-- Pilih Dosen --</option>
        @foreach ($dosens as $dosen)
            <option value="{{ $dosen->id }}">
                {{ $dosen->full_name }}
            </option>
        @endforeach
    </select>
</div>

            <div class="form-group">
                <label>Semester</label>
                <input type="text" name="semester" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" class="form-control" placeholder="2025/2026" required>
            </div>

            <div class="form-group">
                <label>Ruang</label>
                <input type="text" name="ruang" class="form-control">
            </div>

            <button class="btn btn-primary">Simpan</button>
            <a href="{{ route('classes.index') }}" class="btn btn-secondary">Kembali</a>

        </form>

    </div>
</div>
@endsection
