@extends('layouts.app')

@section('title', 'Add Category')

@section('content')
    <h1 class="mt-4">Add Category</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/product-categories') }}">Categories</a></li>
        <li class="breadcrumb-item active">Add</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus me-1"></i> New Category</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ url('/product-categories') }}">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                        name="name" value="{{ old('name') }}" required maxlength="255">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Save Category</button>
                <a href="{{ url('/product-categories') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
