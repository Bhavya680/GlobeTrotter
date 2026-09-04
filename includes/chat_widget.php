<?php
// Extract session context securely
$bot_context = [
    'user_id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
    'user_name' => isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Guest',
    'user_role' => isset($_SESSION['role']) ? $_SESSION['role'] : 'guest',
    'current_screen' => basename($_SERVER['PHP_SELF'], '.php'),
    'active_trip_id' => isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : null),
    'session_cookie' => session_id()
];
?>
<script>
    window.GLOBE_BOT_CONTEXT = <?= json_encode($bot_context) ?>;
</script>

<style>
    /* GlobeBot Widget Styles */
    :root {
        --gt-primary: #0d6efd;
        --gt-light: #f8f9fa;
    }
    
    #globebot-fab {
        position: fixed;
        bottom: 20px;
        right: 100px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: var(--gt-primary);
        color: white;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        cursor: pointer;
        z-index: 1050;
        transition: transform 0.2s;
    }
    #globebot-fab:hover {
        transform: scale(1.1);
    }
    
    #globebot-window {
        position: fixed;
        bottom: 90px;
        right: 20px;
        width: 350px;
        height: 500px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        z-index: 1050;
        overflow: hidden;
        border: 1px solid #ddd;
    }
    
    #globebot-header {
        background-color: var(--gt-primary);
        color: white;
        padding: 15px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    #globebot-close {
        cursor: pointer;
        font-size: 18px;
    }
    
    #globebot-messages {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background-color: var(--gt-light);
    }
    
    .bot-msg, .user-msg {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 16px;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .bot-msg {
        background-color: #e9ecef;
        align-self: flex-start;
        border-bottom-left-radius: 2px;
    }
    
    .user-msg {
        background-color: var(--gt-primary);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 2px;
    }
    
    #globebot-input-area {
        padding: 10px;
        background: white;
        border-top: 1px solid #ddd;
        display: flex;
        gap: 10px;
    }
    
    #globebot-input {
        flex: 1;
        border: 1px solid #ccc;
        border-radius: 20px;
        padding: 8px 15px;
        outline: none;
    }
    
    #globebot-send {
        background-color: var(--gt-primary);
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .globebot-loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(0,0,0,.1);
        border-radius: 50%;
        border-top-color: var(--gt-primary);
        animation: spin 1s ease-in-out infinite;
        align-self: flex-start;
        margin-left: 10px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<div id="globebot-fab" onclick="toggleGlobeBot()">💬</div>

<div id="globebot-window">
    <div id="globebot-header">
        <span>GlobeTrotter Assistant</span>
        <span id="globebot-close" onclick="toggleGlobeBot()">✖</span>
    </div>
    <div id="globebot-messages">
        <!-- Messages will appear here -->
    </div>
    <div id="globebot-input-area">
        <input type="text" id="globebot-input" placeholder="Ask me anything..." onkeypress="handleBotKeyPress(event)">
        <button id="globebot-send" onclick="sendBotMessage()">➤</button>
    </div>
</div>

<script>
    const API_URL = '/api/chat_proxy.php';
    let isBotInitialized = false;

    function toggleGlobeBot() {
        const win = document.getElementById('globebot-window');
        if (win.style.display === 'none' || win.style.display === '') {
            win.style.display = 'flex';
            if (!isBotInitialized) {
                initGlobeBotSession();
            }
        } else {
            win.style.display = 'none';
        }
    }

    function addMessageToDOM(text, sender) {
        const messagesDiv = document.getElementById('globebot-messages');
        const msgDiv = document.createElement('div');
        msgDiv.className = sender === 'user' ? 'user-msg' : 'bot-msg';
        msgDiv.innerHTML = text.replace(/\n/g, '<br>'); // Simple markdown-like line break
        messagesDiv.appendChild(msgDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function addLoadingIndicator() {
        const messagesDiv = document.getElementById('globebot-messages');
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'globebot-loading';
        loadingDiv.id = 'globebot-loading';
        messagesDiv.appendChild(loadingDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function removeLoadingIndicator() {
        const loadingDiv = document.getElementById('globebot-loading');
        if (loadingDiv) loadingDiv.remove();
    }

    async function initGlobeBotSession() {
        isBotInitialized = true;
        // Don't show typing indicator for the silent init
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    appName: "globetrotter_agent",
                    userId: window.GLOBE_BOT_CONTEXT.user_id ? String(window.GLOBE_BOT_CONTEXT.user_id) : "guest",
                    sessionId: window.GLOBE_BOT_CONTEXT.session_cookie || "default_session",
                    newMessage: {
                        role: "user",
                        parts: [{ text: "SYSTEM_INIT" }]
                    },
                    stateDelta: window.GLOBE_BOT_CONTEXT
                })
            });
            const data = await response.json();
            let botText = "";
            if (Array.isArray(data)) {
                for (const event of data) {
                    if (event.content && event.content.parts) {
                        for (const part of event.content.parts) {
                            if (part.text) botText += part.text + "\n";
                        }
                    }
                }
            }
            if (botText) {
                addMessageToDOM(botText.trim(), 'bot');
            } else {
                addMessageToDOM("Hello! I am your GlobeTrotter AI assistant. How can I help you plan your trip today?", 'bot');
            }
        } catch (error) {
            console.error('GlobeBot Init Error:', error);
            addMessageToDOM("Hi! (Offline Mode - Ensure ADK server is running on port 8080)", 'bot');
        }
    }

    async function sendBotMessage() {
        const inputField = document.getElementById('globebot-input');
        const message = inputField.value.trim();
        if (!message) return;

        // 1. Add user message to UI
        addMessageToDOM(message, 'user');
        inputField.value = '';

        // 2. Show loading
        addLoadingIndicator();

        // 3. Fetch from API
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    appName: "globetrotter_agent",
                    userId: window.GLOBE_BOT_CONTEXT.user_id ? String(window.GLOBE_BOT_CONTEXT.user_id) : "guest",
                    sessionId: window.GLOBE_BOT_CONTEXT.session_cookie || "default_session",
                    newMessage: {
                        role: "user",
                        parts: [{ text: message }]
                    },
                    stateDelta: window.GLOBE_BOT_CONTEXT
                })
            });

            if (!response.ok) {
                throw new Error(`Server returned ${response.status}`);
            }

            const data = await response.json();
            removeLoadingIndicator();
            
            let botText = "";
            if (Array.isArray(data)) {
                for (const event of data) {
                    if (event.content && event.content.parts) {
                        for (const part of event.content.parts) {
                            if (part.text) botText += part.text + "\n";
                        }
                    }
                }
            }
            if (botText) {
                addMessageToDOM(botText.trim(), 'bot');
            } else {
                addMessageToDOM("Sorry, I didn't get a valid response.", 'bot');
            }
        } catch (error) {
            console.error('GlobeBot Error:', error);
            removeLoadingIndicator();
            addMessageToDOM("Error connecting to the AI assistant. Please check if the ADK server is running.", 'bot');
        }
    }

    function handleBotKeyPress(event) {
        if (event.key === 'Enter') {
            sendBotMessage();
        }
    }
</script>
