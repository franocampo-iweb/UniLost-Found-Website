<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$database = "lost_found";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ================= LOGIN ================= */
$login_error = null;

if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $pass  = md5($_POST['password']);

    $check = $conn->query("SELECT * FROM admin 
                           WHERE email='$email' AND password='$pass'");

    if ($check->num_rows > 0) {
        $_SESSION['admin'] = $email;
        header("Location: index.php");
        exit();
    } else {
        $login_error = "Invalid email or password!";
    }
}

/* ================= LOGOUT ================= */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

/* ================= ADD ITEM ================= */
if (isset($_POST['add_item']) && isset($_SESSION['admin'])) {

    $item_name   = $conn->real_escape_string($_POST['item_name']);
    $description = $conn->real_escape_string($_POST['description']);
    $school      = $conn->real_escape_string($_POST['school']);

    $image_name = "default.png";

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ["jpg","jpeg","png","gif","webp"];

        if (in_array($ext,$allowed)) {
            $image_name = uniqid() . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/".$image_name);
        }
    }

    $conn->query("INSERT INTO items (item_name,description,school,image)
                  VALUES ('$item_name','$description','$school','$image_name')");

    header("Location: index.php");
    exit();
}

/* ================= SCHOOL LIST ================= */
$schools = [
    ["Don Bosco Academy","Mabalacat, Pampanga","DBA.php","%Don Bosco%","D"],
    ["Mary Help of Christian","Mabalacat, Pampanga","MaryHelp.php","%Mary Help%","M"],
    ["Holy Angel University","Angeles City","HAU.php","%Holy Angel%","H"],
    ["Angeles University Foundation","Angeles City","AUF.php","%Angeles University%","A"],
    ["Holy Family Angeles","Angeles City","HFA.php","%Holy Family%","F"],
];

/* Count items */
foreach ($schools as $k=>$s) {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM items WHERE school LIKE ?");
    $stmt->bind_param("s",$s[3]);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $schools[$k][] = $res['c'];
}

/* Show admin panel if + clicked */
$showAdmin = isset($_SESSION['admin']) || isset($_GET['admin']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Lost & Found</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="styles.css">

</head>
<body>

<!-- ===== HEADER ===== -->
<header class="topbar">
  <div class="topbar-inner">

    <div class="brand">
        <div class="brand-row">
            <img src="logo.jpg" class="brand-logo" alt="UniLost Logo">
            <div>
                <h1>UniLost & Found</h1>
                <p>Where Campus Items Find Their Way Home.</p>
            </div>
        </div>
    </div>

    <a href="index.php?admin=1" class="fab">
      <i class="fa-solid fa-plus"></i>
    </a>

  </div>
</header>

<div class="page-wrap">

<!-- ===== ADMIN PANEL ===== -->
<?php if($showAdmin): ?>

  <?php if(!isset($_SESSION['admin'])): ?>

  <div class="form-card mb-4">
    <h4>Admin Login</h4>

    <?php if($login_error): ?>
      <div class="alert alert-danger"><?php echo $login_error; ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
      <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
      <button name="login" class="btn btn-warning">Login</button>
    </form>
  </div>

  <?php else: ?>

  <div class="form-card mb-4">
    <div class="d-flex justify-content-between mb-3">
      <h4>Add Lost Item</h4>
      <a href="?logout=1" class="btn btn-danger btn-sm">Logout</a>
    </div>

    <form method="POST" enctype="multipart/form-data">
      <input type="text" name="item_name" class="form-control mb-3" placeholder="Item Name" required>
      <textarea name="description" class="form-control mb-3" placeholder="Description" required></textarea>

      <select name="school" class="form-control mb-3" required>
        <option value="">Select School</option>
        <option>Don Bosco Academy</option>
        <option>Mary Help of Christian Mabalacat</option>
        <option>Holy Angel University</option>
        <option>Angeles University Foundation</option>
        <option>Holy Family Angeles</option>
      </select>

      <input type="file" name="image" class="form-control mb-3">
      <button name="add_item" class="btn btn-warning">Add Item</button>
    </form>
  </div>

  <?php endif; ?>

<?php endif; ?>

<!-- ===== SEARCH ===== -->
<div class="search-wrap">
  <i class="fa fa-search search-icon"></i>
  <input type="text" id="schoolSearch" class="search-input"
         placeholder="Search schools by name or location...">
</div>

<h5 class="section-title">Select a School</h5>

<!-- ===== SCHOOL GRID ===== -->
<div class="school-grid" id="schoolGrid">

<?php foreach($schools as $s): ?>
<a class="school-card schoolItem"
   href="<?php echo $s[2]; ?>"
   data-name="<?php echo strtolower($s[0]); ?>"
   data-address="<?php echo strtolower($s[1]); ?>">

  <div class="school-banner">
    <div class="school-letter"><?php echo $s[4]; ?></div>
  </div>

  <div class="school-body">
    <div class="school-name"><?php echo $s[0]; ?></div>

    <div class="meta-line">
      <i class="fa fa-location-dot"></i>
      <?php echo $s[1]; ?>
    </div>

    <div class="count-line">
      <i class="fa fa-box"></i>
      <?php echo $s[5]; ?> items found
    </div>
  </div>

</a>
<?php endforeach; ?>

</div>
</div>

<!-- ===== SEARCH FILTER ===== -->
<script>
const input = document.getElementById("schoolSearch");
const items = document.querySelectorAll(".schoolItem");

input.addEventListener("input", function(){
  const q = this.value.toLowerCase();

  items.forEach(card=>{
    const name = card.dataset.name;
    const addr = card.dataset.address;

    if(name.includes(q) || addr.includes(q)){
      card.style.display = "block";
    } else {
      card.style.display = "none";
    }
  });
});
</script>

</body>
</html>
