@php($fcPrefix = $prefix ?? 'checkin')
<div class="row fc-counts">
    <div class="col-sm-4">
        <div class="form-group">
            <label for="{{ $fcPrefix }}_guest_count">Játszók (fő) <span class="text-danger">*</span></label>
            <input type="number" name="guest_count" id="{{ $fcPrefix }}_guest_count" class="form-control" value="{{ $guests ?? 1 }}" min="1" required>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            <label for="{{ $fcPrefix }}_children_count">Ebből gyerek</label>
            <input type="number" name="children_count" id="{{ $fcPrefix }}_children_count" class="form-control" value="0" min="0">
            <small class="form-text">{{ $flipCitySettings['child_age_limit'] ?? 6 }} év alatt, kedvezményes díj</small>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            <label for="{{ $fcPrefix }}_companions_count">Kísérő (fő)</label>
            <input type="number" name="companions_count" id="{{ $fcPrefix }}_companions_count" class="form-control" value="0" min="0">
            <small class="form-text">egyszeri fix díj</small>
        </div>
    </div>
</div>
