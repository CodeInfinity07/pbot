<?php
require_once("../../includes/connection.php");
if(!isset($_SESSION['admin_login']))
{
	header("Location: login.php");
}

if(isset($_GET['user_id']))
{   
    $id = db_filter($_GET['user_id']);
    $user = mysqli_fetch_assoc(mysqli_query($DB_CONN,"SELECT * from users where id = '{$id}'"));
    $user['balance'] = amount_format($user['balance']);
    $userd = mysqli_fetch_assoc(mysqli_query($DB_CONN,"SELECT sum(amount) as deposit FROM `package_deposits` where status = 1 or avail > 0 and user_id = '{$id}'"));
   
    ?>

    <tr>
        <td>Username: <a href="admin?page=user&id=<?=$id?>&view=overview"><?=$user['username']?></a></td>
        <td>Email: <?=$user['email']?></td>
    </tr>
    <tr>
        <td>Balance: $ <?=$user['balance']?></td>
        <td>Deposit: $ <?=$userd['deposit']?></td>
    </tr>
  
    <?
}