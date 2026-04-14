<div class="modal fade" id="qrCodeModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/validate-reservation" method="POST" class="js-ajax-form">
                @csrf
                <div class="modal-header py-2 bg-dark text-white align-items-center">
                    <h5 class="modal-title font-weight-bold"><i class="fa fa-qrcode"></i> QR Kód beolvasás</h5>
                    <a type="button" data-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times fs-22"></i>
                    </a>
                </div>

                <div class="modal-body py-2 px-2 bg-light" id="qrCodeContent">
                    <div id="qrCodeScanner"></div>

                    <div id="qrCodeResult" class="js-ajax-response px-1">

                    </div>

                    <div class="js-ajax-error fs-15">
                        <div class="p-2 alert alert-danger d-none" role="alert">

                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between align-items-center py-2">
                    <button type="button" class="btn btn-sm btn-outline-dark font-weight-bold fs-16" data-dismiss="modal">
                        bezárás
                    </button>
                    <button type="submit" id="qrOpenBtn" class="btn btn-success font-weight-bold fs-18" disabled>
                        <i class="fa-solid fa-door-open"></i> Beengedés
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
