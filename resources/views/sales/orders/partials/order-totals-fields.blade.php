{{-- GST, discount, remarks — used on create/edit. Expects optional $order for edit defaults. --}}
@php
    $defaultGst = old('gst_rate', isset($order) ? ($order->gst_rate === null ? 18 : (int) $order->gst_rate) : 18);
    $defaultDiscount = old('discount_amount', isset($order) ? ($order->discount_amount ?? 0) : 0);
    $defaultNotes = old('notes', isset($order) ? ($order->notes ?? '') : '');
@endphp

<div class="row mb-3">
    <div class="col-md-4">
        <label for="gst_rate" class="form-label">GST Rate <span class="text-danger">*</span></label>
        <select class="form-control" id="gst_rate" name="gst_rate" required>
            <option value="0" {{ (int) $defaultGst === 0 ? 'selected' : '' }}>No GST</option>
            <option value="18" {{ (int) $defaultGst === 18 ? 'selected' : '' }}>GST 18%</option>
            <option value="5" {{ (int) $defaultGst === 5 ? 'selected' : '' }}>GST 5%</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="discount_amount" class="form-label">Discount (₹)</label>
        <input type="number" class="form-control" id="discount_amount" name="discount_amount"
            step="0.01" min="0" value="{{ $defaultDiscount }}" placeholder="Flat amount off total">
        <small class="text-muted">Price basis — flat rupee discount before grand total</small>
    </div>
    <div class="col-md-4">
        <label for="notes" class="form-label">Remark</label>
        <textarea class="form-control" id="notes" name="notes" rows="2"
            maxlength="2000" placeholder="Order notes / remark">{{ $defaultNotes }}</textarea>
    </div>
</div>
