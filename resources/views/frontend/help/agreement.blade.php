
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="initial-scale=1, maximum-scale=1">
<title>Wyth MEMBERSHIP AGREEMENT</title>
<link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700,900" rel="stylesheet">
</head>
<style>
body {
    font-family: 'Roboto', sans-serif;
    margin: 0;
    counter-reset: section;
}
* {
    margin: 0;
    padding: 0;
    text-decoration: none;
    border: none;
    box-sizing: border-box;
}
u {
    text-decoration:underline;
}
strong {
    font-weight: 500;
    font-size: 15px;
}

.wrap {
    margin: 40px 20px;
}
h1 {
    font-size: 20px;
    margin: 0 0 20px 0;
    text-transform: uppercase;
    color: #393939;
    font-weight: 500;
}
h3 {
    font-size: 17px;
    margin: 30px 0 15px 0;
    color: #393939;
    font-weight: 500;
}
p {
    font-size: 14px;
    margin: 0 0 10px 0;
    line-height: 22px;
    color: #393939;
}

ul {
    font-size: 14px;
    margin: 0 0 25px 26px;
    line-height: 22px;
}

a {
    color: #393939;
    font-weight: bold;
}
ul {
    position: relative;
    margin: 30px 0 0 0;
    padding: 0 0 0 15px;
}

ul li {
    position: relative;
    margin-bottom: 25px;
    padding-left: 0;
    list-style: circle;
    color: #393939;
}

ul ul {
    margin: 20px 20px 20px 0;
    padding: 0 0 0 10px;
}

ul ul li {
    padding: 0;
    margin-bottom: 8px;
    position: relative;
    list-style: circle;
}

</style>

<body>
<div class="wrap">
  {!! $content !!}
</div>
</body>
</html>
