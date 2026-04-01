@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
    @include('reports.partials.expense_report_content', [
        'routePrefix' => 'owner.reports',
        'canInput' => false,
    ])
@endsection