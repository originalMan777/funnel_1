<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $subject }}</title>
</head>
<body>
    @if (filled($previewText))
        <div style="display:none;max-height:0;overflow:hidden;">{{ $previewText }}</div>
    @endif

    @if (filled($headline))
        <h1>{{ $headline }}</h1>
    @endif

    @if (filled($htmlBody))
        {!! $htmlBody !!}
    @else
        @foreach ($lines as $line)
            <p>{{ $line }}</p>
        @endforeach
    @endif

    @if (filled($textBody))
        <pre style="white-space:pre-wrap;">{{ $textBody }}</pre>
    @endif
</body>
</html>
