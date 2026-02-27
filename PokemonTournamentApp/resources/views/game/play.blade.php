@extends('player.layout')

@extends('player.layout') @section('content')
<div class="container text-center">
    <h2>Loading Match Environment...</h2>
    
    <div id="unity-container" style="width: 960px; height: 600px; background: #222; margin: 0 auto; color: white; display: flex; align-items: center; justify-content: center;">
        <p>Unity WebGL Game Will Embed Here</p>
    </div>

    <div class="mt-4">
        <p class="text-muted">For Unity Editor Testing: Copy the URL above and paste it into your Dev Login Panel.</p>
    </div>
</div>
@endsection