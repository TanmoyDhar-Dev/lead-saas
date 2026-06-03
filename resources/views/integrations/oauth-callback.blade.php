<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connecting…</title>
    <style>
        body { font-family: Inter, system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #0f172a; color: #e2e8f0; }
        .box { text-align: center; padding: 2rem; }
    </style>
</head>
<body>
    <div class="box">
        <p>{{ $success ? 'Connected! Closing window…' : 'Connection failed. Closing window…' }}</p>
    </div>
    <script>
        (function () {
            var payload = {
                type: 'integration-oauth',
                provider: @json($provider),
                success: @json($success),
                message: @json($message),
            };

            try {
                localStorage.setItem('lead_saas_microsoft_oauth_result', JSON.stringify(payload));
            } catch (e) { /* ignore */ }

            try {
                var channel = new BroadcastChannel('lead-saas-microsoft-oauth');
                channel.postMessage(payload);
                channel.close();
            } catch (e) { /* ignore */ }

            if (window.opener && !window.opener.closed) {
                try {
                    window.opener.postMessage(payload, window.location.origin);
                } catch (e) { /* ignore */ }

                try {
                    window.opener.postMessage(payload, '*');
                } catch (e) { /* ignore */ }
            }

            setTimeout(function () {
                window.close();
                document.body.innerHTML = '<div class="box"><p>You can close this tab and return to the app.</p></div>';
            }, 300);
        })();
    </script>
</body>
</html>
