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

<div class="card text-center w-50 shadow-lg mx-auto" style="margin-top: 200px; ">
  <div class="card-header" style="user-select: none;">
  	<span style="display: flex; gap: 37%;">
    <div style="text-align: left; ">🟢 🟡 🔴</div><b style="text-align: center">CHALLENGE 1/2</b>
</span>
  </div>
  <div class="card-body">
    <h5 class="card-title">YOU WON'T GET THIS ONE!</h5>
    <p class="card-text">This time, there are no opportunities for you to employ dishonest tactics!</p>
    <p class="card-text"><b>TASK 1:</b> Find the intruder <a href="./intruder/zenphoto-1.6.2">here</a>.</p>
    <form id="answerForm" action="index.php" method="post">
        <input type="text" id="answerInput" name="f1" placeholder="format: *******" class="form-control w-50 mx-auto" required><br>
        <input type="submit" name="answer" value="Submit" class="btn btn-primary">
    </form>
    <?php
    if (isset($_POST['f1'])) {
        $a = $_POST['f1'];
        $flag = "r3dcr0w";
        if ($a == $flag) {
            echo "<br><p style='color: green ' class='bold-text'>CORRECT, to the 2nd challenge :  <a href='./4n0th3r0ne'>Click here</a></p>";
        } else {
            echo "<br><p style='color: red' class='bold-text'>Wrong answer !</p>";
        }
    }
    ?>
  </div>
  <div class="card-footer text-body-secondary">
    <i>Author note: the CMS (Image Gallery) might be a bit slow, sorry for the inconvenience.</i>
  </div>
</div>
</body>
</html>
