@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Login') }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="form-group row">
                                <label for="email"
                                    class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>

                                <div class="col-md-6">
                                    <input id="email" type="email"
                                        class="form-control @error('email') is-invalid @enderror" name="email"
                                        value="{{ old('email') }}" required autocomplete="email" autofocus>

                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row mb-1">
                                <label for="password"
                                    class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>

                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input onkeypress="capLock(event)" id="password" type="password"
                                            class="form-control @error('password') is-invalid @enderror" name="password"
                                            required autocomplete="current-password" value="">

                                        <div class="input-group-append eye-area" style="cursor: pointer">
                                            <span class="input-group-text eye-opened">👁️</span>
                                            <span class="input-group-text eye-closed hidden">🙈</span>
                                        </div>
                                    </div>


                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="text-center mb-3" id="divMayus" style="color:red; font-size:10px;"></div>

                            <div class="form-group row">
                                <div class="col-md-6 offset-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                            {{ old('remember') ? 'checked' : '' }}>

                                        <label class="form-check-label" for="remember">
                                            {{ __('Remember Me') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Login') }}
                                    </button>

                                    @if (Route::has('password.request'))
                                        <a class="btn btn-link" href="{{ route('password.request') }}">
                                            {{ __('Forgot Your Password?') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .hidden {
            display: none;
        }
    </style>
@endsection

@section('js')
    <script language="Javascript">
        document.addEventListener('keydown', function(event) {
            let flag = event.getModifierState && event.getModifierState('CapsLock');
            let estate = flag ? "Caps Lock LIGADO" : "Caps Lock DESLIGADO"
            $('#divMayus').html(estate);
        });
        document.addEventListener('DOMContentLoaded', function() {
            const eyeArea = document.querySelector('.eye-area');
            const input = document.querySelector('input[name=password]');

            eyeArea.addEventListener('click', function() {
                document.querySelector('.eye-opened').classList.toggle('hidden');
                document.querySelector('.eye-closed').classList.toggle('hidden');
                input.setAttribute('type', input.getAttribute('type') === 'password' ? 'text' : 'password');
            });
        });
    </script>
@endsection
