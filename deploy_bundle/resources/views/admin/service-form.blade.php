@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container">
        <div class="card" style="max-width:920px;">
            <h1>Admin service editor</h1>
            <form method="POST" action="{{ route('admin.services.update', $service) }}">
                @csrf
                @method('PUT')
                <div class="grid grid-2">
                    <label>Provider
                        <select name="provider_id" required>
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" @selected(old('provider_id', $service->provider_id) == $provider->id)>{{ $provider->user->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Category
                        <select name="category_id" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $service->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Title<input type="text" name="title" value="{{ old('title', $service->title) }}" required></label>
                    <label>Short description<input type="text" name="short_description" value="{{ old('short_description', $service->short_description) }}" required></label>
                    <label>Price<input type="number" name="price" value="{{ old('price', $service->price) }}" required></label>
                    <label>Price type
                        <select name="price_type"><option value="fixed" @selected(old('price_type', $service->price_type) === 'fixed')>Fixed</option><option value="hourly" @selected(old('price_type', $service->price_type) === 'hourly')>Hourly</option></select>
                    </label>
                    <label>Duration minutes<input type="number" name="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes) }}" required></label>
                    <label style="display:flex;align-items:center;gap:10px;margin-top:30px;"><input type="checkbox" name="is_active" value="1" style="width:auto;margin-top:0;" {{ old('is_active', $service->is_active) ? 'checked' : '' }}> Active</label>
                </div>
                <label>Description<textarea name="description" required>{{ old('description', $service->description) }}</textarea></label>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button class="btn brand" type="submit">Save changes</button>
                    <a class="btn secondary" href="{{ route('dashboard') }}">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
