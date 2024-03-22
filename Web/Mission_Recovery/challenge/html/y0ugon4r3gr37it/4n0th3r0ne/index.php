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

<div class="card text-center w-50 shadow-lg mx-auto" style="margin-top: 150px; ">
  <div class="card-header" style="user-select: none;">
  	<span style="display: flex; gap: 37%;">
    <div style="text-align: left;">🟢 🟡 🔴</div><b style="text-align: center">CHALLENGE 2/2</b>
</span>
  </div>
  <div class="card-body">
    <h5 class="card-title">Congrats on passing the 1st challenge!</h5>
    <p class="card-text">It wasn't easy like the one before right? don't get reassured there is more to come!</p>
    <p class="card-text"><b>TASK 2:</b> I dance upon the air, swift and free,
My breath ignites the flames, for all to see.
I shape the mountains and valleys below,
And in the rivers and oceans, my currents flow.
What am I, embodying these four, you see?</p>
    <form id="answerForm" action="index.php" method="post">
        <div class="row">
            <div class="col">
        <input type="text" id="answerInput" name="f1" placeholder="format: wi********" class="form-control w-60 mx-auto" required><br>
    </div>
    <div class="col">
        <input type="text" id="answerInput" name="f2" placeholder="format: e*********" class="form-control w-60 mx-auto" required><br>
    </div>
</div>
<div class="row">
            <div class="col">
        <input type="text" id="answerInput" name="f3" placeholder="format: f*********" class="form-control w-60 mx-auto" required><br>
    </div>
    <div class="col">
        <input type="text" id="answerInput" name="f4" placeholder="format: w**********" class="form-control w-60 mx-auto" required><br>
    </div>
</div>
<div class="row">
    <b style="text-align: center;">PIN Code : </b>
</div>
<div class="row">
    <div class="col"></div>
    <div class="col">
        <input type="text" id="answerInput" name="f5" placeholder="format: ****" class="form-control w-60 mx-auto" required>
    </div>
    <div class="col"></div>
</div>
<br>
        <input type="submit" name="answer" value="Submit" class="btn btn-primary">
    </form>

    <?php
    if (isset($_POST['f1'])) {
        $a = $_POST['f1'];
        $b = $_POST['f2'];
        $c = $_POST['f3'];
        $d = $_POST['f4'];
        $e = $_POST['f5'];
        if ($a == "wind" && $b == "earth" && $c == "fire" && $d == "water" && !($e == "2013")){
            echo "<br><p style='color: red' class='bold-text'>Wrong PIN code !</p>";
            echo'<!-- Button trigger modal -->
    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#exampleModal">
      Need a hint?
    </button>

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="exampleModalLabel">GOT YOU!</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            HAHA.. You really thought I would help you? You know, I should have sold your files on the dark web, I might do actually. Speaking of dark web, I really miss Silk Road, I still remember the year they took it down and how tragic it was.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
';

        } elseif ($a == "wind" && $b == "earth" && $c == "fire" && $d == "water" && $e == "2013"){
            echo "<br><p style='color: green ' class='bold-text'>IMPRESSIVE! You deserve this one : <a href='./nxba7ldkn0/sec3.txt'>Click here</a> </p>";
        }
        else{
            echo "<br><p style='color: red' class='bold-text'>Wrong answers !</p>";
        }
    }
    ?>
  </div>
  <div class="card-footer text-body-secondary">
    <i>Author note: the asterisks (*) doesn't match the right words but does for the PIN.</i>
  </div>
</div>
</body>
</html>
