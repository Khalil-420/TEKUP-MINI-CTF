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
    <h5 class="card-title">Here's a bonus for you !</h5><br>
    <p class="card-text">Enter the key below :</p>
    <form id="answerForm" action="index.php" method="post">
        <input type="text" id="answerInput" name="f1" placeholder="format: Securinets{*****}" class="form-control w-50 mx-auto" required><br>
        <input type="submit" name="answer" value="Submit" class="btn btn-primary">
    </form>
    <?php
    if (isset($_POST['f1'])) {
        $a = $_POST['f1'];
        $flag = "Securinets{l0s7_inT0_7h3_4by5}";
        if (strtoupper($a) == strtoupper($flag)) {
            echo "<br><p style='color: green ' class='bold-text'>Good job ! <a href='https://youtu.be/dQw4w9WgXcQ?si=j230bzPkFL8R8Srx'>Click here</a></p>";
        } else {
            echo "<br><p style='color: red' class='bold-text'>Wrong answer !</p>";
        }
    }
    ?>
  </div>
  <div class="card-footer text-body-secondary">
    <br>
  </div>
</div>
</body>
</html>
