@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<h1>Tambah User</h1>

<form method="POST" action="{{ route('owner.users.store') }}">
    @csrf

    @include('owner.user_management.partials.form')

    <button>Simpan</button>
</form>

@endsection