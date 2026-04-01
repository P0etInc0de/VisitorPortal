<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 50px; }
        .name { font-size: 30px; font-weight: bold; }
    </style>
</head>
<body>
<div class="name">
    {{ $visitor->first_name }} {{ $visitor->last_name }}
</div>
</body>
</html>
