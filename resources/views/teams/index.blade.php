<h1>لیست تیم‌ها</h1>

@foreach($teams as $team)
    <div>
        <h3>{{ $team->title }}</h3>
        <p>{{ $team->city }}</p>
        <p>{{ $team->game_date }}</p>
    </div>
@endforeach
