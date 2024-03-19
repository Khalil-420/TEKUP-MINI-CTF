<?php
session_start();

if(!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

require_once 'db_connection.php';

if(isset($_POST['send_message'])) {
  $content = $_POST['content'];
  $user_id = $_SESSION['user_id'];

  $req = $pdo->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
  $req->execute([$user_id, $content]);
}

$user_id = $_SESSION['user_id'];
$req = $pdo->prepare("SELECT * FROM messages WHERE user_id = ?");
$req->execute([$user_id]);
$messages = $req->fetchAll();

if(isset($_POST['logout'])) {
  session_unset();
  session_destroy();
  header("Location: index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notes Keeper - Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <style>
    .note-form {
      background-color: #f5f5f5;
      padding: 20px;
      border-radius: 5px;
    }
    .note-content {
      min-height: 100px;
    }
    .notes-table {
      background-color: #fff;
      border-radius: 5px;
    }
  </style>
</head>
<body class="bg-dark">
    <!-- Hey Sam, if you're reading this, I've left for you a way into my notes that you need to read, you can check "view notes", I know you can find your way from there!-->
  <div class="container-fluid d-flex flex-column min-vh-100 justify-content-center align-items-center">
    <div class="card shadow border-0 w-75">
      <div class="card-header bg-primary text-light">
        <h2 style="text-align: center">Welcome, <b><?php echo ucfirst($_SESSION['username']); ?></b>!</h2>
      </div>
      <div class="card-body">

        <h3>Stick a Note</h3>
        <form action="home.php" method="post" class="note-form mb-3">
          <div class="mb-3">
            <label for="noteContent" class="form-label">Content:</label>
            <textarea class="form-control note-content" id="noteContent" name="content" required></textarea>
          </div>
          <button type="submit" name="send_message" class="btn btn-primary">Stick</button>
        </form>

        <h3>Your Notes</h3>
        <table class="table table-striped table-bordered notes-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Content</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($messages as $message): ?>
              <tr>
                <td><?php echo $message['id']; ?></td>
                <td><?php echo $message['content']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="d-flex justify-content-between mt-4">
          <form action="home.php" method="post">
            <button type="submit" name="logout" class="btn btn-secondary">Logout</button>
          </form>
          <a href="notes.php?id=34" class="btn btn-info">View Notes</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
