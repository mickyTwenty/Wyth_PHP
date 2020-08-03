<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ ucfirst($userType) }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#59ba49" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>    
    $(document).ready(function(e) {
        $(".panel-title").click(function(){
            if($(this).hasClass("active")) {
                $(this).removeClass("active");
            } else {
                $(".panel-title.active").removeClass("active")
                $(this).addClass("active");
            }
            
            if ($(this).hasClass('active')) {
                window.location.hash = $(this).find('a').attr('href');
            }else{
                removeHash()
            }
        })
            
    });
    
    $(document).ready(function () {
      if ( location.hash ) {
          $(location.hash + '.collapse').collapse('show');
          $(document).find('a[href="'+location.hash+'"]').parent().addClass('active');
      }
    });
    
    function removeHash () { 
        history.pushState("", document.title, window.location.pathname + window.location.search);
    }
</script>
</head>
<style>

.panel-group {
	margin-top:20px;
}

h2 {
	margin-bottom: 20px;
	font-size: 20px;
	float: left;
}
h3 {
	font-size: 15px;
	margin-top: 25px;
	font-weight: bold;
	margin-bottom: 0;
}
.panel-default>.panel-heading {
	background: #59ba49;
	padding: 0;
}
.panel-title a {
	color: #fff;
	width: 100%;
	display: inline-block;
	padding: 10px 15px;
	text-decoration: none !important;
}
.active {
	background: #393939;
}
.panel-title {
	position: relative;
}
.panel-title::after {
	content: "";
	position: absolute;
	right: 15px;
	top: 37%;
	border: solid #fff;
	border-width: 0 2px 2px 0;
	display: inline-block;
	padding: 3px;
	transform: rotate(-45deg);
	-webkit-transform: rotate(-45deg);
}
.panel-title.active::after {
	transform: rotate(45deg);
	-webkit-transform: rotate(45deg);
}

</style>

<body>
<div class="container">
  <div class="panel-group" id="accordion">
    @foreach ($faqs as $faq)
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"> <a data-toggle="collapse" data-parent="#accordion" href="#{{ str_slug($faq->title) }}">{{ $faq->title }}</a> </h4>
        </div>
        <div id="{{ str_slug($faq->title) }}" class="panel-collapse collapse">
          <div class="panel-body">
            {!! $faq->content !!}
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
</body>
</html>
