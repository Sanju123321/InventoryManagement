<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locationInput = document.getElementById('google_location');
        const addressInput = document.getElementById('address');
        const testBtn = document.getElementById('testLocationBtn');
        const generateBtn = document.getElementById('generateFromAddressBtn');

        if (!locationInput) {
            return;
        }

        function buildMapsSearchUrl(address) {
            return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(address.trim());
        }

        function openLocation(url) {
            if (!url) {
                alert('Enter a Google Maps link or address first.');
                return;
            }
            window.open(url, '_blank', 'noopener');
        }

        testBtn?.addEventListener('click', function () {
            const link = locationInput.value.trim();
            if (link) {
                const url = /^https?:\/\//i.test(link) ? link : 'https://' + link;
                openLocation(url);
                return;
            }
            const address = addressInput?.value?.trim();
            if (address) {
                openLocation(buildMapsSearchUrl(address));
                return;
            }
            alert('Paste a Google Maps link or fill in the Address field.');
        });

        generateBtn?.addEventListener('click', function () {
            const address = addressInput?.value?.trim();
            if (!address) {
                alert('Please enter the customer address first.');
                addressInput?.focus();
                return;
            }
            locationInput.value = buildMapsSearchUrl(address);
        });
    });
</script>
