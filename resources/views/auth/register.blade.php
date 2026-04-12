@extends('layouts.auth')

@section('title', 'Register — Kemtex Management System')

@section('styles')
    <style>
        /* Widen the right panel for the register form */
        .auth-form-panel {
            width: 560px;
        }

        .auth-form-inner {
            max-width: 500px;
        }

        /* Two-column field grid */
        .fields-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 1.1rem;
        }

        /* Select styling to match inputs */
        .input-wrap select {
            width: 100%;
            padding: .78rem 1rem .78rem 2.6rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: .95rem;
            color: #111827;
            background: #f9fafb;
            appearance: none;
            -webkit-appearance: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
            cursor: pointer;
        }

        .input-wrap select:focus {
            border-color: #7b2ff7;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(123, 47, 247, .12);
        }

        /* chevron arrow for select */
        .input-wrap.has-select::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: .75rem;
            pointer-events: none;
        }

        /* Toggle password button */
        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            font-size: .85rem;
            line-height: 1;
        }

        .toggle-pw:hover {
            color: #7b2ff7;
        }

        @media (max-width: 600px) {
            .fields-grid {
                grid-template-columns: 1fr;
            }

            .auth-form-panel {
                width: 100%;
            }
        }
    </style>
@endsection

@section('content')
    <h2>Create Account</h2>
    <p class="auth-subtitle">Set up your Kemtex management system</p>

    @if ($errors->any())
        <div class="alert-auth">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ url('/register') }}">
        @csrf

        {{-- Full Name --}}
        <div class="field-group">
            <label for="inputName">Full Name</label>
            <div class="input-wrap">
                <i class="fas fa-user input-ico"></i>
                <input id="inputName" name="name" type="text" placeholder="John Smith" value="{{ old('name') }}"
                    required maxlength="255" />
            </div>
            @error('name')
                <div style="color:#e74c3c;font-size:.8rem;margin-top:4px;"><i
                        class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>

        {{-- Email + Phone --}}
        <div class="fields-grid">
            <div class="field-group">
                <label for="inputEmail">Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope input-ico"></i>
                    <input id="inputEmail" name="email" type="email" placeholder="you@company.com"
                        value="{{ old('email') }}" required maxlength="255" />
                </div>
                @error('email')
                    <div style="color:#e74c3c;font-size:.8rem;margin-top:4px;"><i
                            class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>
            <div class="field-group">
                <label for="inputPhone">Phone Number</label>
                <div class="input-wrap">
                    <i class="fas fa-phone input-ico"></i>
                    <input id="inputPhone" name="phone_number" type="tel" placeholder="+1 234 567 890"
                        value="{{ old('phone_number') }}" required minlength="7" maxlength="20" />
                </div>
                @error('phone_number')
                    <div style="color:#e74c3c;font-size:.8rem;margin-top:4px;"><i
                            class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Business Type + Company Name --}}
        <div class="fields-grid">
            <div class="field-group">
                <label for="inputBusinessType">Business Type</label>
                <div class="input-wrap has-select">
                    <i class="fas fa-industry input-ico"></i>
                    <select id="inputBusinessType" name="business_type">
                        <option value="" disabled {{ old('business_type') ? '' : 'selected' }}>Select type</option>
                        <option value="textile" {{ old('business_type') == 'textile' ? 'selected' : '' }}>Textile</option>
                        <option value="steel" {{ old('business_type') == 'steel' ? 'selected' : '' }}>Steel</option>
                        <option value="cosmetics" {{ old('business_type') == 'cosmetics' ? 'selected' : '' }}>Cosmetics
                        </option>
                        <option value="soap" {{ old('business_type') == 'soap' ? 'selected' : '' }}>Soap</option>
                        <option value="perfume" {{ old('business_type') == 'perfume' ? 'selected' : '' }}>Perfume
                        </option>
                        <option value="packaging" {{ old('business_type') == 'packaging' ? 'selected' : '' }}>Packaging
                        </option>
                        <option value="chemical" {{ old('business_type') == 'chemical' ? 'selected' : '' }}>Chemical
                        </option>
                        <option value="food" {{ old('business_type') == 'food' ? 'selected' : '' }}>Food Processing
                        </option>
                        <option value="other" {{ old('business_type') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                @error('business_type')
                    <div style="color:#e74c3c;font-size:.8rem;margin-top:4px;"><i
                            class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>
            <div class="field-group">
                <label for="inputCompanyName">Company Name</label>
                <div class="input-wrap">
                    <i class="fas fa-building input-ico"></i>
                    <input id="inputCompanyName" name="company_name" type="text" placeholder="Acme Corp"
                        value="{{ old('company_name') }}" required maxlength="255" />
                </div>
                @error('company_name')
                    <div style="color:#e74c3c;font-size:.8rem;margin-top:4px;"><i
                            class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Password + Confirm --}}
        <div class="fields-grid">
            <div class="field-group">
                <label for="inputPassword">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-ico"></i>
                    <input id="inputPassword" name="password" type="password" placeholder="Min 8 characters" required
                        minlength="8" />
                    <span class="toggle-pw" id="togglePw" title="Show/hide password">
                        <i class="fas fa-eye" id="togglePwIcon"></i>
                    </span>
                </div>
                @error('password')
                    <div style="color:#e74c3c;font-size:.8rem;margin-top:4px;"><i
                            class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>
            <div class="field-group">
                <label for="inputPasswordConfirm">Confirm Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-ico"></i>
                    <input id="inputPasswordConfirm" name="password_confirmation" type="password"
                        placeholder="Repeat password" required minlength="8" />
                    <span class="toggle-pw" id="togglePwConfirm" title="Show/hide password">
                        <i class="fas fa-eye" id="togglePwConfirmIcon"></i>
                    </span>
                </div>
                @error('password_confirmation')
                    <div style="color:#e74c3c;font-size:.8rem;margin-top:4px;"><i
                            class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn-signin" style="margin-top:.5rem;">
            <i class="fas fa-user-plus me-2"></i> Create Account
        </button>
    </form>

    <p class="auth-footer-text">
        Already have an account? <a href="{{ url('/') }}">Sign in</a>
    </p>
@endsection

@section('scripts')
    <script>
        // Toggle password visibility
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
        document.getElementById('togglePwConfirm').addEventListener('click', function() {
            const input = document.getElementById('inputPasswordConfirm');
            const icon = document.getElementById('togglePwConfirmIcon');
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
