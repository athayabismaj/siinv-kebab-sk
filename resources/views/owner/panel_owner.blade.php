@extends('layouts.app')

@section('title', 'Ringkasan Operasional')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<h1 class="text-2xl font-bold mb-8">
    Ringkasan Operasional
</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="text-sm text-gray-500 mb-2">
            Total Transaksi Hari Ini
        </h2>
        <p class="text-2xl font-bold text-blue-700">
            0
        </p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="text-sm text-gray-500 mb-2">
            Total Pendapatan Hari Ini
        </h2>
        <p class="text-2xl font-bold text-green-600">
            Rp 0
        </p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="text-sm text-gray-500 mb-2">
            Bahan Baku Hampir Habis
        </h2>
        <p class="text-2xl font-bold text-red-600">
            0
        </p>
    </div>

</div>

@endsection