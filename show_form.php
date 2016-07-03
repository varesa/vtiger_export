<?php
include_once "vars.php";
include_once "vtiger.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Hakijan tiedot</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
</head>

<?php
function is_valid_request($fullid) {
    $id = substr($fullid, 0, strlen($fullid)-1);

    $weights = array(1, 3, 7, 1, 3, 7, 1, 3, 7);
    $sum = 0;
    for($i = 0; $i < strlen($id); $i++) {
        $mul = $id[$i] * $weights[$i];
        $sum += $mul;
        //echo "$id[$i] * $weights[$i] = $mul<br>";
    }
    $check = $sum % 10;
    return ($fullid == "$id$check");
}
?>

<body>
<!-- Page Content -->
<div class="container">
    <br><br>
    <?php
    $fullid = trim($_POST['id']) . trim($_GET['id']);
    if(!is_valid_request($fullid)) {
        echo "<h2>Virheellinen tunnus</h2>";
        echo "<a onclick='window.history.back()'>Takaisin</a>";
    } else {
        $id = substr($fullid, 0, strlen($fullid)-1); // Remove check digit
        global $endpoint;
        global $username;
        global $accesskey;
        
        $vtiger = new VTiger($endpoint);
        $vtiger->login($username, $accesskey);
        $hakija = $vtiger->retrieve("6x$id");

        if($hakija == false) {
            echo "<h2>Hakijaa ei löydy</h2>";
            echo "<a onclick='window.history.back()'>Takaisin</a>";
        } else {
            $nimi  = $hakija->productname;
            $email = $hakija->cf_538;
            $puh   = $hakija->cf_539;
            $osoite  = $hakija->cf_540;
            $ala     = $hakija->productcategory;
            $ammatti = $hakija->cf_541;
            $taidot  = $hakija->cf_542;
            $huomiot = $hakija->cf_543;
            $haastateltu = $hakija->cf_544;
            $details     = $hakija->description;

            echo "
            <div class='panel panel-default'>
                <div class='panel-heading'><h3 class='panel-title'>Tiedot</h3></div>
                <table class='table'>
                    <tr>
                        <td>Nimi: $nimi</td> <td>Osoite: $osoite</td>
                    </tr>
                    <tr>
                        <td>Puhelin: $puh</td> <td>Email: $email</td>
                    </tr>
                    <tr>
                        <td>Ala: $ala</td> <td>Ammatti: $ammatti</td>
                    </tr>
                    <tr>
                        <td>Taidot: $taidot</td><td>Haastateltu: $haastateltu</td>
                    </tr>
                    <tr>
                        <td colspan='2'>Käsittelijän merkinnät:<br> $huomiot</td>
                    </tr>
                    <tr>
                        <th>Muut tiedot:</th><th></th>
                    </tr>
                    <tr>
                        <td colspan='2' style='border-top: none;'>
                        " . str_replace("\n", "<br>", $details) . "
                        </td>
                    </tr>
                </table>
            </div>
            ";
        }
    }

    ?>

</div>
<!-- /.container -->

</body>

</html>
