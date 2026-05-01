{{-- GLOBAL TOAST NOTIFICATIONS --}}
<script>
    $(document).ready(function() {
        
        // 1. Handle Success Messages
        @if(session('success'))
            $(document).Toasts('create', {
                class: 'bg-success',
                title: 'Success',
                icon: 'fas fa-check-circle fa-lg',
                autohide: true,
                delay: 4000, // Disappears after 4 seconds
                body: '{{ session('success') }}'
            });
        @endif

        // 2. Handle Validation Errors
        @if($errors->any())
            // Combine all errors into one clean list for the toast body
            let errorHtml = '<ul class="mb-0 pl-3">';
            @foreach($errors->all() as $error)
                errorHtml += '<li>{{ $error }}</li>';
            @endforeach
            errorHtml += '</ul>';

            $(document).Toasts('create', {
                class: 'bg-danger',
                title: 'Validation Error',
                icon: 'fas fa-exclamation-circle fa-lg',
                autohide: true,
                delay: 6000, // Errors stay a bit longer so users can read them
                body: errorHtml
            });
        @endif
        
        // 3. Handle General Error Messages (e.g., Kickbacks from Middleware)
        @if(session('error'))
            $(document).Toasts('create', {
                class: 'bg-danger',
                title: 'Error',
                icon: 'fas fa-times-circle fa-lg',
                autohide: true,
                delay: 5000,
                body: '{{ session('error') }}'
            });
        @endif
        
    });
</script>