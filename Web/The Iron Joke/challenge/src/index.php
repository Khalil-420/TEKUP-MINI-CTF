<html>
        <head>
                <title>The Iron Joke</title>
        </head>
        <style>
        * {
            margin: 0;
            padding: 0;
        }

        .background-image {
            background-image: url("./main.jpg");
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
            background-size: cover;
            height: 100vh;
            width: 100%;
        }
        .transparent-h1 {
        background-color: rgba(255, 255, 255, 0.4); /* White color with 50% opacity */
         color: white;
          text-align: center;
        }
        
        .center {
	
	  margin: auto;
	  width: 50%;
	  padding: 10px;
	}
		
	a.button {
	    display: inline-block;
	    padding: 10px 20px;
	    font-size: 16px;
	    text-align: center;
	    text-decoration: none;
	    color: #fff;
	    background-color: #007BFF;
	    border-radius: 5px;
	     margin: 120px 30% 150px;
	    border: none;
	    transition: all 0.5s;
	}

	a.button:hover {
	    background-color: #0056b3;
	}

        
        
        </style>
        <body  class="background-image" >
                <h1 class="transparent-h1" > Hamas will never be able to break the Iron Dome    &#128520;       &#128520;       &#128520;</h1>
<div class="center">

    <?php
    $f='blocked.txt';
    echo "<a class=\"button\" href=\".?file=$f\" /> <h2> Bypass the Iron Dome !</h2> </a><h1 class=\"transparent-h1\">";
    if (isset($_GET['file'])) {
       if($file=$_GET['file']){
         $file=str_replace("../","",$file);
        if($file!="../index.php"){
            include('files/'.$file);
        }
    }
    }
    ?>
    
    <!-- hint: Find the flag.txt in the root directory -->
    

</div>
<script type="text/javascript" src="static/js/bootstrap.min.js"></script>
</body>
</html>

