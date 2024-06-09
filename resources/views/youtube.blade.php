<h1>Youtube Demo</h1>
<p>
    <strong>Status:</strong>
    @if($connected)
        Authorized. <a href='?disconnect'>Disconnect</a>
    @else
        Not authorized.
        <a href='{{ $authUrl }}'>Authorize with YouTube...</a>
    @endif
</p>
