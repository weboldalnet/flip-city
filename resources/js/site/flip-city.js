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
            var childRate = parseFloat($row.data('child-rate'));
            if (isNaN(childRate)) childRate = rate;
            var guestCount = parseInt($row.data('guests')) || parseInt($row.find('.guest-count').text()) || 1;
            var childCount = parseInt($row.data('children')) || 0;
            var companions = parseInt($row.data('companions')) || 0;
            var companionPrice = parseFloat($row.data('companion-price')) || 0;
            var billed = Math.max(diffMins, parseInt($row.data('min-minutes')) || 0);

            if (rate && $row.find('.expected-fee').length) {
                var totalFee = Math.round((billed / 60) * rate * (guestCount - childCount))
                    + Math.round((billed / 60) * childRate * childCount)
                    + companions * companionPrice;
                $row.find('.expected-fee').text(numberFormat(totalFee) + ' Ft');
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

});
