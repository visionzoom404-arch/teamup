<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ساخت تیم</title>
</head>
<body>

<h1>ساخت تیم جدید</h1>

<form method="POST" action="/teams">
    @csrf

    <p>
        <label>نام تیم</label><br>
        <input type="text" name="name">
    </p>

    <p>
        <label>شهر</label><br>
        <input type="text" name="city">
    </p>

    <p>
        <label>تاریخ بازی</label><br>
        <input type="date" name="game_date">
    </p>

    <p>
        <label>جنسیت</label><br>
        <select name="gender">
            <option value="male">آقایان</option>
            <option value="female">بانوان</option>
            <option value="mixed">مختلط</option>
        </select>
    </p>

    <button type="submit">ثبت تیم</button>

</form>

</body>
</html>
