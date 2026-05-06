@extends('layouts.auth')

@section('main-content')
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="col-xl-4 col-lg-5 col-md-7">
        <div class="card o-hidden border-0 shadow-lg">
            <div class="card-body p-5">

                <div class="text-center">
                    <h1 class="h4 text-gray-900 mb-4">{{ __('Login') }}</h1>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger border-left-danger" role="alert">
                        <ul class="pl-4 my-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="user">
                    @csrf

                    <div class="form-group">
                        <input type="email" 
                               class="form-control form-control-user" 
                               name="email" 
                               placeholder="{{ __('E-Mail Address') }}" 
                               value="{{ old('email') }}" 
                               required autofocus>
                    </div>

                    <div class="form-group">
                        <input type="password" 
                               class="form-control form-control-user" 
                               name="password" 
                               placeholder="{{ __('Password') }}" 
                               required>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox small">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   name="remember" 
                                   id="remember" 
                                   {{ old('remember') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="remember">
                                {{ __('Remember Me') }}
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-user btn-block">
                        {{ __('Login') }}
                    </button>
                </form>

                <hr>

                @if (Route::has('password.request'))
                    <div class="text-center">
                        <a class="small" href="{{ route('password.request') }}">
                            {{ __('Forgot Password?') }}
                        </a>
                    </div>
                @endif

                @if (Route::has('register'))
                    <div class="text-center">
                        <a class="small" href="{{ route('register') }}">
                            {{ __('Create an Account!') }}
                        </a>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection