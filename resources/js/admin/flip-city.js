$(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var currentEntryId = null;
    var currentPartialEntryId = null;
    var html5QrCode = null;
    var timerPaused = false;
    var scanLocked = false;
    var cameraUnavailable = false;
    
    // -------------------------------------------------------
    // QR Beolvasó Modal és Kamera
    // -------------------------------------------------------
    $('#scanQrModal').on('shown.bs.modal', function () {
        $('#qr_token_input').val('').focus();
        $('#qr_result').html('');
startCamera();
        timerPaused = true;
    });

    $('#scanQrModal').on('hidden.bs.modal', function () {
        stopCamera();
        timerPaused = false;
        updateElapsedTimes();
    });

    function startCamera() {
        if (html5QrCode === null) {
            html5QrCode = new Html5Qrcode("qr-reader");
        }

        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        if (html5QrCode.isScanning) {
            return;
        }

        scanLocked = false;
        html5QrCode.start({ facingMode: "environment" }, config, (decodedText) => {
            if (scanLocked) {
                return;
            }
            scanLocked = true;
            $('#qr_token_input').val(decodedText);
            stopCamera().then(triggerScan);
        }).catch((err) => {
            console.error("Hiba a kamera indításakor: ", err);
            cameraUnavailable = true;
            if ($('#qr_result').is(':empty')) {
                $('#qr_result').html('<div class="alert alert-warning small">Nem sikerült elérni a kamerát. Használja a manuális bevitelt.</div>');
            }
        });
    }

    function stopCamera() {
        if (html5QrCode && html5QrCode.isScanning) {
            return html5QrCode.stop().catch((err) => {
                console.error("Hiba a kamera leállításakor: ", err);
            });
        }

        return Promise.resolve();
    }

    function restartCameraAfterFailure() {
        if ($('#scanQrModal').hasClass('show') && !cameraUnavailable) {
            startCamera();
        }
    }

    // Enter gomb a QR beolvasásnál
    $('#qr_token_input').on('keypress', function (e) {
        if (e.which === 13) {
            triggerScan();
        }
    });

    $('#manual_scan_btn').on('click', function () {
        triggerScan();
    });

    function triggerScan() {
        var token = $('#qr_token_input').val().trim();
        if (!token) {
            $('#qr_result').html('<div class="alert alert-warning">Kérjük adjon meg egy QR kódot!</div>');
            return;
        }

        $('#qr_result').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Azonosítás...</p></div>');

        $.ajax({
            url: '/flip-city/entries/scan',
            method: 'POST',
            data: {
                _token: csrfToken,
                qr_code_token: token
            },
            success: function (response) {
                if (response.success) {
                    if (response.action === 'checkout') {
                        $('#qr_result').html('<div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i>' + response.message + '</div>');
                        setTimeout(function () {
                            $('#scanQrModal').one('hidden.bs.modal', function () {
                                openCheckout(response.entry_id);
                            }).modal('hide');
                        }, 800);
                    } else if (response.action === 'confirm_checkin') {
                        showCheckinConfirmation(response);
                    }
                } else {
                    $('#qr_result').html('<div class="alert alert-danger"><i class="fas fa-times-circle mr-1"></i>' + escapeHtml(response.message) + '</div>');
                    restartCameraAfterFailure();
                }
            },
            error: function (xhr) {
                $('#qr_result').html('<div class="alert alert-danger">' + escapeHtml(errorMessage(xhr, 'Hiba történt a feldolgozás során!')) + '</div>');
                restartCameraAfterFailure();
            }
        });
    }

    function showCheckinConfirmation(data) {
        var childAgeLimit = $('#dashboard_data').data('child-age') || 6;
        var user = data.user;
        var booking = data.booking;
        var guestCount = booking ? (parseInt(booking.guest_count) || 1) : 1;

        var html = '<div class="card bg-light border-primary mt-3">' +
            '<div class="card-body">' +
            '<h6 class="card-title font-weight-bold text-primary mb-3"><i class="fas fa-user-check mr-2"></i>' + escapeHtml(user.name) + '</h6>' +
            (booking ? '<div class="alert alert-success small py-2 mb-3"><i class="fas fa-calendar-check mr-1"></i>Találtunk mai foglalást (' + guestCount + ' fő)!</div>' : '') +
            (data.notice ? '<div class="alert alert-warning small py-2 mb-3"><i class="fas fa-exclamation-triangle mr-1"></i>' + escapeHtml(data.notice) + '</div>' : '') +
            '<div class="row">' +
            '<div class="col-sm-4">' +
            '<div class="form-group mb-3">' +
            '<label class="small font-weight-bold" for="final_guest_count">Játszók (fő):</label>' +
            '<input type="number" id="final_guest_count" class="form-control" value="' + guestCount + '" min="1" max="50">' +
            '</div>' +
            '</div>' +
            '<div class="col-sm-4">' +
            '<div class="form-group mb-3">' +
            '<label class="small font-weight-bold" for="final_children_count">Ebből gyerek:</label>' +
            '<input type="number" id="final_children_count" class="form-control" value="0" min="0" max="50">' +
            '<small class="form-text">' + childAgeLimit + ' év alatt, kedvezményes</small>' +
            '</div>' +
            '</div>' +
            '<div class="col-sm-4">' +
            '<div class="form-group mb-3">' +
            '<label class="small font-weight-bold" for="final_companions_count">Kísérő (fő):</label>' +
            '<input type="number" id="final_companions_count" class="form-control" value="0" min="0" max="50">' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<button type="button" id="confirm_checkin_btn" class="btn btn-primary btn-block" data-token="' + escapeHtml(user.qr_code_token) + '">' +
            '<i class="fas fa-sign-in-alt mr-1"></i> Beléptetés indítása' +
            '</button>' +
            '</div>' +
            '</div>';

        $('#qr_result').html(html);

        $('#confirm_checkin_btn').on('click', function () {
            var token = $(this).data('token');
            var count = $('#final_guest_count').val();
            var companions = $('#final_companions_count').val();

            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Beléptetés...');
            
            var companionsVal = $('#final_companions_count').val() || 0;

            $.ajax({
                url: '/flip-city/entries',
                method: 'POST',
                data: {
                    _token: csrfToken,
                    qr_code_token: token,
                    guest_count: count,
                    children_count: $('#final_children_count').val() || 0,
                    companions_count: companionsVal
                },
                success: function (res) {
                    if (res.success) {
                        $('#qr_result').html('<div class="alert alert-success mt-3"><i class="fas fa-check-circle mr-1"></i>' + escapeHtml(res.message) + '</div>');
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        $('#qr_result').find('.alert-danger').remove();
                        $('#qr_result').append('<div class="alert alert-danger mt-3">' + escapeHtml(res.message) + '</div>');
                        $('#confirm_checkin_btn').prop('disabled', false).text('Beléptetés indítása');
                    }
                },
                error: function (xhr) {
                    $('#qr_result').find('.alert-danger').remove();
                    $('#qr_result').append('<div class="alert alert-danger mt-3">' + escapeHtml(errorMessage(xhr, 'Hiba történt a beléptetés során!')) + '</div>');
                    $('#confirm_checkin_btn').prop('disabled', false).text('Beléptetés indítása');
                }
            });
        });
    }

    // QR scan után mutasd a vendégszám mezőt belépéshez
    // (csak ha nem checkout)
    $('#qr_token_input').on('input', function () {
        $('#qr_result').html('');
    });

    $(document).on('click', '.checkin-manual-btn', function () {
        var $form = $('#checkin_manual_form');
        var bookingId = $(this).data('booking-id') || '';

        $('#checkin_user_id').val($(this).data('id'));
        $('#checkin_user_name').text($(this).data('name'));
        var notice = $(this).data('notice') || '';
        $('#checkin_notice').text(notice).toggleClass('d-none', !notice);
        $('#checkin_guest_count').val($(this).data('guests') || 1);
        $('#checkin_booking_id').val(bookingId);
        $('#checkin_children_count').val(0);
        $('#checkin_companions_count').val(0);
        $form.attr('action', bookingId ? $form.data('booking-url') : $form.data('manual-url'));
        $('#checkinManualModal').modal('show');
    });

    // -------------------------------------------------------
    // Kiléptetés (checkout) a táblázatból
    // -------------------------------------------------------
    $(document).on('click', '.checkout-btn', function () {
        var entryId = $(this).data('id');
        openCheckout(entryId);
    });

    $('#checkoutModal').on('hidden.bs.modal', function () {
        timerPaused = false;
        updateElapsedTimes();
    });

    function openCheckout(entryId) {
        timerPaused = true;
        currentEntryId = entryId;
        $('#checkout_entry_id').val(entryId);
        $('#checkout_amount').text('...');
        $('#checkout_duration').text('');
        $('#cash_received').val('');
        $('#change_amount').text('0');
        $('#cash_details').show();
        $('input[name="payment_method"][value="cash"]').prop('checked', true).closest('label').addClass('active');
        $('input[name="payment_method"][value="card"]').closest('label').removeClass('active');

        // Lekérdezzük az összeget
        $.ajax({
            url: '/flip-city/entries/' + entryId + '/checkout',
            method: 'POST',
            data: { _token: csrfToken },
            success: function (response) {
                if (response.success) {
                    var amount = response.total_cost;
                    var duration = response.duration;
                    var guests = response.guest_count;

                    // Breakdown adatok
                    var compCount = parseInt(response.companions_count) || 0;
                    var childCount = parseInt(response.children) || 0;
                    $('#checkout_details_breakdown').show();
                    $('#checkout_breakdown_guests').text(response.adults);
                    $('#checkout_breakdown_base').text(numberFormat(response.adult_cost));
                    $('#checkout_breakdown_adults_row').toggle(parseInt(response.adults) > 0);
                    $('#checkout_breakdown_children_count').text(childCount);
                    $('#checkout_breakdown_children').text(numberFormat(response.child_cost));
                    $('#checkout_breakdown_children_row').toggle(childCount > 0);
                    $('#checkout_breakdown_companions_count').text(compCount);
                    $('#checkout_breakdown_companions').text(numberFormat(response.companions_cost));
                    $('#checkout_breakdown_companions_row').toggle(compCount > 0);
                    if (response.min_applied) {
                        $('#checkout_min_note').text('Bent töltött idő: ' + duration + ' perc, a minimum ' + response.min_minutes + ' perc díja került felszámításra fejenként.').show();
                    } else {
                        $('#checkout_min_note').hide();
                    }

                    $('#checkout_amount').text(numberFormat(amount));
                    $('#checkout_duration').text(duration + ' perc, ' + guests + ' fő');
                    $('#checkoutModal').modal('show');
                } else {
                    timerPaused = false;
                    alert(response.message || 'Hiba: nem sikerült az összeget lekérdezni.');
                }
            },
            error: function (xhr) {
                timerPaused = false;
                alert(errorMessage(xhr, 'Szerverhiba a kiléptetés során!'));
            }
        });
    }

    // Visszajáró számolás
    $('#cash_received').on('input', function () {
        var amount = parseFloat($('#checkout_amount').text().replace(/\s/g, '')) || 0;
        var received = parseFloat($(this).val()) || 0;
        var change = Math.max(0, received - amount);
        $('#change_amount').text(numberFormat(change));
    });

    // Fizetési mód váltás
    $('input[name="payment_method"]').on('change', function () {
        if ($(this).val() === 'card') {
            $('#cash_details').hide();
        } else {
            $('#cash_details').show();
        }
    });

    // Checkout form beküldése -> invoice store
    $('#checkout_form').on('submit', function (e) {
        e.preventDefault();

        var paymentMethod = $('input[name="payment_method"]:checked').val();
        var cashReceived = $('#cash_received').val();
        var entryId = $('#checkout_entry_id').val();
        var amount = parseFloat($('#checkout_amount').text().replace(/\s/g, '')) || 0;
        var duration = $('#checkout_duration').text(); // Ezt a szerver válaszából kaptuk

        if (paymentMethod === 'cash' && (!cashReceived || parseFloat(cashReceived) < amount)) {
            alert('A kapott összeg nem lehet kevesebb a fizetendőnél (' + numberFormat(amount) + ' Ft)!');
            return;
        }

        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Feldolgozás...');

        $.ajax({
            url: '/flip-city/invoices',
            method: 'POST',
            data: {
                _token: csrfToken,
                entry_id: entryId,
                payment_method: paymentMethod,
                cash_received: cashReceived || null,
                final_cost: amount // Beküldjük a rögzített összeget
            },
            success: function (response) {
                if (response.success) {
                    $('#checkoutModal').modal('hide');

                    var msg = 'Fizetés sikeresen rögzítve!';
                    if (paymentMethod === 'cash') {
                        msg += '\nVissza kell adni: ' + numberFormat(response.change) + ' Ft';
                    }
                    alert(msg);
                    location.reload();
                } else {
                    alert('Hiba: ' + response.message);
                    $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Fizetés Lezárása');
                }
            },
            error: function (xhr) {
                alert(errorMessage(xhr, 'Szerverhiba a fizetés során!'));
                $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Fizetés Lezárása');
            }
        });
    });

    // -------------------------------------------------------
    // Részleges kiléptetés
    // -------------------------------------------------------
    $(document).on('click', '.partial-btn', function () {
        timerPaused = true;
        var entryId = $(this).data('id');
        var max = parseInt($(this).data('max'));
        var children = parseInt($(this).data('children')) || 0;
        currentPartialEntryId = entryId;
        $('#partial_total_children').text(children);
        $('#partial_leaving_children').attr('max', children).val(0).prop('disabled', children === 0);

        $('#partial_entry_id').val(entryId);
        $('#partial_total_guests').text(max);
        $('#partial_leaving_count').attr('max', max - 1).val(1).prop('disabled', false);
        $('#partial_result').html('');
        
        // Form alaphelyzetbe
        $('#partial_payment_details').hide();
        $('#partial_calc_btn').show();
        $('#partial_confirm_btn').hide();
        $('#partial_cash_received').val('');
        $('#partial_change_amount').text('0');
        $('#partial_pay_cash').prop('checked', true).closest('label').addClass('active');
        $('#partial_pay_card').closest('label').removeClass('active');
        $('#partial_cash_details').show();

        $('#partialCheckoutModal').modal('show');
    });

    $('#partialCheckoutModal').on('hidden.bs.modal', function () {
        timerPaused = false;
        updateElapsedTimes();
    });

    // Részleges számolás gomb
    $('#partial_calc_btn').on('click', function () {
        var leavingCount = parseInt($('#partial_leaving_count').val());
        var max = parseInt($('#partial_leaving_count').attr('max')) + 1;

        if (!leavingCount || leavingCount < 1 || leavingCount >= max) {
            $('#partial_result').html('<div class="alert alert-warning">Érvénytelen szám! (1 és ' + (max - 1) + ' között kell lennie)</div>');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Számolás...');

        $.ajax({
            url: '/flip-city/entries/' + currentPartialEntryId + '/checkout',
            method: 'POST',
            data: {
                _token: csrfToken,
                partial: true,
                leaving_count: leavingCount,
                leaving_children: parseInt($('#partial_leaving_children').val()) || 0
            },
            success: function (response) {
                $btn.prop('disabled', false).html('<i class="fas fa-calculator mr-1"></i> Összeg számítása');
                if (response.success) {
                    $('#partial_checkout_amount').text(numberFormat(response.total_cost));
                    $('#partial_payment_details').fadeIn();
                    $('#partial_calc_btn').hide();
                    $('#partial_confirm_btn').show();
                    $('#partial_leaving_count').prop('disabled', true);
                    $('#partial_leaving_children').prop('disabled', true);
                    $('#partial_result').html('');
                } else {
                    $('#partial_result').html('<div class="alert alert-danger">' + escapeHtml(response.message || 'Hiba!') + '</div>');
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-calculator mr-1"></i> Összeg számítása');
                $('#partial_result').html('<div class="alert alert-danger">' + escapeHtml(errorMessage(xhr, 'Hiba az összeg lekérdezésekor!')) + '</div>');
            }
        });
    });

    // Részleges visszajáró számolás
    $('#partial_cash_received').on('input', function () {
        var amount = parseFloat($('#partial_checkout_amount').text().replace(/\s/g, '')) || 0;
        var received = parseFloat($(this).val()) || 0;
        var change = Math.max(0, received - amount);
        $('#partial_change_amount').text(numberFormat(change));
    });

    // Részleges fizetési mód váltás
    $('input[name="partial_payment_method"]').on('change', function () {
        if ($(this).val() === 'card') {
            $('#partial_cash_details').hide();
        } else {
            $('#partial_cash_details').show();
        }
    });

    // Részleges fizetés beküldése
    $('#partial_checkout_form').on('submit', function (e) {
        e.preventDefault();

        var leavingCount = $('#partial_leaving_count').val();
        var paymentMethod = $('input[name="partial_payment_method"]:checked').val();
        var cashReceived = $('#partial_cash_received').val();
        var amount = parseFloat($('#partial_checkout_amount').text().replace(/\s/g, '')) || 0;

        if (paymentMethod === 'cash' && (!cashReceived || parseFloat(cashReceived) < amount)) {
            alert('A kapott összeg nem lehet kevesebb a fizetendőnél (' + numberFormat(amount) + ' Ft)!');
            return;
        }

        var $btn = $('#partial_confirm_btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Feldolgozás...');

        $.ajax({
            url: '/flip-city/entries/' + currentPartialEntryId + '/partial-checkout',
            method: 'POST',
            data: {
                _token: csrfToken,
                leaving_count: leavingCount,
                leaving_children: parseInt($('#partial_leaving_children').val()) || 0,
                payment_method: paymentMethod,
                cash_received: cashReceived || null,
                final_cost: amount
            },
            success: function (response) {
                if (response.success) {
                    $('#partialCheckoutModal').modal('hide');
                    var msg = 'Részleges fizetés sikeres!';
                    if (paymentMethod === 'cash') {
                        msg += '\nVissza kell adni: ' + numberFormat(response.change || 0) + ' Ft';
                    }
                    alert(msg);
                    location.reload();
                } else {
                    resetPartialForm($btn);
                    $('#partial_result').html('<div class="alert alert-danger">' + escapeHtml(response.message || 'Hiba!') + '</div>');
                }
            },
            error: function (xhr) {
                resetPartialForm($btn);
                $('#partial_result').html('<div class="alert alert-danger">' + escapeHtml(errorMessage(xhr, 'Szerverhiba a részleges fizetés során!')) + '</div>');
            }
        });
    });

    function resetPartialForm($btn) {
        $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Fizetés és Kiléptetés').hide();
        $('#partial_payment_details').hide();
        $('#partial_calc_btn').show();
        $('#partial_leaving_count').prop('disabled', false);
        $('#partial_leaving_children').prop('disabled', (parseInt($('#partial_total_children').text()) || 0) === 0);
    }

    // -------------------------------------------------------
    // Eltelt idő frissítése (live)
    // -------------------------------------------------------
    function updateElapsedTimes() {
        if (timerPaused) return;

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
            var guestCount = parseInt($row.data('guests')) || 1;
            var childCount = parseInt($row.data('children')) || 0;
            var companionsCount = parseInt($row.data('companions')) || 0;
            var companionPrice = parseFloat($('#dashboard_data').data('companion-price')) || 0;
            var minMinutes = parseInt($('#dashboard_data').data('min-minutes')) || 0;
            var billed = Math.max(diffMins, minMinutes);

            if (rate && $row.find('.expected-fee').length) {
                var fee = Math.round((billed / 60) * rate * (guestCount - childCount))
                    + Math.round((billed / 60) * childRate * childCount)
                    + (companionsCount * companionPrice);
                $row.find('.expected-fee').text(numberFormat(fee) + ' Ft');
            }
        });
    }

    updateElapsedTimes();
    setInterval(updateElapsedTimes, 10000); // 10 másodpercenként frissítünk a jobb élményért

    // -------------------------------------------------------
    // Segédfüggvények
    // -------------------------------------------------------
    function escapeHtml(text) {
        return $('<div>').text(text == null ? '' : String(text)).html().replace(/"/g, '&quot;');
    }

    function errorMessage(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) {
            return json.errors[Object.keys(json.errors)[0]][0];
        }
        if (xhr && xhr.status === 419) {
            return 'Lejárt a munkamenet, frissítsd az oldalt.';
        }
        return (json && json.message) || fallback;
    }

    function numberFormat(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
});
