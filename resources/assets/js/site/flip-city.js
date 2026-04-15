$(document).ready(function() {
    // -------------------------------------------------------
    // Eltelt idő és Várható díj frissítése (live)
    // -------------------------------------------------------
    function updateElapsedTimes() {
        $('.elapsed-time').each(function () {
            var startStr = $(this).data('start');
            if (!startStr) return;
            var start = new Date(startStr);
            var now = new Date();
            var diffMs = now - start;
            var diffMins = Math.ceil(diffMs / 60000);
            if (diffMins < 1) diffMins = 1;

            var hours = Math.floor(diffMins / 60);
            var mins = diffMins % 60;
            var text = hours > 0 ? (hours + ' óra ' + mins + ' perc') : (diffMins + ' perc');
            $(this).text(text);

            // Várható díj frissítése
            var $row = $(this).closest('tr');
            var rate = parseFloat($row.data('rate'));
            var guestCountText = $row.find('.guest-count').text();
            var guestCount = parseInt(guestCountText) || 1;
            
            if (rate && $row.find('.expected-fee').length) {
                var fee = Math.round((diffMins / 60) * rate * guestCount);
                $row.find('.expected-fee').text(numberFormat(fee) + ' Ft');
            }
        });
    }

    function numberFormat(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    if ($('.elapsed-time').length) {
        updateElapsedTimes();
        setInterval(updateElapsedTimes, 15000);
    }

    console.log('Flip-City Site JS loaded.');
});
