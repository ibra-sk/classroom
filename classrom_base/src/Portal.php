<?php

class Portal extends Model {
	protected $account_ID;	
	protected $account;
	protected $profile;		
	
	public function __construct() {		
		session_start();
		if(!isset($_SESSION['user_access_id'])){
			header('location: '.DOMAIN.'login');
			exit();
		}else{
			$data = $this->result("SELECT * FROM `accounts` WHERE `account_ID`='".$_SESSION['user_access_id']."'");
			$query = $this->result("SELECT * FROM `students` WHERE `account_ID`='".$_SESSION['user_access_id']."'");
			$this->account_ID = $_SESSION['user_access_id'];
			$this->account = $data;
			$this->profile = $query;
		}
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
		$studies = [];
		$payment = $this->result("SELECT * FROM `payment` WHERE `account_ID`='".$this->account_ID."' AND `stage`='ONGOING'");					
		$enrollment = $this->read("SELECT `course_ID` FROM `enrollment` WHERE `account_ID`='".$this->account_ID."' AND `position_as`='STUDENT'");					
		$enroll = array_column($enrollment, 'course_ID');
		foreach($enroll as $course){
			$courses = $this->result("SELECT * FROM `courses` WHERE `course_ID`='".$course."'");
			$finished = $this->read("SELECT * FROM `completion` WHERE `account_ID`='".$this->account_ID."' AND `course_ID`='".$course."'");
			$completion = (count($finished) * 100) / $courses['total_chapters'];
			$person = array('course_ID' => $course,
							'title' => $courses['title'],
							'image' => $courses['cover_image'],
							'chapters' => $courses['total_chapters'],
							'completion' => $completion,
							'date' => $courses['created_date']);
			array_push($studies, $person);
		}
		$this->render('include/header', []);
		$this->render('portal/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
		$this->render('portal/home', ['courses' => $studies, 'payment' => $payment]);
		$this->render('include/footer', []);
	}
	
	public function ListCourses() {
		$myclass = [];
		$allclass = [];
		$courses = $this->read("SELECT * FROM `courses`");
		if(!empty($courses)){
			$enroll = $this->read("SELECT `course_ID` FROM `enrollment` WHERE `account_ID`='".$this->account_ID."'");
			$enroll = array_column($enroll, 'course_ID');
			foreach($courses as $class){
				if(in_array( $class['course_ID'] ,$enroll)){
					array_push($myclass, $class);
				}else{
					array_push($allclass, $class);
				}
			}
		}
		$this->render('include/header', []);
		$this->render('portal/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
		$this->render('portal/courses', ['allclass' => $allclass, 'myclass' => $myclass]);
		$this->render('include/footer', []);
	}	
	
	public function ViewCourses() {
		$course_ID = $_GET['cuid'];
		if($this->checkCourseAccess($course_ID)){
			$content = '';
			if(isset($_GET['chid'])){	
				$chapter_ID = $_GET['chid'];
			}else{
				$chapter_ID = $this->result("SELECT `chapter_ID` FROM `chapters` WHERE `course_ID`='".$course_ID."' AND `rank_position`=1");
				$chapter_ID = $chapter_ID['chapter_ID'];				
			}
			$filename = 'data/chapters/'.$chapter_ID.'.jsp';
			$content = file_get_contents($filename);
			$chapters = $this->read("SELECT * FROM `chapters` WHERE `course_ID`='".$course_ID."' ORDER BY `rank_position` ASC");
			$rank = $this->result("SELECT `rank_position` FROM `chapters` WHERE `chapter_ID`='".$chapter_ID."' AND `course_ID`='".$course_ID."'");
			$files = $this->read("SELECT * FROM `files` WHERE `course_ID`='".$course_ID."' AND `chapter_ID`='".$chapter_ID."'");
			$this->render('include/header', []);
			$this->render('portal/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('portal/courses_view', ['chapters' => $chapters, 'rank' => $rank,'content' => $content, 'chapter_ID' => $chapter_ID, 'files' => $files, 'course_ID' => $course_ID]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'portal/courses');
				exit();
			}else{
				header('location: '.DOMAIN.'portal/page/notfound');
				exit();
			}
		}
	}	
	
	public function ViewForum() {
		$course_ID = $_GET['cuid'];
		if($this->checkCourseAccess($course_ID)){
			if(isset($_GET['chid'])){	
				$chapter_ID = $_GET['chid'];
			}else{
				header('location: '.DOMAIN.'portal/page/notfound');
				exit();			
			}
			$forum = $this->read("SELECT * FROM `forums` WHERE `course_ID`='".$course_ID."' AND `chapter_ID`='".$chapter_ID."'");
			$chathead = [];
			foreach($forum as $message){
				if($message['account_type'] == 'STUDENT'){
					$account = $this->result("SELECT * FROM `students` WHERE `account_ID`='".$message['account_ID']."'");
				}else{
					$account = $this->result("SELECT * FROM `teachers` WHERE `account_ID`='".$message['account_ID']."'");
				}
				$chead = array('fullname' => $account['fullname'],
							   'image' => $account['profile_image'],
							   'account_type' => $message['account_type'],
							   'content' => $message['msg_content'],
							   'date' => $message['created_date']);
				array_push($chathead,$chead);
			}
			$this->render('include/header', []);
			$this->render('portal/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('portal/courses_forum', ['forum' => $chathead, 'course_ID' => $course_ID, 'chapter_ID' => $chapter_ID]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'portal/courses');
				exit();
			}else{
				header('location: '.DOMAIN.'portal/page/notfound');
				exit();
			}
		}
	}	
	
	public function ViewBooks() {
		$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if($this->checkCourseAccess()){
			$chapters = $this->read("SELECT * FROM `chapters` WHERE `course_ID`='".$course_ID."' ORDER BY `rank_position` ASC");
			$books = $this->read("SELECT * FROM `ebooks` WHERE `course_ID`='".$course_ID."'");
			$this->render('include/header', []);
			$this->render('portal/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('portal/courses_books', ['chapters' => $chapters, 'ebooks' => $books]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'portal/courses');
				exit();
			}else{
				header('location: '.DOMAIN.'portal/page/notfound');
				exit();
			}
		}
	}	
	
	public function ViewPeople() {
		$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if($this->checkCourseAccess()){
			$chapters = $this->read("SELECT * FROM `chapters` WHERE `course_ID`='".$course_ID."' ORDER BY `rank_position` ASC");
			$people = $this->read("SELECT * FROM `enrollment` WHERE `course_ID`='".$course_ID."'");
			$teachers = [];
			$students = [];
			foreach($people as $person){
				if($person['position_as'] == 'STUDENT'){
					$account = $this->result("SELECT * FROM `students` WHERE `account_ID`='".$person['account_ID']."'");
					$chead = array('fullname' => $account['fullname'],
							   'image' => $account['profile_image']);
					array_push($students,$chead);
				}else{
					$account = $this->result("SELECT * FROM `teachers` WHERE `account_ID`='".$person['account_ID']."'");
					$chead = array('fullname' => $account['fullname'],
							   'image' => $account['profile_image']);
					array_push($teachers,$chead);
				}
				
			}
			$this->render('include/header', []);
			$this->render('portal/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('portal/courses_attendee', ['chapters' => $chapters, 'students' => $students, 'teachers' => $teachers, 'course_ID' => $course_ID]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'portal/courses');
				exit();
			}else{
				header('location: '.DOMAIN.'portal/page/notfound');
				exit();
			}
		}
		
	}	
	
	public function ViewFiles() {
		$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if($this->checkCourseAccess()){
			$chapters = $this->read("SELECT * FROM `chapters` WHERE `course_ID`='".$course_ID."' ORDER BY `rank_position` ASC");
			$files = $this->read("SELECT * FROM `files` WHERE `course_ID`='".$course_ID."'");
			$this->render('include/header', []);
			$this->render('portal/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('portal/courses_files', ['chapters' => $chapters, 'files' => $files]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'portal/courses');
				exit();
			}else{
				header('location: '.DOMAIN.'portal/page/notfound');
				exit();
			}
		}
	}	
	
	public function setCompletion() {
		$course_ID = $_POST['course_ID'];
		$chapter_ID = $_POST['chapter_ID'];
		if($this->checkCourseAccess($course_ID)){
			$date = date('Y/m/d');
			$data = $this->result("SELECT * FROM `completion` WHERE `account_ID`='".$this->account_ID."' AND `course_ID`='".$course_ID."' AND `chapter_ID`='".$chapter_ID."'");
			if(COUNT($data) == 0){
					$query = $this->write("INSERT INTO `completion`(`account_ID`, `course_ID`, `chapter_ID`, `date`) VALUES ('".$this->account_ID."','".$course_ID."','".$chapter_ID."','".$date."')");
				if($query){
					echo 'success';
				}else{
					echo 'failed';
				}
			}else{
				echo 'exists';
			}
			
		}else{
			echo 'invalid';
		}
	}	
	
	public function setForumPost() {
		$course_ID = $_POST['course_ID'];
		$chapter_ID = $_POST['chapter_ID'];
		if($this->checkCourseAccess($course_ID)){
			$date = date('Y/m/d H:i:s');
			$content = $_POST['content'];
			$msg_ID = $this->generateRandomString();
			$query = $this->write("INSERT INTO `forums`(`message_ID`, `course_ID`, `chapter_ID`, `account_ID`, `account_type`, `msg_content`, `msg_type`, `created_date`) VALUES ('".$msg_ID."','".$course_ID."','".$chapter_ID."','".$this->account_ID."','".$this->account['account_type']."','".$content."','TEXT','".$date."')");
			if($query){
				echo 'success';
			}else{
				echo 'failed';
			}
		}else{
			echo 'invalid';
		}
	}	
	
	public function viewSettings() {	
		$this->render('include/header', []);
		$this->render('portal/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
		$this->render('portal/settings', []);
		$this->render('include/footer', []);
	}
	
	public function saveSettings() {	
		$password = $_POST['newpwd'];
		$repassword = $_POST['repwd'];
		if($password == $repassword){
			$hashedpass = hash("sha256", $password);
			$query = $this->write("UPDATE `accounts` SET `password`='".$hashedpass."', `on_forgot_pwd`='0',`forgot_pwd_token`='none' WHERE `account_ID`='".$this->account_ID."'");
			if($query){
				header('location: '.DOMAIN.'portal/settings?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'portal/settings?alert=error');
			}
		}else{
			header('location: '.DOMAIN.'portal/settings?alert=mismatch');
			exit();
		}
	}
	
	function checkCourseAccess($cuid = null){
		if($cuid <> null){
			$course_ID = $cuid;
		}else{
			$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		}		
		if(empty($course_ID)){
			return false;
		}else{
			$enroll = $this->read("SELECT `course_ID` FROM `enrollment` WHERE `account_ID`='".$this->account_ID."'");
			$enroll = array_column($enroll, 'course_ID');
			if(in_array($course_ID, $enroll)){
				return true;
			}else{
				return false;
			}
		}		
	}
	
	function generateRandomString($length = 20) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
	}
}

?>