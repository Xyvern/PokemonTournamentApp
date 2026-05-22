@extends('admin.layout')

@section('title', 'Spectating Match')

@section('content')
<link rel="stylesheet" href="{{ asset('unity-game/TemplateData/style.css') }}">

{{-- Added the requested margin wrapper --}}
<div style="margin-left: 10vw; margin-top: 1vh; margin-right: 10vw;">
    <div class="row mb-4">
        {{-- Left Column: Unity WebGL Game --}}
        <div class="col-md-9">
            {{-- Replaced height: 700px with aspect-ratio: 16/9 --}}
            <div id="unity-container" class="bg-dark rounded shadow" style="width: 100%; aspect-ratio: 16/9; position: relative; overflow: hidden;">
                <canvas id="unity-canvas" tabindex="-1" style="background: #231F20; width: 100%; height: 100%;"></canvas>
                
                <div id="unity-loading-bar">
                    <div id="unity-logo"></div>
                    <div id="unity-progress-bar-empty">
                        <div id="unity-progress-bar-full"></div>
                    </div>
                </div>
                <div id="unity-warning"></div>
            </div>
            
        </div>

        {{-- Right Column: Live Chat --}}
        <div class="col-md-3">
            {{-- Replaced fixed height with height: 100% to match the Unity container --}}
            <div class="card shadow-sm border-0 d-flex flex-column" style="height: 100%;">
                <div class="card-header bg-dark text-white font-weight-bold border-0">
                    <i class="fas fa-comments mr-2"></i> Live Chat
                </div>
                
                {{-- Message Container --}}
                <div class="card-body chat-messages flex-grow-1" id="chatMessages" style="overflow-y: auto; background-color: #f8f9fa;">
                    <div class="text-center text-muted small mt-2">Connecting to chat...</div>
                </div>

                {{-- Input Area --}}
                <div class="card-footer bg-white border-top">
                    <form id="chatForm" class="d-flex m-0">
                        <input type="text" id="chatInput" class="form-control form-control-sm mr-2 border-dark" placeholder="Type a message..." autocomplete="off" required>
                        <button type="submit" class="btn btn-sm btn-dark font-weight-bold px-3">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- ========================================== --}}
{{-- 1. UNITY WEBGL INITIALIZATION SCRIPT       --}}
{{-- ========================================== --}}
<script>
    var container = document.querySelector("#unity-container");
    var canvas = document.querySelector("#unity-canvas");
    var loadingBar = document.querySelector("#unity-loading-bar");
    var progressBarFull = document.querySelector("#unity-progress-bar-full");
    var warningBanner = document.querySelector("#unity-warning");

    function unityShowBanner(msg, type) {
        function updateBannerVisibility() {
            warningBanner.style.display = warningBanner.children.length ? 'block' : 'none';
        }
        var div = document.createElement('div');
        div.innerHTML = msg;
        warningBanner.appendChild(div);
        if (type == 'error') div.style = 'background: red; padding: 10px; color: white;';
        else {
            if (type == 'warning') div.style = 'background: yellow; padding: 10px; color: black;';
            setTimeout(function() {
                warningBanner.removeChild(div);
                updateBannerVisibility();
            }, 5000);
        }
        updateBannerVisibility();
    }

    // Hosted Paths using Laravel asset()
    var buildUrl = "{{ asset('unity-game/Build') }}";
    var loaderUrl = buildUrl + "/Build.loader.js";
    
    var config = {
        dataUrl: buildUrl + "/Build.data",
        frameworkUrl: buildUrl + "/Build.framework.js",
        codeUrl: buildUrl + "/Build.wasm",
        streamingAssetsUrl: "{{ asset('unity-game/StreamingAssets') }}",
        companyName: "DefaultCompany",
        productName: "PokemonApp",
        productVersion: "1.0",
        showBanner: unityShowBanner,
    };

    loadingBar.style.display = "block";

    var script = document.createElement("script");
    script.src = loaderUrl;
    script.onload = () => {
        createUnityInstance(canvas, config, (progress) => {
            progressBarFull.style.width = 100 * progress + "%";
        }).then((unityInstance) => {
            loadingBar.style.display = "none";
            
            // Push URL parameters to ApiManager
            var currentUrlParams = window.location.search; 
            unityInstance.SendMessage('ApiManager', 'ReceiveURLParameters', currentUrlParams);
            
        }).catch((message) => {
            alert(message);
        });
    };
    document.body.appendChild(script);
</script>

