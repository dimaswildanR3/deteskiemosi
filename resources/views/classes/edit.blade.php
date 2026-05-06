@extends('layouts.admin')

@section('main-content')
<h1 class="h3 mb-4 text-gray-800">Edit Kelas</h1>

<div class="card">
<div class="card-body">

<form action="{{ route('classes.update',$class->id) }}" method="POST">
@csrf

<div class="form-group">
<label>Kode Kelas</label>
<input type="text" name="kode_kelas" class="form-control"
value="{{ $class->kode_kelas }}" required>
</div>

<div class="form-group">
<label>Nama Kelas</label>
<input type="text" name="nama_kelas" class="form-control"
value="{{ $class->nama_kelas }}" required>
</div>

<div class="form-group">
<label>Mata Kuliah</label>
<input type="text" name="mata_kuliah" class="form-control"
value="{{ $class->mata_kuliah }}" required>
</div>

<div class="form-group">
<label>Dosen</label>
<select name="dosen_id" class="form-control" required>

<option value="">-- Pilih Dosen --</option>

@foreach ($dosens as $dosen)
<option value="{{ $dosen->id }}"
{{ $class->dosen_id == $dosen->id ? 'selected' : '' }}>
{{ $dosen->full_name }}
</option>
@endforeach

</select>
</div>

<div class="form-group">
<label>Semester</label>
<input type="text" name="semester" class="form-control"
value="{{ $class->semester }}">
</div>

<div class="form-group">
<label>Tahun Ajaran</label>
<input type="text" name="tahun_ajaran" class="form-control"
value="{{ $class->tahun_ajaran }}">
</div>

<div class="form-group">
<label>Ruang</label>
<input type="text" name="ruang" class="form-control"
value="{{ $class->ruang }}">
</div>

<button class="btn btn-primary">Update</button>
<a href="{{ route('classes.index') }}" class="btn btn-secondary">Kembali</a>

</form>

</div>
</div>

@endsection