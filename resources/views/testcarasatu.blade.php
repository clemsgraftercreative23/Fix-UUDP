<!-- <input type="text" name="token" id="token"> -->

<script>
var urlhash = window.location.hash, 
    txthash = urlhash.replace("#", ""); 
    subStr = txthash.substr(13, 36);
    console.log(subStr);
    // window.onload=document.getElementById("token");
    // var element_jam = document.getElementById("token");
    // element_jam.value = subStr;
</script>

<?php
$hash = "<script>document.writeln(subStr);</script>";


   


$api_url = 'https://account.accurate.id/api/open-db.do?id=193658';


$context = stream_context_create(array(
    'http' => array(
        'header' => "Authorization: Bearer 5d8f6753-d1c4-4b4a-a7bc-6bf887e18177",
    ),
));

$result = file_get_contents($api_url, false, $context);
$data = json_decode($result, TRUE);
echo "<pre>";
print_r($data['session']);
echo "</pre>";


// $dbhost = 'localhost:3306';
   // $dbuser = 'phpmyadmin';
   // $dbpass = 'Matilampu2019@@';
   // $dbname = 'uudp';
   // $conn = mysqli_connect($dbhost, $dbuser, $dbpass,$dbname);
   
   // if(! $conn ) {
   //    die('Could not connect: ' . mysqli_error());
   // }
   // echo 'Connected successfully<br>';
   // $sql = "UPDATE apis SET token='$hash' WHERE id=1";
   
   // if (mysqli_query($conn, $sql)) {
   //    echo "Record updated successfully";
   // } else {
   //    echo "Error updating record: " . mysqli_error($conn);
   // }
   // mysqli_close($conn);

?>




