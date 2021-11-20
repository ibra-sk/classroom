<?php

class Admin extends Model {
	protected $account_ID;	
	protected $account;
	protected $profile;		
	
	public function __construct() {		
		session_start();
		if(!isset($_SESSION['user_access_id'])){
			header('location: '.DOMAIN.'login');
			exit();
		}else{
			if($_SESSION['user_role_type'] == 'ADMIN'){
				$data = $this->result("SELECT * FROM `accounts` WHERE `account_ID`='".$_SESSION['user_access_id']."'");
				$this->account_ID = $_SESSION['user_access_id'];
				$this->account = 'ADMINISTRATOR';
				$this->profile = 'admin.ico';
			}else{
				header('location: '.DOMAIN.'login');
				exit();
			}			
		}
	}
	
	function generateRandomString($length = 20) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);		
	}
	
	protected function render($view_file,$view_data){
		$this->view_file = $view_file;
		$this->view_data = $view_data;
		if(file_exists(APP . 'view/' . $view_file . '.phtml'))
		{
		  include APP . 'view/' . $view_file . '.phtml';
		}
	}
	
	public function home() {	
		$students = $this->read("SELECT * FROM `students`");
		$teachers = $this->read("SELECT * FROM `teachers`");
		$courses = $this->read("SELECT * FROM `courses`");
		$payment = $this->read("SELECT * FROM `payment`");
		$this->render('include/header', []);
		$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
		$this->render('admin/home', ['num_courses' => $courses, 'num_students' => $students, 'num_teachers' => $teachers, 'payments' => $payment]);
		$this->render('include/footer', []);
	}
	
	
	public function listStudents() {
		$students = $this->read("SELECT * FROM `students`");
		$this->render('include/header', []);
		$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
		$this->render('admin/students', ['students' => $students]);
		$this->render('include/footer', []);
	}
	
	public function addStudents() {
		if(isset($_POST['name'])){
			$name = $_POST['name'];
			$phone = $_POST['phone'];
			$email = $_POST['email'];
			$address = $_POST['address'];
			$birth = $_POST['birth'];
			$date = date('Y/m/d');
			$student_ID = $this->generateRandomString(12);
			
			if(!empty($_FILES['cover_image'])){
				$sourcePath = $_FILES['cover_image']['tmp_name'];
				$targetPath = "data/accounts/profile/".$student_ID.".jpg";
				move_uploaded_file($sourcePath,$targetPath);
			}
			
			$data = $this->write("INSERT INTO `students`(`account_ID`, `fullname`, `profile_image`, `phone_number`, `address`, `birth_date`) VALUES ('".$student_ID."','".$name."','".$student_ID.".jpg','".$phone."','".$address."','".$birth."')");
			if($data){
				$que = $this->write("INSERT INTO `accounts`(`account_ID`, `email`, `password`, `account_type`, `on_forgot_pwd`, `forgot_pwd_token`, `status`, `created_date`) VALUES ('".$student_ID."','".$email."','5B18A780FA79E25676E63C057025B9B650E9748DBEB36AA69FD00663EAEC471C','STUDENT','0','none','ACTIVE','".$date."')");
				if($que){
					header('location: '.DOMAIN.'admin/students?alert=success');
					exit();
				}else{
					header('location: '.DOMAIN.'admin/students?/add/?'.$teacher_ID.'&alert=account_failed');
					exit();
				}
			}else{
				header('location: '.DOMAIN.'admin/students?/add/?'.$teacher_ID.'&alert=submit_failed');
				exit();
			}
		}else{
			$this->render('include/header', []);
			$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
			$this->render('admin/students_view', []);
			$this->render('include/footer', []);
		}
		
	}
	
	public function editStudents() {
		if(isset($_POST['name'])){
			$name = $_POST['name'];
			$phone = $_POST['phone'];
			$address = $_POST['address'];
			$birth = $_POST['birth'];
			$student_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
			
			if(!empty($_FILES['cover_image'])){
				$sourcePath = $_FILES['cover_image']['tmp_name'];
				$targetPath = "data/accounts/profile/".$student_ID.".jpg";
				move_uploaded_file($sourcePath,$targetPath);
			}
			
			$data = $this->write("UPDATE `students` SET `fullname`='".$name."', `phone_number`='".$phone."',`address`='".$address."',`birth_date`='".$birth."' WHERE `account_ID`='".$student_ID."'");
			if($data){
				header('location: '.DOMAIN.'admin/students?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/students?/edit/?'.$student_ID.'&alert=failed');
				exit();
			}
		}else{
			$courses = $this->read("SELECT * FROM `courses`");
			$student = $this->result("SELECT * FROM `students` WHERE `account_ID`='".parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)."'");
			$enrolli = $this->read("SELECT `course_ID` FROM `enrollment` WHERE `account_ID`='".parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)."'");
			$enroll = array_column($enrolli, 'course_ID');
			$this->render('include/header', []);
			$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
			$this->render('admin/students_edit', ['courses' => $courses, 'student' => $student, 'enroll' => $enroll]);
			$this->render('include/footer', []);
		}
		
	}
	
	public function removeStudents() {
		if(isset($_GET['stid'])){
			$student_ID = $_GET['stid'];
			$data = $this->write("DELETE FROM `students` WHERE `account_ID`='".$student_ID."'");
			if($data){
				header('location: '.DOMAIN.'admin/students?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/students/edit/?'.$student_ID.'&alert=failed');
				exit();
			}
		}else{
			header('location: '.DOMAIN.'admin/students');
			exit();
		}
	}
	
	public function linkStudents() {
		if(isset($_GET['stid']) && isset($_GET['cuid']) && isset($_GET['ac'])){
			$student_ID = $_GET['stid'];
			$course_ID = $_GET['cuid'];
			$action = $_GET['ac'];
			$date = date('Y/m/d');
			if($action == 'link'){
				$data = $this->write("INSERT INTO `enrollment`(`course_ID`, `account_ID`, `position_as`, `enroll_date`) VALUES ('".$course_ID."','".$student_ID."','STUDENT','".$date."')");
			}else{
				$data = $this->write("DELETE FROM `enrollment` WHERE `account_ID`='".$student_ID."' AND `course_ID`='".$course_ID."'");
			}
			if($data){
				header('location: '.DOMAIN.'admin/students?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/students/edit/?'.$student_ID.'&alert=failed');
				exit();
			}
		}else{
			header('location: '.DOMAIN.'admin/students');
			exit();
		}
	}
	
	public function listTeachers() {
		$teachers = $this->read("SELECT * FROM `teachers`");
		$this->render('include/header', []);
		$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
		$this->render('admin/teachers', ['teachers' => $teachers]);
		$this->render('include/footer', []);
	}
	
	public function addTeachers() {
		if(isset($_POST['name'])){
			$name = $_POST['name'];
			$phone = $_POST['phone'];
			$email = $_POST['email'];
			$address = $_POST['address'];
			$skill = $_POST['skill'];
			$birth = $_POST['birth'];
			$date = date('Y/m/d');
			$teacher_ID = $this->generateRandomString(12);
			
			if(!empty($_FILES['cover_image'])){
				$sourcePath = $_FILES['cover_image']['tmp_name'];
				$targetPath = "data/accounts/profile/".$teacher_ID.".jpg";
				move_uploaded_file($sourcePath,$targetPath);
			}
			
			$data = $this->write("INSERT INTO `teachers`(`account_ID`, `fullname`, `profile_image`, `phone_number`, `address`, `birth_date`, `skills`) VALUES ('".$teacher_ID."','".$name."','".$teacher_ID.".jpg','".$phone."','".$address."','".$birth."','".$skill."')");
			if($data){
				$que = $this->write("INSERT INTO `accounts`(`account_ID`, `email`, `password`, `account_type`, `on_forgot_pwd`, `forgot_pwd_token`, `status`, `created_date`) VALUES ('".$teacher_ID."','".$email."','5B18A780FA79E25676E63C057025B9B650E9748DBEB36AA69FD00663EAEC471C','TEACHER','0','none','ACTIVE','".$date."')");
				if($que){
					header('location: '.DOMAIN.'admin/teachers?alert=success');
					exit();
				}else{
					header('location: '.DOMAIN.'admin/teachers?/add/?'.$teacher_ID.'&alert=account_failed');
					exit();
				}
			}else{
				header('location: '.DOMAIN.'admin/teachers?/add/?'.$teacher_ID.'&alert=submit_failed');
				exit();
			}
		}else{
			$this->render('include/header', []);
			$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
			$this->render('admin/teachers_view', []);
			$this->render('include/footer', []);
		}
		
	}
	
	public function editTeachers() {
		if(isset($_POST['name'])){
			$name = $_POST['name'];
			$phone = $_POST['phone'];
			$address = $_POST['address'];
			$skill = $_POST['skill'];
			$birth = $_POST['birth'];
			$teacher_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
			
			if(!empty($_FILES['cover_image'])){
				$sourcePath = $_FILES['cover_image']['tmp_name'];
				$targetPath = "data/accounts/profile/".$teacher_ID.".jpg";
				move_uploaded_file($sourcePath,$targetPath);
			}
			
			$data = $this->write("UPDATE `teachers` SET `fullname`='".$name."', `phone_number`='".$phone."',`address`='".$address."',`birth_date`='".$birth."',`skills`='".$skill."' WHERE `account_ID`='".$teacher_ID."'");
			if($data){
				header('location: '.DOMAIN.'admin/teachers?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/teachers?/edit/?'.$teacher_ID.'&alert=failed');
				exit();
			}
		}else{
			$courses = $this->read("SELECT * FROM `courses`");
			$teacher = $this->result("SELECT * FROM `teachers` WHERE `account_ID`='".parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)."'");
			$enrolli = $this->read("SELECT `course_ID` FROM `enrollment` WHERE `account_ID`='".parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)."'");
			$enroll = array_column($enrolli, 'course_ID');
			$this->render('include/header', []);
			$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
			$this->render('admin/teachers_edit', ['courses' => $courses, 'teacher' => $teacher, 'enroll' => $enroll]);
			$this->render('include/footer', []);
		}
		
	}
	
	public function removeTeachers() {
		if(isset($_GET['thid'])){
			$teacher_ID = $_GET['thid'];
			$data = $this->write("DELETE FROM `teachers` WHERE `account_ID`='".$teacher_ID."'");
			if($data){
				header('location: '.DOMAIN.'admin/teachers?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/teachers/edit/?'.$teacher_ID.'&alert=failed');
				exit();
			}
		}else{
			header('location: '.DOMAIN.'admin/teachers');
			exit();
		}
	}
	
	public function linkTeachers() {
		if(isset($_GET['thid']) && isset($_GET['cuid']) && isset($_GET['ac'])){
			$teacher_ID = $_GET['thid'];
			$course_ID = $_GET['cuid'];
			$action = $_GET['ac'];
			$date = date('Y/m/d');
			if($action == 'link'){
				$data = $this->write("INSERT INTO `enrollment`(`course_ID`, `account_ID`, `position_as`, `enroll_date`) VALUES ('".$course_ID."','".$teacher_ID."','TEACHER','".$date."')");
			}else{
				$data = $this->write("DELETE FROM `enrollment` WHERE `account_ID`='".$teacher_ID."' AND `course_ID`='".$course_ID."'");
			}
			if($data){
				header('location: '.DOMAIN.'admin/teachers?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/teachers/edit/?'.$teacher_ID.'&alert=failed');
				exit();
			}
		}else{
			header('location: '.DOMAIN.'admin/teachers');
			exit();
		}
	}
	
	public function showClassRoom() {
		$num_students = 0;
		$num_courses = 0;
		$classroom = [];
		
		$courses = $this->read("SELECT * FROM `courses`");
		$num_courses = count($courses);
		foreach($courses as $course){
			$course_ID = $course['course_ID'];
			$enrollment = $this->read("SELECT `account_ID` FROM `enrollment` WHERE `course_ID`='".$course_ID."' AND `position_as`='STUDENT'");
			$enroll = array_column($enrollment, 'account_ID');
			$num_enroll = count($enroll);
			$num_students += $num_enroll;
			
			$class = array('course_ID' => $course_ID,
						   'title' => $course['title'],
						   'image' => $course['cover_image'],
						   'teacher' => $course['teacher_name'],
						   'enrolled' => $num_enroll,
						   'chapters' => $course['total_chapters'],
						   'date' => $course['created_date']);
			array_push($classroom, $class);
		}
		
		$this->render('include/header', []);
		$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
		$this->render('admin/classroom', ['classroom' => $classroom]);
		$this->render('include/footer', []);
	}
	
	public function addClassRoom() {
		if(isset($_POST['Save'])){
			$title = $_POST['title'];
			if(isset($_POST['teacher'])){
				$teacher_ID = $_POST['teacher'];
				$teacher_name = $this->result('SELECT `fullname` FROM `teachers` WHERE `account_ID`="'.$teacher_ID.'"');
			}else{
				$teacher_ID = '0';
				$teacher_name = '';
			}
			$course_ID = $this->generateRandomString();
			$date = date("Y/m/d");
			if(!empty($_FILES['cover_image'])){
				$sourcePath = $_FILES['cover_image']['tmp_name'];
				$targetPath = "data/courses/".$course_ID.".jpg";
				if(move_uploaded_file($sourcePath,$targetPath)) {
				}else{
					header('location: '.DOMAIN.'admin/classroom/add?alert=error');
					exit();
				}
			}
			$data = $this->write("INSERT INTO `courses`(`course_ID`, `title`, `cover_image`, `teacher_ID`, `teacher_name`, `total_chapters`, `created_date`) VALUES ('".$course_ID."','".$title."','".$course_ID.".jpg','".$teacher_ID."','".$teacher_name['fullname']."','0','".$date."')");
			if($data){
				header('location: '.DOMAIN.'admin/classroom?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/classroom/add?alert=failed');
				exit();
			}
		}else{
			$teachers = $this->read('SELECT * FROM `teachers`');
			$this->render('include/header', []);
			$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
			$this->render('admin/classroom_add', ['teachers' => $teachers]);
			$this->render('include/footer', []);
		}		
	}
	
	public function viewClassRoom() {
		$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if(!empty($course_ID)){
			$teachers = $this->read('SELECT * FROM `teachers`');
			$course = $this->result('SELECT * FROM `courses` WHERE `course_ID`="'.$course_ID.'"');
			$this->render('include/header', []);
			$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
			$this->render('admin/classroom_edit', ['teachers' => $teachers, 'course' => $course]);
			$this->render('include/footer', []);
		}else{
			header('location: '.DOMAIN.'admin/classroom');
			exit();
		}
		
	}
	
	public function editClassRoom() {
		if(isset($_POST['Save'])){
			$title = $_POST['title'];
			$course_ID = $_POST['course_ID'];
			if(isset($_POST['teacher'])){
				$teacher_ID = $_POST['teacher'];
				$teacher_name = $this->result('SELECT `fullname` FROM `teachers` WHERE `account_ID`="'.$teacher_ID.'"');
			}else{
				$teacher_ID = '0';
				$teacher_name = '';
			}
			if(isset($_FILES['cover_image']) && !empty($_FILES['cover_image'])){
				$sourcePath = $_FILES['cover_image']['tmp_name'];
				$targetPath = "data/courses/".$course_ID.".jpg";
				if(move_uploaded_file($sourcePath,$targetPath)) {
				}
			}
			$data = $this->write("UPDATE `courses` SET `title`='".$title."', `teacher_ID`='".$teacher_ID."', `teacher_name`='".$teacher_name['fullname']."' WHERE `course_id`='".$course_ID."'");
			if($data){
				header('location: '.DOMAIN.'admin/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/classroom?alert=failed');
				exit();
			}
		}elseif(isset($_POST['Delete'])){
			$course_ID = $_POST['course_ID'];
			$data = $this->write("DELETE FROM `courses` WHERE `course_id`='".$course_ID."'");
			if($data){
				header('location: '.DOMAIN.'admin/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/classroom?alert=failed');
				exit();
			}
		}else{
			header('location: '.DOMAIN.'admin/classroom');
			exit();
		}		
	}
	
	public function addPayment() {
		if(isset($_POST['student'])){
			$name = $_POST['student'];
			$start_date = date("Y/m/d", strtotime($_POST['start_date']));
			$end_date = date("Y/m/d", strtotime($_POST['end_date']));;
			$amount = $_POST['amount'];
			
			$data = $this->write("INSERT INTO `payment`(`account_ID`, `start_date`, `due_date`, `amount`, `stage`) VALUES ('".$name."','".$start_date."','".$end_date."','".$amount."','ONGOING')");
			if($data){
				header('location: '.DOMAIN.'admin/home?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/home&alert=failed');
				exit();
			}
		}else{
			
			$students = $this->read('SELECT * FROM `students`');
			$this->render('include/header', []);
			$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
			$this->render('admin/payment_add', ['students' => $students]);
			$this->render('include/footer', []);
		}
		
	}
	
	public function removePayment() {	
		if(isset($_GET['uid'])){
			$account_ID = $_GET['uid'];
			$data = $this->write("DELETE FROM `payment` WHERE `account_ID`='".$account_ID."'");
			if($data){
				header('location: '.DOMAIN.'admin/home?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admin/home&alert=failed');
				exit();
			}
		}
	}
	
	public function viewSettings() {	
		$this->render('include/header', []);
		$this->render('admin/navbar', ['username' => $this->account, 'userimage' => $this->profile]);
		$this->render('admin/settings', []);
		$this->render('include/footer', []);
	}
	
	public function saveSettings() {	
		$password = $_POST['newpwd'];
		$repassword = $_POST['repwd'];
		if($password == $repassword){
			$hashedpass = hash("sha256", $password);
			$query = $this->write("UPDATE `accounts` SET `password`='".$hashedpass."', `on_forgot_pwd`='0',`forgot_pwd_token`='none' WHERE `account_ID`='".$this->account_ID."'");
			if($query){
				header('location: '.DOMAIN.'admin/settings?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'admi/settings?alert=error');
			}
		}else{
			header('location: '.DOMAIN.'admin/settings?alert=mismatch');
			exit();
		}
	}
}

?>