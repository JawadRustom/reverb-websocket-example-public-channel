<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reverb Listener</title>
    @vite('resources/js/app.js') {{-- contains Echo setup; see below --}}
</head>

<body class="font-sans antialiased">
<h1 id="status">Waiting for messages…</h1>
<ul id="messages"></ul>

<script>
    setTimeout(() => {
        Echo.channel('public.chat')
            .listen('MessageSent', e => {
                document.getElementById('status').textContent = 'Real-time connected ✔';
                document.getElementById('messages').insertAdjacentHTML(
                    'beforeend',
                    `<li><strong>${e.user.name}</strong>: <br>${e.body} <br><em>${e.time}</em></li>`
                );
                console.log('Event received', e);
            });

    }, 500);
</script>
</body>

</html>
