@extends('layouts.admin')

@section('pageTitle') Settings @endsection

@section('pageContent')
    <h1 class="h3 mb-4">Settings</h1>

    @include('partials.errors')

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card card-body border-0 shadow-sm admin-form-narrow" data-validate novalidate>
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label" for="order_email">Email that receives new orders</label>
            <input type="email" class="form-control" id="order_email" name="order_email" value="{{ old('order_email', $orderEmail) }}" data-rules="required|email|max:100">
            <div class="form-text">Every new order is sent to this address.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="contact_email">Email that receives contact messages (optional)</label>
            <input type="email" class="form-control" id="contact_email" name="contact_email" value="{{ old('contact_email', $contactEmail) }}" data-rules="email|max:100">
            <div class="form-text">Messages from the contact page. Empty = the order email above. Also shown in the shop footer (only when filled in here).</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="contact_phone">Phone number (optional)</label>
            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $contactPhone) }}" placeholder="+381 11 123 4567" data-rules="max:30">
            <div class="form-text">Shown on the contact page and in the shop footer.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="instagram_url">Instagram profile (optional)</label>
            <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="{{ old('instagram_url', $instagramUrl) }}" placeholder="https://www.instagram.com/shopmoda" data-rules="max:200">
            <div class="form-text">Shown as an Instagram icon in the shop navigation. Empty = no icon.</div>
        </div>
        <button class="btn btn-dark">Save</button>
    </form>
@endsection
