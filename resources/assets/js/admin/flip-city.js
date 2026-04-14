$(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var currentEntryId = null;
    var currentPartialEntryId = null;
    var html5QrCode = null;
    
    // -------------------------------------------------------
    // QR Beolvasó Modal és Kamera
    // -------------------------------------------------------
    $('#scanQrModal').on('shown.bs.modal', function () {
        $('#qr_token_input').val('').focus();
        $('#qr_result').html('');
        $('#qr_guest_count_wrap').addClass('d-none');
        startCamera();
    });

    $('#scanQrModal').on('hidden.bs.modal', function () {
        stopCamera();
    });

    function startCamera() {
        if (html5QrCode === null) {
            html5QrCode = new Html5Qrcode("qr-reader");
        }

        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        html5QrCode.start({ facingMode: "environment" }, config, (decodedText, decodedResult) => {
            $('#qr_token_input').val(decodedText);
            triggerScan();
            stopCamera(); // Megállítjuk az első sikeres olvasás után
        }).catch((err) => {
            console.error("Hiba a kamera indításakor: ", err);
            $('#qr_result').html('<div class="alert alert-warning small">Nem sikerült elérni a kamerát. Használja a manuális bevitelt.</div>');
        });
    }

    function stopCamera() {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().then((ignore) => {
                // Stopped
            }).catch((err) => {
                console.error("Hiba a kamera leállításakor: ", err);
            });
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
                            $('#scanQrModal').modal('hide');
                            openCheckout(response.entry_id);
                        }, 800);
                    } else if (response.action === 'confirm_checkin') {
                        showCheckinConfirmation(response);
                    }
                } else {
                    $('#qr_result').html('<div class="alert alert-danger"><i class="fas fa-times-circle mr-1"></i>' + response.message + '</div>');
                }
            },
            error: function () {
                $('#qr_result').html('<div class="alert alert-danger">Hiba történt a feldolgozás során!</div>');
            }
        });
    }

    function showCheckinConfirmation(data) {
        var user = data.user;
        var booking = data.booking;
        var guestCount = booking ? booking.guest_count : 1;

        var html = '<div class="card bg-light border-primary mt-3">' +
            '<div class="card-body">' +
            '<h6 class="card-title font-weight-bold text-primary mb-3"><i class="fas fa-user-check mr-2"></i>' + user.name + '</h6>' +
            (booking ? '<div class="alert alert-success small py-2 mb-3"><i class="fas fa-calendar-check mr-1"></i>Találtunk mai foglalást (' + booking.guest_count + ' fő)!</div>' : '') +
            '<div class="form-group mb-3">' +
            '<label class="small font-weight-bold">Vendégek száma:</label>' +
            '<input type="number" id="final_guest_count" class="form-control" value="' + guestCount + '" min="1" max="50">' +
            '</div>' +
            '<button type="button" id="confirm_checkin_btn" class="btn btn-primary btn-block" data-token="' + user.qr_code_token + '">' +
            '<i class="fas fa-sign-in-alt mr-1"></i> Beléptetés indítása' +
            '</button>' +
            '</div>' +
            '</div>';

        $('#qr_result').html(html);

        $('#confirm_checkin_btn').on('click', function () {
            var token = $(this).data('token');
            var count = $('#final_guest_count').val();

            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Beléptetés...');

            $.ajax({
                url: '/flip-city/entries',
                method: 'POST',
                data: {
                    _token: csrfToken,
                    qr_code_token: token,
                    guest_count: count
                },
                success: function (res) {
                    if (res.success) {
                        $('#qr_result').html('<div class="alert alert-success mt-3"><i class="fas fa-check-circle mr-1"></i>' + res.message + '</div>');
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        $('#qr_result').html('<div class="alert alert-danger mt-3">' + res.message + '</div>');
                        $('#confirm_checkin_btn').prop('disabled', false).text('Beléptetés indítása');
                    }
                },
                error: function () {
                    alert('Hiba történt a beléptetés során!');
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

    // -------------------------------------------------------
    // Kiléptetés (checkout) a táblázatból
    // -------------------------------------------------------
    $(document).on('click', '.checkout-btn', function () {
        var entryId = $(this).data('id');
        openCheckout(entryId);
    });

    function openCheckout(entryId) {
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
                    $('#checkout_amount').text(numberFormat(amount));
                    $('#checkout_duration').text(duration + ' perc, ' + guests + ' fő');
                    $('#checkoutModal').modal('show');
                } else {
                    alert('Hiba: nem sikerült az összeget lekérdezni.');
                }
            },
            error: function () {
                alert('Szerverhiba a kiléptetés során!');
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
                cash_received: cashReceived || null
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
            error: function () {
                alert('Szerverhiba a fizetés során!');
                $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Fizetés Lezárása');
            }
        });
    });

    // -------------------------------------------------------
    // Részleges kiléptetés
    // -------------------------------------------------------
    $(document).on('click', '.partial-btn', function () {
        var entryId = $(this).data('id');
        var max = parseInt($(this).data('max'));
        currentPartialEntryId = entryId;

        $('#partial_total_guests').text(max);
        $('#partial_leaving_count').attr('max', max - 1).val(1);
        $('#partial_result').html('');
        $('#partialCheckoutModal').modal('show');
    });

    $('#partial_confirm_btn').on('click', function () {
        var leavingCount = parseInt($('#partial_leaving_count').val());
        var max = parseInt($('#partial_leaving_count').attr('max')) + 1;

        if (!leavingCount || leavingCount < 1 || leavingCount >= max) {
            $('#partial_result').html('<div class="alert alert-warning">Érvénytelen szám! (1 és ' + (max - 1) + ' között kell lennie)</div>');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Feldolgozás...');

        $.ajax({
            url: '/flip-city/entries/' + currentPartialEntryId + '/partial-checkout',
            method: 'POST',
            data: {
                _token: csrfToken,
                leaving_count: leavingCount
            },
            success: function (response) {
                if (response.success) {
                    $('#partial_result').html(
                        '<div class="alert alert-success">' +
                        '<strong>Részleges kiléptetés rögzítve!</strong><br>' +
                        'Fizetendő összeg: <strong>' + numberFormat(response.total_cost) + ' Ft</strong><br>' +
                        'Bent maradó vendégek: <strong>' + response.remaining_guests + ' fő</strong>' +
                        '</div>'
                    );
                    $btn.html('<i class="fas fa-check mr-1"></i> Részleges Kiléptetés');
                    setTimeout(function () {
                        $('#partialCheckoutModal').modal('hide');
                        location.reload();
                    }, 2000);
                } else {
                    $('#partial_result').html('<div class="alert alert-danger">' + (response.message || 'Hiba történt!') + '</div>');
                    $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Részleges Kiléptetés');
                }
            },
            error: function () {
                $('#partial_result').html('<div class="alert alert-danger">Szerverhiba!</div>');
                $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Részleges Kiléptetés');
            }
        });
    });

    // -------------------------------------------------------
    // Eltelt idő frissítése (live)
    // -------------------------------------------------------
    function updateElapsedTimes() {
        $('.elapsed-time').each(function () {
            var startStr = $(this).data('start');
            if (!startStr) return;
            var start = new Date(startStr);
            var now = new Date();
            var diffMs = now - start;
            var diffMins = Math.floor(diffMs / 60000);
            var hours = Math.floor(diffMins / 60);
            var mins = diffMins % 60;
            var text = hours > 0 ? (hours + ' óra ' + mins + ' perc') : (diffMins + ' perc');
            $(this).text(text);
        });
    }

    updateElapsedTimes();
    setInterval(updateElapsedTimes, 30000);

    // -------------------------------------------------------
    // Segédfüggvények
    // -------------------------------------------------------
    function numberFormat(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
});
