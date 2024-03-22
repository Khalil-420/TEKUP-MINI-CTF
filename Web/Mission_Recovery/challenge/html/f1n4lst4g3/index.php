<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission: Recovery</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@400;500;600;700&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        body {
            font-family: 'Fira Sans', sans-serif;
            background-color: grey;
        }
        .bold-text {
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="card text-center w-50 shadow-lg mx-auto" style="margin-top: 200px;">
  <div class="card-header">
    <div style="text-align: left; user-select: none;">🟢 🟡 🔴</div>
  </div>
  <div class="card-body">
    <h5 class="card-title">The Last Dance!</h5>
    <p class="card-text">Let's see how clever you are! can you figure out the passphrase?</p>
    <form id="answerForm" action="index.php" method="post">
        <input type="text" id="answerInput" name="f1" class="form-control w-50 mx-auto" required><br>
        <input type="submit" name="answer" value="Submit" class="btn btn-primary">
    </form>
   <?php
session_start();

if (!isset($_SESSION['ch'])) {
    $ch = '';
    echo "<br>";
    for ($i = 0; $i < 30; $i++) {
        $tmp = rand(97, 122);
        echo $tmp." ";
        $ch .= chr($tmp);
    }
    $_SESSION['ch'] = $ch;
} else {
    $ch = $_SESSION['ch'];
    $n= "";
    for ($j = 0; $j < strlen($ch); $j++) {
        $n .=ord($ch[$j]) . " ";
    }
}

if (isset($_POST['f1'])) {
    $a = $_POST['f1'];
    $tmp2 = $ch;

    if ($a == $tmp2) {
        echo "<br><p style='color: green' class='bold-text'>How did you.. fine here is the last piece : <a href='./okwn29qlwm/sec4.txt'>Click here</a></p>";
    } else {
        echo "<br><p style='color: red' class='bold-text'>Wrong answer!</p>";
    }
}    
echo "<br>".$n;
?>

  <div class="card-footer text-body-secondary">
    <br>
  </div>
</div>
</body>
</html>
