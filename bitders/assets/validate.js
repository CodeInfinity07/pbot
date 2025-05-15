$(document).ready(function() {
    // Initialize DataTables
    $('#kt_licenses_table').DataTable({
        "info": false,
        "pageLength": 10
    });

    // Domain validation function
    function isValidDomain(domain) {
        const pattern = /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/;
        return pattern.test(domain);
    }

    // Form validation
    $('form[id^="kt_modal_add_domain_form_"]').each(function() {
        this.addEventListener('submit', function(event) {
            const domainInput = this.querySelector('input[name="domain"]');
            if (!domainInput.value || !isValidDomain(domainInput.value)) {
                event.preventDefault();
                event.stopPropagation();
                domainInput.classList.add('is-invalid');
            } else {
                domainInput.classList.remove('is-invalid');
            }
        }, false);

        // Real-time validation
        this.querySelector('input[name="domain"]').addEventListener('input', function() {
            if (this.value && !isValidDomain(this.value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
});