@extends('layouts.admin')

@section('main-content')

<h1 class="h3 mb-4 text-gray-800">
    {{ __('Profile') }}
</h1>

@if ($errors->any())
    <div class="alert alert-danger border-left-danger">
        <ul class="pl-4 my-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST"
      action="{{ route('profile.update') }}"
      enctype="multipart/form-data">

    @csrf
    @method('PUT')

    <div class="row">

        <!-- LEFT -->
        <div class="col-lg-4 order-lg-2">

            <div class="card shadow mb-4">

                <div class="card-profile-image mt-4 text-center">

                    <label for="profile_photo"
                           style="cursor:pointer; position:relative; display:inline-block;">

                           @if(!empty(Auth::user()->profile_photo))
    <img id="preview-image"
         src="{{ asset(Auth::user()->profile_photo) }}"
         class="rounded-circle shadow"
         width="180"
         height="180"
         style="object-fit:cover; border:4px solid white;">
@else
<img id="preview-image"
                                 src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}"
                                 class="rounded-circle shadow"
                                 width="180"
                                 height="180"
                                 style="object-fit:cover; border:4px solid white;">
@endif

                        <!-- ICON CAMERA -->
                        <div style="
                            position:absolute;
                            bottom:10px;
                            right:10px;
                            background:#4e73df;
                            width:40px;
                            height:40px;
                            border-radius:50%;
                            color:white;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            border:2px solid white;
                        ">
                            <i class="fas fa-camera"></i>
                        </div>

                    </label>

                    <!-- INPUT FILE -->
                    <input type="file"
                           id="profile_photo"
                           name="profile_photo"
                           class="d-none"
                           accept="image/*">

                </div>

                <div class="card-body text-center">

                    <h5 class="font-weight-bold">
                        {{ Auth::user()->fullName }}
                    </h5>

                    <p>{{ Auth::user()->role }}</p>

                </div>

            </div>

        </div>

        <!-- RIGHT -->
        <div class="col-lg-8 order-lg-1">

            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        My Account
                    </h6>
                </div>

                <div class="card-body">

                    <div class="form-group">
                        <label>Name</label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name', Auth::user()->name) }}">
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>

                        <input type="text"
                               name="last_name"
                               class="form-control"
                               value="{{ old('last_name', Auth::user()->last_name) }}">
                    </div>

                    <div class="form-group">
                        <label>Email</label>

                        <input type="email"
                               name="email"
                               class="form-control"
                               value="{{ old('email', Auth::user()->email) }}">
                    </div>

                    <hr>

                    <div class="row">

                        <div class="col-lg-4">
                            <div class="form-group">

                                <label>Current Password</label>

                                <input type="password"
                                       name="current_password"
                                       class="form-control">

                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">

                                <label>New Password</label>

                                <input type="password"
                                       name="new_password"
                                       class="form-control">

                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">

                                <label>Confirm Password</label>

                                <input type="password"
                                       name="password_confirmation"
                                       class="form-control">

                            </div>
                        </div>

                    </div>

                    <div class="text-center mt-4">

                        <button type="submit"
                                class="btn btn-primary">
                            Save Changes
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</form>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const input = document.getElementById('profile_photo');

    input.addEventListener('change', function(e) {

        const file = e.target.files[0];

        if(file){

            const reader = new FileReader();

            reader.onload = function(event) {

                document.getElementById('preview-image')
                    .src = event.target.result;
            }

            reader.readAsDataURL(file);
        }

    });

});

</script>

@endsection