<?php

include "db.php";


$result=mysqli_query($conn,

"SELECT * FROM disaster_incidents ORDER BY created_at DESC");


?>


<html>

<head>

<title>Disaster Dashboard</title>

<link rel="stylesheet" href="css/style.css">

</head>


<body>


<nav>

<h2>ResQ Lanka</h2>

<div>

<a href="create_incident.php">
Create Incident
</a>

</div>


</nav>



<div class="container">


<h1>Active Disaster Incidents</h1>



<?php while($row=mysqli_fetch_assoc($result)){ ?>


<div class="card">


<h2>

<?php echo $row['title']; ?>

</h2>


<p>

<?php echo $row['description']; ?>

</p>


<br>


<strong>

District:

</strong>

<?php echo $row['district']; ?>


<br><br>


<span class="priority">

<?php echo $row['priority']; ?>

</span>



<br>


<a class="btn"

href="incident.php?id=<?php echo $row['id']; ?>">

View Details

</a>



</div>


<?php } ?>



</div>


</body>

</html>