{{--
  Template Name: Vouchers
--}}

@extends('layouts.app')

@section('content')
    @php
        global $wpdb;
        $table_name = $wpdb->prefix . 'nanoid_codes';

        // Get the username from the query variable
        $username = get_query_var('username');

        if (!$username) {
            echo '<p>No user specified.</p>';
            return;
        }

        // Get the user by username
        $user = get_user_by('login', $username);

        if (!$user) {
            echo '<p>User not found.</p>';
            return;
        }

        // Fetch codes for the specified user
        $codes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC",
            $user->ID
        ));
    @endphp

    <h1>Vouchers for {{ $user->display_name }}</h1>

    @if (empty($codes))
        <p>No codes found for this user.</p>
    @else
        <table class="vouchers-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($codes as $code)
                    <tr>
                        <td>{{ $code->code }}</td>
                        <td>{{ $code->status }}</td>
                        <td>{{ $code->created_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection