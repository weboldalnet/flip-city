$(function() {
    $('#submitScan').on('click', function() {
        let formData = $('#qrScanForm').serialize();
        $.ajax({
            url: '/admin/flip-city/entries/scan',
            method: 'POST',
            data: formData + '&_token=' + $('meta[name="csrf-token"]').attr('content'),
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    $('.checkout-btn').on('click', function() {
        let entryId = $(this).data('id');
        if (confirm('Biztosan ki szeretné léptetni a vendéget?')) {
            $.ajax({
                url: '/admin/flip-city/entries/' + entryId + '/checkout',
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    if (response.success) {
                        alert('Fizetendő: ' + response.total_cost + ' Ft');
                        location.reload();
                    }
                }
            });
        }
    });
});
