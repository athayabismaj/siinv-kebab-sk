@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')
    @include('reports.partials.expense_report_content', [
        'routePrefix' => 'admin.reports',
        'canInput' => true,
    ])
@endsection
