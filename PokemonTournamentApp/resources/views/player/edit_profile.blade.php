@extends('player.layout')

@section('title', 'Edit Profile')

@section('content')
<div class="container mt-5" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h4 class="mb-0">Edit Profile</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('player.updateProfile') }}" method="POST">
                @csrf
                
                {{-- Nickname Field --}}
                <div class="form-group mb-3">
                    <label for="nickname" class="font-weight-bold">Nickname</label>
                    <input type="text" name="nickname" id="nickname" class="form-control" value="{{ old('nickname', $user->nickname) }}" required>
                </div>

                <hr class="my-4">
                <h6 class="text-muted mb-3">Change Password <small>(Leave blank to keep current password)</small></h6>

                {{-- Password Field --}}
                <div class="form-group mb-3">
                    <label for="password" class="font-weight-bold">New Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password">
                </div>

                {{-- Password Confirmation Field --}}
                <div class="form-group mb-4">
                    <label for="password_confirmation" class="font-weight-bold">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Re-type new password">
                </div>

                {{-- Submit Buttons --}}
                <div class="d-flex justify-content-between">
                    <a href="{{ route('player.profile', $user->id) }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
            
        </div>
    </div>
</div>
@endsection