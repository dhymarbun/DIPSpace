@extends('layouts.main')

@section('title', 'Profil Saya - DIPSpace')

@section('content')
    <h1 class="page-title">Profil Saya</h1>

    @include('profile.partials.update-profile-information-form')
    @include('profile.partials.update-password-form')
    @include('profile.partials.delete-user-form')
@endsection