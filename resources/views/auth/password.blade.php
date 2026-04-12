@extends('layouts.auth')

@section('title', 'Password Recovery - Kemtex ERP')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-header">
                        <h3 class="text-center font-weight-light my-4">Password Recovery</h3>
                    </div>
                    <div class="card-body">
                        <div class="small mb-3 text-muted">Enter your email address and we will send you a link to reset your
                            password.</div>
                        @if (session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif
                        <form method="POST" action="{{ url('/password') }}">
                            @csrf
                            <div class="form-floating mb-3">
                                <input class="form-control" id="inputEmail" name="email" type="email"
                                    placeholder="name@example.com" value="{{ old('email') }}" required />
                                <label for="inputEmail">Email address</label>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                <a class="small" href="{{ url('/') }}">Return to login</a>
                                <button type="submit" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <div class="small"><a href="{{ url('/register') }}">Need an account? Sign up!</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
