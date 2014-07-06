<!DOCTYPE html>
<meta charset="utf-8">
<head>
  <title>NestGraph</title>
  <link rel='stylesheet'  type='text/css' href='nest.css'>
</head>
<body>
<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
<?php 
require 'inc/config.php';
require 'inc/class.db.php';

define('DEFAULT_ID', 1);

$id = DEFAULT_ID; 
if (!empty($_GET["id"])) {
  $id = $_GET["id"];
}

try {
  $db = new DB($config);
  if ($stmt = $db->res->prepare("SELECT id,name from devices")) {
    $stmt->execute();
    $stmt->bind_result($device_id,$device_name);
    $options="";	
    while ($stmt->fetch()) {
	$selected = ($id == $device_id)?"selected":"";
	$options .= " <option value='$device_id' $selected>$device_name</option>";
    }
    $stmt->close();
  }
  $db->close();
} catch (Exception $e) {
  $errors[] = ("DB connection error! <code>" . $e->getMessage() . "</code>.");
}
?>
<select id="device_id">
<?php echo $options;?>
</select>
<input name="hours" type="text" id="hours"/> hours
<script src="main.js" charset="utf-8"></script>
</body>
</html>
