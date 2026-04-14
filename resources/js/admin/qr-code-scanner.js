/** --- QR Code scanner modal --------- */
const qrCodeModal = $('#qrCodeModal');
const qrCodeScanner = $('#qrCodeScanner');
const qrCodeResult = $('#qrCodeResult');
const qrOpenBtn = $('#qrOpenBtn');

let htmlQRCodeScanner;

function openQRCodeModal() {
    qrCodeModal.modal('show');
    qrOpenBtn.attr('disabled', true);
    qrOpenBtn.removeClass('d-none');

    let formError = qrCodeModal.find('.js-ajax-error').children('div');
    formError.attr('class', 'd-none');
    qrCodeResult.html('');

    setTimeout(function() {
        createQRCodeScanner();
    }, 500);
}

/** --- QR Kód scan lekezelése --------- */
function onScanSuccess(decodedText, decodedResult) {
    //console.log(`Scan result: ${decodedText}`, decodedResult);
    htmlQRCodeScanner.clear();
    getReservationByGuid(decodedText);
}

/** --- Kamera létrehozása --------- */
function createQRCodeScanner() {
    htmlQRCodeScanner = new Html5QrcodeScanner(
        "qrCodeScanner", {
            fps: 60,
            qrbox: 300
        });

    htmlQRCodeScanner.render(onScanSuccess);
}

/** --- Kamera létrehozása --------- */
function getReservationByGuid(guid) {
    let formError = qrCodeModal.find('.js-ajax-error').children('div');

    $.ajax({
        url: '/get-reservation-by-guid',
        data: {
            guid: guid,
        },
        success: function (response) {
            qrCodeResult.html(response['view']);
            qrOpenBtn.attr('disabled', false);
        },
        error: function(xhr, status, error) {
            let errorData = JSON.parse(xhr.responseText);

            formError.attr('class', errorData.alertClass);
            formError.html(errorData.message);

            qrOpenBtn.attr('disabled', true);
        }
    });
}
