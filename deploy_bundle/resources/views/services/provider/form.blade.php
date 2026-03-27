@extends('layouts.app')

@section('content')
<div class="page">
    <div class="container">
        <div class="card" style="max-width:840px;">
            <h1>{{ $title }}</h1>
            <p class="muted">Provider apni service details, pricing, duration aur category yahan manage kar sakta hai.</p>
            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
                @csrf
                @if($service->exists)
                    @method('PUT')
                @endif
                <div class="grid grid-2">
                    <label>Category
                        <select name="category_id" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $service->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Service title<input type="text" name="title" value="{{ old('title', $service->title) }}" required></label>
                    <label>Short description<input type="text" name="short_description" value="{{ old('short_description', $service->short_description) }}" required></label>
                    <label>Price<input type="number" name="price" min="1" value="{{ old('price', $service->price) }}" required></label>
                    <label>Price type
                        <select name="price_type">
                            <option value="fixed" @selected(old('price_type', $service->price_type) === 'fixed')>Fixed</option>
                            <option value="hourly" @selected(old('price_type', $service->price_type) === 'hourly')>Hourly</option>
                        </select>
                    </label>
                    <label>Duration in minutes<input type="number" name="duration_minutes" min="15" value="{{ old('duration_minutes', $service->duration_minutes ?: 60) }}" required></label>
                    <label>Service image<input type="file" name="image" accept="image/*"></label>
                    <label style="display:flex;align-items:center;gap:10px;margin-top:30px;"> 
                        <input type="checkbox" name="is_active" value="1" style="width:auto;margin-top:0;" {{ old('is_active', $service->exists ? $service->is_active : true) ? 'checked' : '' }}>
                        Active service
                    </label>
                </div>
                @if($service->image_path)
                    <div style="margin-bottom:16px;"><img src="{{ asset($service->image_path) }}" alt="{{ $service->title }}" style="width:220px;border-radius:18px;border:1px solid #ddd;"></div>
                @endif
                <label>Full description<textarea name="description" required>{{ old('description', $service->description) }}</textarea></label>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button class="btn brand" type="submit">{{ $submitLabel }}</button>
                    <a class="btn secondary" href="{{ route('dashboard') }}">Back to dashboard</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
