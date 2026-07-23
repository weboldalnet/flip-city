@extends('site.layouts.layout')

@section('title', 'Jelszó visszaállítása - ' . config('app.name'))
@section('og_title', 'Jelszó visszaállítása - ' . config('app.name'))

@section('extendCSS')
    @if($flipCitySettings['css_published'])
        <link rel="stylesheet" href="{{ asset('packages/flip-city/css/site/flip-city.css') }}">
    @endif
@endsection

@section('content')
<div class="flipcity flipcity-reset-password">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">{{ isset($is_setup) ? 'Jelszó beállítása' : 'Jelszó visszaállítása' }}</h4>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('flip-city.password.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            @if(isset($is_setup))
                                <input type="hidden" name="email" value="{{ $email }}">
                                <div class="alert alert-info mb-3">
                                    Jelszó beállítása a következőhöz: <strong>{{ $email }}</strong>
                                </div>
                            @else
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">E-mail cím</label>
                                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
                                </div>
                            @endif

                            <div class="form-group mb-3">
                                <label for="password" class="form-label">{{ isset($is_setup) ? 'Jelszó' : 'Új jelszó' }}</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="password_confirmation" class="form-label">{{ isset($is_setup) ? 'Jelszó megerősítése' : 'Új jelszó megerősítése' }}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">{{ isset($is_setup) ? 'Fiók aktiválása' : 'Jelszó mentése' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('script')
    @if($flipCitySettings['js_published'])
        <script src="{{ asset('packages/flip-city/js/site/flip-city.js') }}"></script>
    @endif
@endsection

@endsection
