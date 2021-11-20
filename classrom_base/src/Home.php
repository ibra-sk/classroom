<?php

class Home extends Model {
	
	public function __construct() {
		//$this->render();
	}
	
	protected function render($view_file,$view_data){
		$this->view_file = $view_file;
		$this->view_data = $view_data;
		if(file_exists(APP . 'view/' . $view_file . '.phtml'))
		{
		  include APP . 'view/' . $view_file . '.phtml';
		}
	}
	
	public function meUpload() {
		$this->render('upload', []);		
	}
	
	public function saveUpload() {
		
	}
	
	public function index() {
		$this->render('index', []);
	}
		
	public function login() {
		session_start();
		if(isset($_SESSION['user_access_id']) && isset($_SESSION['user_role_type'])){
			switch ($_SESSION['user_role_type']) {
				case 'ADMIN':
					header('location: admin/home');
					exit();
					break;
				case 'TEACHER':
					header('location: dashboard/classroom');
					exit();
					break;
				case 'STUDENT':
					header('location: portal/home');
					exit();
					break;
			}
		}
		
		$alert;
		$role_type;
		if(isset($_POST['submit'])){
			$username = $_POST['email'];
			$password = $_POST['pwd'];
			if($username == "" || $password == ""){
				$alert = "Please enter your email and password";
				$this->render('login', ['alert' => $alert]);
				exit();
			}else{
				$hashedpass = hash("sha256", $password);
				$data = $this->result("SELECT * FROM `accounts` WHERE `email`='".$username."' AND `password`='".$hashedpass."'");
				if($data){
					if($data['status'] == 'ACTIVE'){
						$_SESSION['user_access_id'] = $data['account_ID'];
						$_SESSION['user_role_type'] = $data['account_type'];
						$role_type = $data['account_type'];
					}else{
						//RESPOND TO DIFFERENT ACCOUNT STATUS VALUES
						switch ($data['status']) {
							case 'INACTIVE':
								$alert = "This account has been inactiveted";
								break;
							case 'SUSPENDED':
								$alert = "Your account has been suspended, Please contact Administator.";
								break;							
						}
						$this->render('login', ['alert' => $alert]);
						exit();
					}					
				}else{
					$alert = "Wrong email or password";
					$this->render('login', ['alert' => $alert]);
					exit();
				}
			}
		}else{
			$this->render('login', []);
		}
		
		if(isset($role_type)){
			$date = date("Y/m/d");			
			switch ($role_type) {
				case 'ADMIN':
					header('location: admin/home');
					exit();
					break;
				case 'TEACHER':
					$last_login = $this->result("SELECT `last_login_date` FROM `teachers` WHERE `account_ID`='".$_SESSION['user_access_id']."'");
					$_SESSION['user_last_login'] = $last_login['last_login_date'];
					$upd = $this->write("UPDATE `teachers` SET `last_login_date`='".$date."' WHERE `account_ID`='".$_SESSION['user_access_id']."'");
					header('location: dashboard/classroom');
					exit();
					break;
				case 'STUDENT':
					$last_login = $this->result("SELECT `last_login_date` FROM `students` WHERE `account_ID`='".$_SESSION['user_access_id']."'");
					$_SESSION['user_last_login'] = $last_login['last_login_date'];
					$upd = $this->write("UPDATE `students` SET `last_login_date`='".$date."' WHERE `account_ID`='".$_SESSION['user_access_id']."'");
					header('location: portal/home');
					exit();
					break;
			}
		}		
	}
	
	public function logout() {
		session_start();
		unset($_SESSION['user_access_id']);
		header('location: home');
	}
	
	public function ForgotPassword() {
		if(isset($_POST['submit'])){
			$username = $_POST['email'];
			if($username == ""){
				$alert = "Please enter your email first";
				$this->render('forgot', ['alert' => $alert]);
				exit();
			}else{
				$data = $this->result("SELECT * FROM `accounts` WHERE `email`='".$username."'");
				if($data){
					$forgot_pwd_token = $this->generateRandomString();
					$encoded_ID = base64_encode($data['account_ID']);
					$query = $this->write("UPDATE `accounts` SET `on_forgot_pwd`='1',`forgot_pwd_token`='".$forgot_pwd_token."' WHERE `email`='".$username."'");
					if($query){
						$this->render('forgotconfirm', []);
					}else{
						$alert = "Error occurred while resetting your password, Please try again later.";
						$this->render('forgot', ['alert' => $alert]);
					}
				}else{
					$alert = "Given email address in unavialable";
					$this->render('forgot', ['alert' => $alert]);
				}
			}
		}else{
			$this->render('forgot', []);
		}
		
	}
	
	public function ResetPassword() {
		if(isset($_POST['submit'])){
			$accountID = $_POST['account'];
			$token_key = $_POST['token'];
			$password = $_POST['newpwd'];
			$repassword = $_POST['repwd'];
			if($password == $repassword){
				$data = $this->result("SELECT * FROM `accounts` WHERE `account_ID`='".$accountID."' AND `on_forgot_pwd`='1' AND `forgot_pwd_token`='".$token_key."'");
				if($data){
					$hashedpass = hash("sha256", $password);
					$query = $this->write("UPDATE `accounts` SET `password`='".$hashedpass."', `on_forgot_pwd`='0',`forgot_pwd_token`='none' WHERE `account_ID`='".$accountID."'");
					if($query){
						header('location: '.DOMAIN.'login');
						exit();
					}else{
						$alert = "Error occurred while resetting your password, Please try again later.";
						$this->render('resetpwd', ['alert' => $alert, 'token' => $token_key, 'usrid' => $accountID]);
					}
				}else{
					$alert = "The account you are trying to reset is not valid";
					$this->render('resetpwd', ['alert' => $alert, 'token' => $token_key, 'usrid' => $accountID]);
				}
			}else{
				$alert = "Password do not match, Try again.";
				$this->render('resetpwd', ['alert' => $alert, 'token' => $token_key, 'usrid' => $accountID]);
				exit();
			}
		}else{
			if(isset($_GET['q']) && isset($_GET['em'])){
				$usrID = base64_decode($_GET['em']);
				$token = $_GET['q'];
				$this->render('resetpwd', ['token' => $token, 'usrid' => $usrID]);
			}else{
				header('location: error404');
				exit();
			}
		}
		
	}
	
	function generateRandomString($length = 20) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);		
	}
	
}

?>