{{-- ========================================== --}}
{{-- 2. FIREBASE LIVE CHAT SCRIPT               --}}
{{-- ========================================== --}}
<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-app.js";
    // ADDED doc and updateDoc to the imports
    import { getFirestore, collection, addDoc, onSnapshot, query, where, orderBy, serverTimestamp, doc, updateDoc } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-firestore.js";

    const firebaseConfig = {
        apiKey: "{{ config('services.firebase.api_key') }}",
        authDomain: "{{ config('services.firebase.auth_domain') }}",
        projectId: "{{ config('services.firebase.project_id') }}",
        storageBucket: "{{ config('services.firebase.storage_bucket') }}",
        messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
        appId: "{{ config('services.firebase.app_id') }}",
        measurementId: "{{ config('services.firebase.measurement_id') }}"
    };

    const app = initializeApp(firebaseConfig);
    const db = getFirestore(app);

    const matchId = "{{ request()->query('match_id', 'lobby') }}"; 
    const playerName = "{{ auth()->user()->nickname ?? auth()->user()->name }}";
    
    // Blade Check: Is the person looking at this page an Admin?
    const isAdmin = {{ auth()->user()->role == 2 ? 'true' : 'false' }};
    
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.getElementById('chatMessages');

    const chatQuery = query(
        collection(db, "chats"), 
        where("match_id", "==", matchId), 
        orderBy("timestamp", "asc")
    );

    onSnapshot(chatQuery, (snapshot) => {
        chatMessages.innerHTML = ''; 
        
        // Changed loop variable to 'docSnap' to avoid conflict with Firestore's 'doc' function
        snapshot.forEach((docSnap) => {
            const data = docSnap.data();
            
            const isMe = data.player_name === playerName;
            const alignClass = isMe ? 'text-right' : 'text-left';
            const bubbleColor = isMe ? 'bg-primary text-white' : 'bg-white border text-dark';
            
            // Format styling if deleted
            const isDeleted = data.message.includes("deleted by admin") || data.message.includes("deactivated");
            let bubbleStyle = "max-width: 85%; font-size: 0.9rem;";
            if (isDeleted) {
                bubbleStyle += " font-style: italic; color: #dc3545 !important;";
            }
            
            chatMessages.innerHTML += `
                <div class="mb-3 ${alignClass}">
                    <div class="mb-1">
                        <strong class="text-muted" style="font-size: 0.75rem;">${data.player_name}</strong>
                        ${isAdmin && !isDeleted ? `
                            <span class="ml-2">
                                <a href="javascript:void(0)" class="text-secondary delete-btn" data-id="${docSnap.id}" style="font-size: 0.75rem;" title="Delete Message"><i class="fas fa-trash"></i></a>
                                <a href="javascript:void(0)" class="text-danger ml-1 ban-btn" data-player-id="${data.player_id}" data-nickname="${data.player_name}" data-id="${docSnap.id}" style="font-size: 0.75rem;" title="Toggle Ban"><i class="fas fa-ban"></i></a>
                            </span>
                        ` : ''}
                    </div>
                    <div class="${bubbleColor} p-2 rounded d-inline-block shadow-sm text-left" style="${bubbleStyle}">
                        ${data.message}
                    </div>
                </div>
            `;
        });
        
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Attach listeners ONLY if the user is an admin
        if (isAdmin) {
            // Delete Logic
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    if(confirm("Delete this message?")) {
                        await updateDoc(doc(db, "chats", this.dataset.id), { message: "This message was deleted by admin." });
                    }
                });
            });

            // Ban Logic
            document.querySelectorAll('.ban-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const playerId = this.dataset.playerId;
                    const docId = this.dataset.id;
                    const nickname = this.dataset.nickname;

                    if (!playerId) {
                        alert("Cannot ban this user (Missing Database ID in chat log).");
                        return;
                    }

                    if(confirm(`Toggle ban status for ${nickname}?`)) {
                        fetch(`/admin/players/${playerId}/toggle`, {
                            method: "POST", 
                            headers: { 
                                "Content-Type": "application/json", 
                                "X-CSRF-TOKEN": "{{ csrf_token() }}", 
                                "Accept": "application/json" 
                            }
                        }).then(res => res.json()).then(data => {
                            if(data.success) {
                                alert(data.message);
                                updateDoc(doc(db, "chats", docId), { message: `<span style='color: #dc3545; font-style: italic;'>[System: ${data.message}]</span>` });
                            }
                        });
                    }
                });
            });
        }
    });

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const messageText = chatInput.value.trim();
        if (!messageText) return;

        chatInput.value = ''; 
        await addDoc(collection(db, "chats"), {
            match_id: matchId,
            player_name: playerName,
            player_id: "{{ auth()->user()->id }}",
            message: messageText,
            timestamp: serverTimestamp() 
        });
    });
</script>
@endpush