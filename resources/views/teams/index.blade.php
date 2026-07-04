<!DOCTYPE html>
<html>
<head>
    <title>Rooms</title>
</head>

<body style="background:#0f0f0f;color:white;font-family:sans-serif;">

<h1 style="text-align:center;color:#ff0033;margin-top:30px;">
    اتاق‌های فرار
</h1>

<div style="max-width:900px;margin:auto;padding:20px;">

@foreach($teams as $team)

<div style="background:#1e1e1e;margin:15px;padding:20px;border-left:6px solid #ff0033;border-radius:12px;">

    <h2>{{ $team->name ?? 'Escape Room' }}</h2>

    <p>📍 شهر: {{ $team->city ?? '-' }}</p>
    <p>📅 تاریخ: {{ $team->game_date ?? '-' }}</p>
    <p>🎯 سطح: {{ $team->gender ?? '-' }}</p>

</div>

@endforeach

</div>

</body>
</html>
