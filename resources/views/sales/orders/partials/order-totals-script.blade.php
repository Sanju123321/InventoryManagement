<script>
    function toggleGstRow() {
        const gstRate = parseInt(document.getElementById('gst_rate')?.value ?? '18', 10);
        const gstRow = document.getElementById('gstRow');
        const label = document.getElementById('gstRateLabel');
        if (gstRow) {
            gstRow.style.display = gstRate === 0 ? 'none' : '';
        }
        if (label) {
            label.textContent = gstRate === 0 ? '—' : String(gstRate);
        }
    }

    function recalculateOrderTotals() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
            const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
            const total = qty * price;
            const lineEl = row.querySelector('.line-total');
            if (lineEl) lineEl.textContent = total.toFixed(2);
            subtotal += total;
        });

        const gstRate = parseInt(document.getElementById('gst_rate')?.value ?? '0', 10);
        const discount = parseFloat(document.getElementById('discount_amount')?.value) || 0;

        const gstAmount = gstRate === 0 ? 0 : subtotal * gstRate / 100;
        const beforeDiscount = subtotal + gstAmount;
        const appliedDiscount = Math.min(Math.max(0, discount), beforeDiscount);
        const grandTotal = beforeDiscount - appliedDiscount;

        const fmt = n => '₹' + n.toFixed(2);
        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = fmt(val);
        };

        set('displaySubtotal', subtotal);
        set('displayGst', gstAmount);
        set('displayDiscount', appliedDiscount);
        set('displayGrandTotal', grandTotal);
        toggleGstRow();
    }

    function bindOrderItemEvents() {
        document.querySelectorAll('.remove-row').forEach(btn => {
            btn.onclick = function() {
                if (document.querySelectorAll('.item-row').length > 1) {
                    this.closest('.item-row').remove();
                    recalculateOrderTotals();
                }
            };
        });

        document.querySelectorAll('.product-select').forEach(select => {
            select.onchange = function() {
                const option = this.options[this.selectedIndex];
                const price = option.getAttribute('data-price') || 0;
                this.closest('.item-row').querySelector('.price-input').value = price;
                recalculateOrderTotals();
            };
        });

        document.querySelectorAll('.qty-input, .price-input').forEach(input => {
            input.oninput = recalculateOrderTotals;
        });
    }

    document.getElementById('gst_rate')?.addEventListener('change', recalculateOrderTotals);
    document.getElementById('discount_amount')?.addEventListener('input', recalculateOrderTotals);
</script>
