<?php
include("includes/init.php");
$title = "L&S Course Catalog";

//open connection to database
$db = open_sqlite_db('secure/catalog.sqlite');

//create array to tell user if petition to add was successful
$messages = array();

//array of departments to select
$departments = array("AMST", "ASIAN", "BSOC", "CAPS", "CRP", "DSOC", "ECON", "EDUC", "ENG", "FGSS", "GOVT", "HADM", "HD", "HIST", "ILR", "LAW", "LGBT", "NTRES", "PAM", "PHIL", "SOC", "STS", "Other");

//define function to print db records
function print_record($record){
?>
  <tr>
    <td><?php echo htmlspecialchars($record["dept"]); ?></td>
    <td><?php echo htmlspecialchars($record["num"]); ?></td>
    <td><?php echo htmlspecialchars($record["course"]); ?></td>
    <td><?php echo htmlspecialchars($record["grouped"]); ?></td>
    <td><?php echo htmlspecialchars($record["approved"]); ?></td>
  </tr>
<?php
}

//search form
const SEARCH_FIELDS = [
  "all" => "All Courses",
  "dept" => "by Department",
  "num" => "by Course Number",
  "course" => "by Course Title",
  "grouped" => "by L&S Grouping",
  "approved" => "by Status"
];

//security
if (isset($_GET['search'])) {
  $do_search = TRUE;
  $category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING);
    if (in_array($category, array_keys(SEARCH_FIELDS))) {
      $search_field = $category;
    } else {
      array_push($messages, "Invalid search category.");
      $do_search = FALSE;
    }
  $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
  $search = trim($search);
  } else {
  $do_search = FALSE;
  $category = NULL;
  $search = NULL;
}

// form for petition to add course

// Default to no feedback
// id set automatically
$show_dept_feedback = FALSE;
$show_num_feedback = FALSE;
$show_course_feedback = FALSE;
$show_grouped_feedback = FALSE;
// status set automatically

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $valid_add = TRUE;

  // no id, automatic increment
  $dept = filter_input(INPUT_GET, 'dept', FILTER_SANITIZE_STRING);
  $num = filter_input(INPUT_GET, 'num', FILTER_VALIDATE_INT);
  $course = filter_input(INPUT_GET, 'course', FILTER_SANITIZE_STRING);
  $grouped = filter_input(INPUT_GET, 'grouped', FILTER_VALIDATE_INT);
  $approved = "Petition";

  //department must be in array of departments
  if (!$dept || !in_array($dept, $departments)) {
    $valid_add = FALSE;
    $show_dept_feedback = TRUE;
  }

  //course # between 1000-5000
  if (!$num || $num == NULL || $num < 1000 || $num > 5000) {
    $valid_add = FALSE;
    $show_num_feedback = TRUE;
  }

  //course title unique
  $courses = exec_sql_query($db, "SELECT DISTINCT course FROM law_society_catalog", NULL)->fetchAll(PDO::FETCH_COLUMN);
  if (!$course || in_array($course, $courses)) {
    $valid_add = FALSE;
    $show_course_feedback = TRUE;
  }

  //course grouping 1-5
  if (!$grouped || $grouped < 1 || $grouped > 5) {
    $valid_add = FALSE;
    $show_grouped_feedback = TRUE;
  }

  //status is automatically Petition
  if ($approved != "Petition"){
    $valid_add = FALSE;
  }

  // insert valid course petitions into db
  if ($valid_add) {
    $sql = "INSERT INTO law_society_catalog (id, dept, num, course, grouped, approved) VALUES (:id, :dept, :num, :course, :grouped, :approved)";
    $params = array(
      ':id' => $id,
      ':dept' => $dept,
      ':num' => $num,
      ':course' => $course,
      ':grouped' => $grouped,
      ':approved' => $approved
    );

    $result = exec_sql_query($db, $sql, $params);

    if ($result) {
      array_push($messages, "Your course petition has been recorded. Thank you!");
    } else {
      array_push($messages, "Failed to add course petition.");
    }

  } else {
    array_push($messages, "Failed to add course petition. Invalid input.");
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include("includes/head.php"); ?>

<body class='border'>
  <?php include("includes/header.php"); ?>
  <h2><?php echo $title; ?></h2>
  <main class='border'>

  <form id="SearchForm" action="catalog.php" method="get" novalidate>
    <div>
    <label for="search">Search: </label>
    <select name="category">
      <?php foreach (SEARCH_FIELDS as $field_name => $label) { ?>
        <option value="<?php echo $field_name; ?>"><?php echo $label; ?></option>
      <?php } ?>
    </select>
    <input type="text" id ="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" required />
      <button type="submit">Go</button>
    </div>
    </form>

    <?php
    if ($do_search) {?>
      <div id='search' class='border'>
      <?php
      if ($search==NULL){?>
        <h3 id='results'>Search <?php echo SEARCH_FIELDS[$search_field]?>:</h3>
      <?php }
      else {?>
        <h3 id='results'>Search <?php echo SEARCH_FIELDS[$search_field]?> for "<?php echo htmlspecialchars($search);?>":</h3>
      <?php }

      if ($search_field == "all") {
        $sql = "SELECT * FROM law_society_catalog WHERE (id LIKE '%' || :search || '%' OR dept LIKE '%' || :search || '%' OR course LIKE '%' || :search || '%' OR grouped LIKE '%' || :search || '%' OR approved LIKE '%' || :search || '%')";
        $params = array(
          ':search' => $search
        );
      } else { ?>
        <?php $sql = "SELECT * FROM law_society_catalog WHERE (" .$search_field. " LIKE '%' || :search || '%')";
        $params = array(
          ':search' => $search
        );
      }
    }
    else {
      ?>
      <div id='catalog' class='border'>
      <?php
      $sql = "SELECT * FROM law_society_catalog";
      $params = array();
    }

    $result = exec_sql_query($db, $sql, $params);
    if ($result) {
      $records = $result->fetchAll();

      //create catalog columns
      if (count($records) > 0) {
        ?>
        <table>
          <tr>
            <th>Department(s)</th>
            <th>Course #</th>
            <th>Course Title</th>
            <th>L&S Grouping</th>
            <th>Status</th>
          </tr>

        <?php
        //print catalog results
        foreach($records as $record) {
            print_record($record);
          }
        ?>
        </table>
      </div>
    <?php
      } else {
        echo "<p><em>No matching courses found.</em></p>";
      }
    }
    ?>
    </div>

    <div id='add' class='border'>
      <h3 id='results'>Petition to Add a Course to the L&S Catalog:</h3>
      <p><em>We review petitions on a monthly basis and update their status and course cross-listings should they be approved. Thank you for your suggestions!</em></p>

      <form id="AddForm" action="catalog.php" method="get" novalidate>

      <div class="item">
          <label for="dept">Department:</label>
          <select name="dept" id="dept">
            <option value="Select" selected disabled>Select Department...</option>
            <?php
            foreach ($departments as $dept) {
              echo "<option value=\"" . htmlspecialchars($dept) . "\">" . htmlspecialchars($dept) . "</option>";
            }
            ?>
          </select>
          <p class="form_feedback <?php echo ($show_dept_feedback) ? '' : 'hidden'; ?>">Please select a valid department.</p>
      </div>

      <div class="item">
          <label for="num">Course #:</label>
          <input id="num" name="num" required />
          <p class="form_feedback <?php echo ($show_num_feedback) ? '' : 'hidden'; ?>">Please provide a valid undergraduate course number.</p>
      </div>

      <div class="item">
          <label for="course">Course Title:</label>
          <input id="course" name="course" required />
          <p class="form_feedback <?php echo ($show_course_feedback) ? '' : 'hidden'; ?>">Please provide a course title not yet listed.</p>
      </div>

      <div class="item">
        <label for="grouped">Suggested Group:</label>
            <select name="grouped" id="grouped">
                <option value="" selected disabled>Select Group...</option>
                <option value=1>1: Legal Institutions</option>
                <option value=2>2: Law and Policy</option>
                <option value=3>3: Law and Social Structure</option>
                <option value=4>4: Law and Culture</option>
                <option value=5>5: Law and Ethics</option>
            </select>
            <p class="form_feedback <?php echo ($show_grouped_feedback) ? '' : 'hidden'; ?>">Please select an L&S grouping category.</p>
      </div>

      <div>
          <button type="submit" class="submit">Submit</button>
      </div>
      </form>
      <?php
        // print whether add was success or failure
        foreach ($messages as $message) {
          echo "<p><em>" . htmlspecialchars($message) . "</em></p>\n";
        }
      ?>
    </div>

  </main>
  <?php include("includes/footer.php"); ?>
</body>

</html>
