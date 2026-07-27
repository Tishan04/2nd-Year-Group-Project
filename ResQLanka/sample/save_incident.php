<?php

include "db.php";


$title=$_POST['title'];

$description=$_POST['description'];

$district=$_POST['district'];

$location=$_POST['location'];

$volunteers=$_POST['volunteers'];

$priority=$_POST['priority'];



$sql="INSERT INTO disaster_incidents

(title,description,district,location,volunteers_needed,priority)

VALUES

('$title','$description','$district','$location','$volunteers','$priority')";



if(mysqli_query($conn,$sql))
{

header("Location:index.php");

}

else{

echo "Error";

}


?>