@extends('layouts.auth')

@section('title', 'Sign In — Kemtex Management System')

@section('content')
    <h2>Welcome back</h2>
    <p class="auth-subtitle">Sign in to continue to your account</p>

    @if ($errors->any())
        <div class="alert-auth">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if (session('status'))
        <div class="alert-auth" style="background:#f0fdf4;border-color:#bbf7d0;color:#16a34a;">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ url('/login') }}">
        @csrf

        <div class="field-group">
            <label for="inputEmail">Email address</label>
            <div class="input-wrap @error('email') is-invalid @enderror">
                <i class="fas fa-envelope input-ico"></i>
                <input id="inputEmail" name="email" type="email" placeholder="you@company.com"
                    value="{{ old('email') }}" required autocomplete="email" />
            </div>
            @error('email')
                <div style="color:#e74c3c;font-size:0.8rem;margin-top:4px;"><i
                        class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>

        <div class="field-group">
            <label for="inputPassword">Password</label>
            <div class="input-wrap">
                <i class="fas fa-lock input-ico"></i>
                <input id="inputPassword" name="password" type="password" placeholder="••••••••" required minlength="8"
                    autocomplete="current-password" />
                <span class="toggle-pw" id="togglePw" title="Show/hide password">
                    <i class="fas fa-eye" id="togglePwIcon"></i>
                </span>
            </div>
            @error('password')
                <div style="color:#e74c3c;font-size:0.8rem;margin-top:4px;"><i
                        class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>

        <div class="row-between">
            <label class="remember-label">
                <input type="checkbox" name="remember" value="1" />
                Remember me
            </label>
        </div>

        <button type="submit" class="btn-signin">
            <i class="fas fa-sign-in-alt me-2"></i> Sign In
        </button>
    </form>

    <p class="auth-footer-text">
        Don't have an account? <a href="{{ url('/register') }}">Create one</a>
    </p>
@endsection

@section('scripts')
    <script>
        document.getElementById('togglePw').addEventListener('click', function() {
            const input = document.getElementById('inputPassword');
            const icon = document.getElementById('togglePwIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>
@endsection
