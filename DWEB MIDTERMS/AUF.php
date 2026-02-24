<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$database = "lost_found";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

function e($v){ return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }

/* =========================
   EDIT THESE 3 VALUES ONLY
   ========================= */
$pageTitle   = "Angeles University Foundation";
$pageAddress = "Angeles City";
$schoolLike  = "%Angeles University%";

$search = trim($_GET["search"] ?? "");
$cat    = $_GET["cat"] ?? "all"; // all | electronics | personal

$electronicsKeywords = ["laptop","phone","tablet","charger","earbuds","headset","camera","powerbank","usb","mouse","keyboard","airpods"];
$personalKeywords    = ["bottle","wallet","id","card","bag","umbrella","jacket","keys","cap","notebook","pouch"];

/* ===== Build query safely ===== */
$conds = ["school LIKE ?"];
$params = [$schoolLike];
$types = "s";

if ($search !== "") {
  $conds[] = "(item_name LIKE ? OR description LIKE ?)";
  $sLike = "%{$search}%";
  $params[] = $sLike; $params[] = $sLike;
  $types .= "ss";
}

if ($cat === "electronics") {
  $sub = [];
  foreach($electronicsKeywords as $kw){
    $sub[] = "(item_name LIKE ? OR description LIKE ?)";
    $kLike = "%{$kw}%";
    $params[] = $kLike; $params[] = $kLike;
    $types .= "ss";
  }
  $conds[] = "(" . implode(" OR ", $sub) . ")";
}

if ($cat === "personal") {
  $sub = [];
  foreach($personalKeywords as $kw){
    $sub[] = "(item_name LIKE ? OR description LIKE ?)";
    $kLike = "%{$kw}%";
    $params[] = $kLike; $params[] = $kLike;
    $types .= "ss";
  }
  $conds[] = "(" . implode(" OR ", $sub) . ")";
}

$where = implode(" AND ", $conds);

$sql = "SELECT id, item_name, description, school, image, created_at
        FROM items
        WHERE $where
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$countSql = "SELECT COUNT(*) c FROM items WHERE $where";
$cstmt = $conn->prepare($countSql);
$cstmt->bind_param($types, ...$params);
$cstmt->execute();
$total = (int)($cstmt->get_result()->fetch_assoc()["c"] ?? 0);

$stmt->close();
$cstmt->close();

function guessTag($name, $desc){
  $text = strtolower(($name ?? "")." ".($desc ?? ""));
  $electronics = ["laptop","phone","tablet","charger","earbuds","headset","camera","powerbank","usb","mouse","keyboard","airpods"];
  foreach($electronics as $kw){
    if (strpos($text, $kw) !== false) return ["Electronics","tag"];
  }
  return ["Personal Items","tag personal"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo e($pageTitle); ?> - Lost Items</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>

<body>

<header class="topbar">
  <div class="topbar-inner">
    <div class="leftHead">
      <a class="backBtn" href="index.php" title="Back">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <div class="brand">
        <h1 style="margin:0;"><?php echo e($pageTitle); ?></h1>
        <p style="margin:4px 0 0;"><?php echo e($pageAddress); ?></p>
      </div>
    </div>

    <a class="fab" href="index.php?admin=1" title="Admin / Add Item">
      <i class="fa-solid fa-plus"></i>
    </a>
  </div>
</header>

<div class="page-wrap">

  <!-- Search -->
  <form class="search-wrap" method="GET">
    <i class="fa-solid fa-magnifying-glass search-icon"></i>
    <input class="search-input" type="text" name="search"
           value="<?php echo e($search); ?>" placeholder="Search items...">
    <input type="hidden" name="cat" value="<?php echo e($cat); ?>">
  </form>

  <h5 class="section-title">Lost Items (<?php echo $total; ?>)</h5>

  <?php if ($total === 0): ?>
    <div class="empty">No items found.</div>
  <?php else: ?>
    <div class="item-grid">
      <?php while($row = $result->fetch_assoc()): ?>
        <?php
          $img = $row["image"] ?: "default.png";
          $tag = guessTag($row["item_name"], $row["description"]);
        ?>
        <div class="item-card">
          <img class="item-img" src="uploads/<?php echo e($img); ?>" alt="Item">

          <div class="item-body">
            <div class="item-top">
              <h3 class="item-name"><?php echo e($row["item_name"]); ?></h3>
              <span class="<?php echo e($tag[1]); ?>"><?php echo e($tag[0]); ?></span>
            </div>

            <p class="item-desc"><?php echo e($row["description"]); ?></p>

            <div class="meta">
              <i class="fa-solid fa-location-dot"></i>
              <span>Found at: Campus</span>
            </div>

            <div class="meta">
              <i class="fa-regular fa-calendar"></i>
              <span>Date: <?php echo e(date("M d, Y", strtotime($row["created_at"]))); ?></span>
            </div>

            <div class="divider"></div>
            <span class="status"><i class="fa-solid fa-tag"></i> Lost</span>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
