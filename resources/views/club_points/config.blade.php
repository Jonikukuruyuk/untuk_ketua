@extends('backend.layouts.app')
@section('content')
    @php
    // $club_point_convert_rate = \App\Models\BusinessSetting::where('type', 'club_point_convert_rate')->first();
    $cps = \App\Models\ClubPointSetting::all();
    @endphp
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Convert Point To Wallet') }}</h5>
                </div>
                <div class="card-body">
                    {{-- <form class="form-horizontal" action="{{ route('point_convert_rate_store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="club_point_convert_rate">
                        <div class="form-group row">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Set Point For ') }}
                                    {{ single_price(1) }}</label>
                            </div>
                            <div class="col-lg-5">
                                <input type="number" min="0" step="0.01" class="form-control" name="value"
                                    @if ($club_point_convert_rate != null) value="{{ $club_point_convert_rate->value }}" @endif
                                    placeholder="100" required>
                            </div>
                            <div class="col-lg-3">
                                <label class="col-from-label">{{ translate('Points') }}</label>
                            </div>
                        </div>
                        <div class="form-group mb-3 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form> --}}
                    <form class="form-horizontal" action="{{ route('point_convert_rate_store') }}" method="POST">
                        @csrf
                        @for ($i = 1; $i <= 8; $i++)
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Hadiah {{ $i }}</label>
                                                <input type="text" name="hadiah[]"
                                                    value="{{ $cps->count() > 0 ? $cps[$i - 1]->hadiah : '' }}"
                                                    class="form-control" required />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Jumlah Point {{ $i }}</label>
                                                <input type="number" name="point[]"
                                                    value="{{ $cps->count() > 0 ? $cps[$i - 1]->point : '' }}"
                                                    class="form-control" required />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                        <div class="form-group mb-3 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                    <i
                        class="fs-12"><b>{{ translate('Note: You need to activate wallet option first before using club point addon.') }}</b></i>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ static_asset('assets/js/jquery.repeater.min.js') }}"></script>
    {{-- <script src="{{ static_asset('assets/js/jquery.form-repeater.js') }}"></script> --}}
    <script>
        $('.repeater-custom-show-hide').repeater({
            show: function() {
                $(this).slideDown();
            },
            hide: function(remove) {
                $(this).slideUp(remove);
            }
        });
    </script>
@endsection
