@php
    $locationValue = old('google_location', $customer->google_location ?? '');
@endphp

<div class="card border mb-3 bg-light">
    <div class="card-body py-3">
        <label for="google_location" class="form-label fw-semibold mb-1">
            <i class="fas fa-map-marker-alt text-danger me-1"></i>
            Delivery Location (Google Maps)
            <span class="text-muted small fw-normal">(recommended for drivers)</span>
        </label>
        <div class="input-group mb-2">
            <input type="text" class="form-control @error('google_location') is-invalid @enderror"
                id="google_location" name="google_location" value="{{ $locationValue }}"
                placeholder="https://maps.app.goo.gl/... or https://www.google.com/maps/...">
            <button type="button" class="btn btn-outline-primary" id="testLocationBtn" title="Open in Google Maps">
                <i class="fas fa-external-link-alt"></i>
            </button>
        </div>
        @error('google_location')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

        <div class="d-flex flex-wrap gap-2 mb-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="generateFromAddressBtn">
                <i class="fas fa-magic me-1"></i> Generate from Address
            </button>
            <a href="https://www.google.com/maps" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">
                <i class="fab fa-google me-1"></i> Open Google Maps
            </a>
        </div>

        <details class="small text-muted">
            <summary class="fw-semibold text-dark" style="cursor:pointer;">How to get the best link</summary>
            <ol class="mb-0 mt-2 ps-3">
                <li>Open <strong>Google Maps</strong> and search the customer factory/shop.</li>
                <li>Tap <strong>Share</strong> → <strong>Copy link</strong>.</li>
                <li>Paste the link here — driver can tap it in WhatsApp and navigate directly.</li>
            </ol>
            <p class="mb-0 mt-2">
                <i class="fas fa-lightbulb text-warning me-1"></i>
                Short links like <code>maps.app.goo.gl</code> work best on mobile.
            </p>
        </details>
    </div>
</div>
