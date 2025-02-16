@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Admin Dashboard</div>

                <div class="card-body">
                    <p>Welcome to the admin dashboard!</p>
                    <a href="{{ route('admin.users') }}" class="btn btn-primary">Manage Users</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection