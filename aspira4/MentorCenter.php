<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$sql = "SELECT 
            mentors.mentor_id, 
            mentors.profile_picture, 
            mentors.brief_description,  
            field.field_name, 
            COALESCE(CONCAT(users.first_name, ' ', users.last_name), 'Unknown') AS mentor_name,
            years_of_experience.experience_level AS experience_years
        FROM mentors
        LEFT JOIN users ON mentors.user_id = users.user_id
        JOIN field ON mentors.field_id = field.id
        LEFT JOIN years_of_experience ON mentors.experience_id = years_of_experience.id
        WHERE mentors.status = 'approved'";


$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aspira Mentoring</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
<style>
        	/* Color Variables */
 @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
 * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', sans-serif;
}
:root {
    --primary-bg: #211742;
    --form-bg: #ffffff;
    --input-bg: #e5e5e5;
    --input-border: #b3b3b3;
    --button-color: #4b398e;
    --text-color: #a576ff;
    --highlight-text: #a576ff;
}

/* General Styles */
body {
   
    background-color: #ffffff;
    color: var(--button-color);
    margin: 0;
    padding: 0;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 50px;
    background: #ffffff;
    position: sticky;
    top: 0;
    box-shadow: none !important; /* ✅ تأكد من إزالة أي ظل */
    border-bottom: 0.4px solid rgba(0, 0, 0, 0.05); /* ✅ خط أخف */
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: #a576ff;
}

.logo img {
    width: 50px;
    height: auto;
}

nav ul {
    list-style: none;
    display: flex;
    gap: 20px;
}

nav ul li a {
    color: white;
    text-decoration: none;
}


/* Layout */
.container {
    display: flex;
    width: 90%;
    margin: auto;
    padding: 20px;
}

/* Filter Section (Left Side) */
/* Filter Section */
/* Filter Section */
.filter-section {
    width: 25%;
    padding: 20px;
}

.filter-divider {
    margin: 20px 0;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1); 
}


/* Filter Options */
.filter-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Checkboxes */
.custom-checkbox {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 16px;
    cursor: pointer;
}

.custom-checkbox input {
    display: none;
}

.custom-checkbox .checkmark {
    width: 20px;
    height: 20px;
    border-radius: 5px;
    border: 2px solid var(--button-color);
    display: inline-block;
    margin-right: 10px;
    position: relative;
}

.custom-checkbox input:checked + .checkmark {
    background-color: var(--highlight-text);
}

.custom-checkbox input:checked + .checkmark::after {
    content: '✔';
    position: absolute;
    left: 5px;
    top: -2px;
    color: white;
    font-size: 16px;
}

/* Mentor Count */
.mentor-count {
    color: gray;
    font-weight: normal;
    margin-left: auto;
}

/* Show More & Show Less Text Styling */
.toggle-more {
    color: #444;
    font-size: 14px;
    cursor: pointer;
    margin-top: 10px;
    text-decoration: none;
    display: inline-block;
    font-weight: normal;
}

.toggle-more:hover {
    color: var(--highlight-text);
    text-decoration: none;
}

/* Hide Items Initially */
.hidden {
    display: none;
}

    .custom-checkbox b {
    font-size: 16px; /* Match mentor card text size */
    font-weight: 400; /* Regular weight, not bold */
    color: var(--button-color); /* Ensure it matches the theme */
    
}





/* Mentor Center (Right Side) */
.mentor-center {
    width: 75%;
    padding-left: 20px;
}

.mentor-center h1 {
    color: var(--highlight-text);
    font-size: 24px;
    margin-bottom: 20px;
}

/* Search Input */
#searchInput {
    width: 100%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #4b398e;
    background-color: var(--form-bg);
    font-size: 16px;
    margin-bottom: 20px;
}

/* Mentor Cards */
.mentor-list {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.mentor-card {
    background: var(--form-bg);
    padding: 15px;
    width: 220px;
    border-radius: 8px;
  /*box-shadow: 0 3px 8px rgba(75, 57, 142, 0.5);*/
  
  box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);


    text-align: center;
    transition: transform 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
}

.mentor-card:hover {
    transform: scale(1.05);
    
}

/* Increased the size of mentor images */
.mentor-card img {
    width: 120px;  /* Increased from 100px */
    height: 120px; /* Increased from 100px */
    border-radius: 50%;
    margin-bottom: 15px;
    object-fit: cover;
}

.mentor-card h3 {
    color: var(--button-color);
    font-size: 18px;
    margin-bottom: 5px;
}

.mentor-card p {
   color: var(--button-color);
    font-size: 14px;
    margin-bottom: 10px;
}

.card-footer {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 14px;
    color: var(--button-color);
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color:#a576ff;
}

.logo img {
    width: 24px;
    height: auto;
}

/* Header */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 50px;
    background: #ffffff;
    box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);
}

.logout-btn {
    background: #211742;
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.logout-btn:hover {
    background: #a576ff;
    transform: scale(1.05);
}



    
    

/* Footer */
footer {
    text-align: center;
    padding: 10px;
    background: #ffffff;
    color: #211742;
    margin-top: 100px;
    box-shadow: none !important; /* ✅ إزالة أي ظل */
    border-top: 0.3px solid rgba(0, 0, 0, 0.05); /* ✅ خط خفيف جدًا أعلى الفوتر */
}

footer img {
    margin-right: 10px;
}

.footer-links {
    display: flex;
    justify-content: space-between;
    padding: 0 5%;
    margin-top: 20px;
}

.footer-links div {
    text-align: left;
}

.footer-links ul {
    list-style: none;
    padding: 0;
}

.footer-links ul li {
    margin-bottom: 8px;
}

.footer-links ul li a {
    text-decoration: none;
    color: #211742;
    transition: color 0.3s;
}

.footer-links ul li a:hover {
    color: var(--primary-light);
}

.footer-social {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

.footer-social a {
    color: #211742;
    font-size: 20px;
    transition: color 0.3s;
}

.footer-social a:hover {
    color: var(--primary-light);
}




    </style>
</head>
<body>
    <header>
        <div class="logo">
            <div class="logo">
    <img src="images/logo.png" alt="ASPIRA">
    <span class="logo-text">SPIRA</span>
</div>
 
        </div>
       
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <main class="container">
 <aside class="filter-section">
    <!-- Skills Filter -->
    <h3>Fields</h3>
    <br>
    <div class="filter-options skill-container">
        <?php 
        $fieldQuery = "SELECT field.field_name, COUNT(mentors.mentor_id) AS mentor_count 
                       FROM field 
                       LEFT JOIN mentors ON field.id = mentors.field_id 
                       WHERE mentors.status = 'approved'
                       GROUP BY field.field_name
                       ORDER BY mentor_count DESC";
         
        $fields = $conn->query($fieldQuery);

        $count = 0;
        while ($row = $fields->fetch_assoc()) {
            $count++;
            $hiddenClass = ($count > 5) ? "hidden skill-hidden" : ""; // Show first 5 items, hide rest
            echo "<label class='custom-checkbox $hiddenClass'>";
            echo "<input type='checkbox' class='skill-checkbox' value='" . $row['field_name'] . "'>";
            echo "<span class='checkmark'></span><b>" . $row['field_name'] . "</b>";
            echo "<span class='mentor-count'>" . $row['mentor_count'] . "</span>";
            echo "</label>";
        }
        ?>
    </div>

    <p class="toggle-more" id="showMoreSkills">Show more</p>
    <p class="toggle-more hidden" id="showLessSkills">Show less</p>

    <!-- Space between Filters -->
    <div class="filter-divider"></div>

    <!-- Experience Filter -->
    <h3>Years of Experience</h3>
<br>    
    <div class="filter-options experience-container">
        <?php 
        $experienceQuery = "SELECT years_of_experience.experience_level, COUNT(mentors.mentor_id) AS mentor_count 
                            FROM years_of_experience 
                            LEFT JOIN mentors ON years_of_experience.id = mentors.experience_id 
                            WHERE mentors.status = 'approved'
                            GROUP BY years_of_experience.experience_level 
                             ORDER BY mentor_count DESC";

        $experienceResults = $conn->query($experienceQuery);

        $count = 0;
        while ($row = $experienceResults->fetch_assoc()) {
            $count++;
            $hiddenClass = ($count > 5) ? "hidden experience-hidden" : ""; // Show first 5 items, hide rest
            echo "<label class='custom-checkbox $hiddenClass'>";
            echo "<input type='checkbox' class='experience-checkbox' value='" . $row['experience_level'] . "'>";
            echo "<span class='checkmark'></span><b>" . $row['experience_level'] . "</b>";
            echo "<span class='mentor-count'>" . $row['mentor_count'] . "</span>";
            echo "</label>";
        }
        ?>
    </div>

    <p class="toggle-more" id="showMoreExperience">Show more</p>
    <p class="toggle-more hidden" id="showLessExperience">Show less</p>
</aside>


        <section class="mentor-center">
            <div class="filter-search">
                <input type="text" id="searchInput" placeholder="Search mentor...">
            </div>
            <div class="mentor-list">
<?php 
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<div class='mentor-card' data-category='" . $row['field_name'] . "' data-experience='" . $row['experience_years'] . "'>";
        echo "<img src='" . $row['profile_picture'] . "' alt='Mentor'>";
        echo "<h3>" . $row['mentor_name'] . "</h3>";
        echo "<div class='card-footer'>";
        echo "<span>Field: " . $row['field_name'] . "</span>";
        echo "<span>Experience: " . $row['experience_years'] . "</span>";
        echo "</div>";
        echo "</div>";
    }
} else {
    echo "<p>No mentors available.</p>";
}
?>


            </div>
        </section>
    </main>

   <script>
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("searchInput").addEventListener("keyup", filterMentors);
    document.querySelectorAll(".custom-checkbox input").forEach(checkbox => {
        checkbox.addEventListener("change", filterMentors);
    });

    function filterMentors() {
        let searchInput = document.getElementById("searchInput").value.toLowerCase();
        let checkedSkills = Array.from(document.querySelectorAll(".skill-checkbox:checked")).map(cb => cb.value);
        let checkedExperience = Array.from(document.querySelectorAll(".experience-checkbox:checked")).map(cb => cb.value);
        let mentorCards = document.querySelectorAll(".mentor-card");

        mentorCards.forEach(card => {
            let name = card.querySelector("h3").textContent.toLowerCase();
            let category = card.getAttribute("data-category");
            let experience = card.getAttribute("data-experience");

            let matchesSearch = name.includes(searchInput);
            let matchesSkills = (checkedSkills.length === 0 || checkedSkills.includes(category));
            let matchesExperience = (checkedExperience.length === 0 || checkedExperience.includes(experience));

            if (matchesSearch && matchesSkills && matchesExperience) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
    }

    // Show More/Less Functionality for Skills
    const showMoreSkills = document.getElementById("showMoreSkills");
    const showLessSkills = document.getElementById("showLessSkills");
    let hiddenSkills = document.querySelectorAll(".skill-hidden");

    showMoreSkills.addEventListener("click", function () {
        hiddenSkills.forEach(item => item.classList.remove("hidden"));
        showMoreSkills.classList.add("hidden");
        showLessSkills.classList.remove("hidden");
    });

    showLessSkills.addEventListener("click", function () {
        hiddenSkills.forEach(item => item.classList.add("hidden"));
        showMoreSkills.classList.remove("hidden");
        showLessSkills.classList.add("hidden");
    });

    // Show More/Less Functionality for Experience
    const showMoreExperience = document.getElementById("showMoreExperience");
    const showLessExperience = document.getElementById("showLessExperience");
    let hiddenExperience = document.querySelectorAll(".experience-hidden");

    showMoreExperience.addEventListener("click", function () {
        hiddenExperience.forEach(item => item.classList.remove("hidden"));
        showMoreExperience.classList.add("hidden");
        showLessExperience.classList.remove("hidden");
    });

    showLessExperience.addEventListener("click", function () {
        hiddenExperience.forEach(item => item.classList.add("hidden"));
        showMoreExperience.classList.remove("hidden");
        showLessExperience.classList.add("hidden");
    });
});
</script>

<footer>
       
        <div class="footer-links">
            <div  id="aboutus">
             <h3 style="color:#a576ff">About Us :</h3>
             <p class="aboutp">a mentorship platform designed to connect</p>
             <p class="aboutp">experenced professionals with ambitious individuals</p>
             <p class="aboutp">seeking guidance and growth.Through structured</p> 
             <p class="aboutp">mentorship programs, tailored support,</p>
             <p class="aboutp">and career development resources</p>
             
             
            </div>
            
            <div id="contact" >
          <h3 style="color:#a576ff">Contact Info :</h3>
         
        <p>Email: aspira@gmail.com</p>
         
             <img src="images/twitter.png" class="icon" alt="X" width="30" height="40">
        <img src="images/instagram.png" class="icon" alt="Instagram" width="40" height="40">
     
       

         </div>

        <p id="copy"> &copy; 2025 aspira</p>  
           
        </div>
    </footer>





</body>
</html>

<?php
$conn->close();
?>