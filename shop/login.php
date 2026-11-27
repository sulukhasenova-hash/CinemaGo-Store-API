<?php
session_start();require __DIR__.'/lib/api.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: index.php');exit;}
try{$u=api_request('auth.php','POST',['login'=>trim($_POST['login']??''),'password'=>$_POST['password']??'']);session_regenerate_id(true);$_SESSION['user_id']=(int)$u['id'];header('Location: index.php');exit;}
catch(Throwable $e){$_SESSION['login_error']=$e->getMessage();header('Location: index.php#login');exit;}
