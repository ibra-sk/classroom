<?php

class Dashboard extends Model {
	protected $account_ID;	
	protected $account;
	protected $profile;		
	
	public function __construct() {		
		session_start();
		if(!isset($_SESSION['user_access_id'])){
			header('location: '.DOMAIN.'login');
			exit();
		}else{
			if($_SESSION['user_role_type'] == 'TEACHER'){
				$data = $this->result("SELECT * FROM `accounts` WHERE `account_ID`='".$_SESSION['user_access_id']."'");
				$query = $this->result("SELECT * FROM `teachers` WHERE `account_ID`='".$_SESSION['user_access_id']."'");
				$this->account_ID = $_SESSION['user_access_id'];
				$this->account = $data;
				$this->profile = $query;
			}else{
				header('location: '.DOMAIN.'login');
				exit();
			}			
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
		$this->render('include/header', []);
		$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
		$this->render('dashboard/home', []);
		$this->render('include/footer', []);
	}
	
	public function showClassRoom() {
		$num_students = 0;
		$num_courses = 0;
		$classroom = [];
		
		$courses = $this->read("SELECT * FROM `courses` WHERE `teacher_ID`='".$this->account_ID."'");
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
						   'enrolled' => $num_enroll,
						   'chapters' => $course['total_chapters'],
						   'date' => $course['created_date']);
			array_push($classroom, $class);
		}
		
		$this->render('include/header', []);
		$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
		$this->render('dashboard/classroom', ['num_courses' => $num_courses, 'num_students' => $num_students, 'classroom' => $classroom]);
		$this->render('include/footer', []);
	}
	
	
	public function viewCourse() {
		$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		$students = [];
		if($this->checkCourseAccess()){
			$course = $this->result("SELECT * FROM `courses` WHERE `course_ID`='".$course_ID."'");
			$chapters = $this->read("SELECT * FROM `chapters` WHERE `course_ID`='".$course_ID."' ORDER BY `rank_position` ASC");	
			$enrollment = $this->read("SELECT `account_ID` FROM `enrollment` WHERE `course_ID`='".$course_ID."' AND `position_as`='STUDENT'");					
			$enroll = array_column($enrollment, 'account_ID');
			foreach($enroll as $account){
				$query = $this->result("SELECT * FROM `students` WHERE `account_ID`='".$account."'");
				$finished = $this->read("SELECT * FROM `completion` WHERE `account_ID`='".$account."' AND `course_ID`='".$course_ID."'");
				$completion = (count($finished) * 100) / $course['total_chapters'];
				$person = array('account_ID' => $query['account_ID'],
								'fullname' => $query['fullname'],
								'image' => $query['profile_image'],
								'course' => $course['title'],
								'completion' => $completion,
								'last_login' => $query['last_login_date']);
				array_push($students, $person);
			}
			$this->render('include/header', []);
			$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('dashboard/course_view', ['chapters' => $chapters, 'course' => $course, 'students' => $students]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
		}
	}
	
	public function editCourse() {
		$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if($this->checkCourseAccess()){
			$course = $this->result("SELECT * FROM `courses` WHERE `course_ID`='".$course_ID."'");
			$this->render('include/header', []);
			$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('dashboard/course_edit', ['course' => $course]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
		}		
	}
	
	public function saveCourse() {
		if(isset($_POST['Save'])){
			$title = $_POST['title'];
			$course_ID = $_POST['course_ID'];
			if(!empty($_FILES['cover_image'])){
				$sourcePath = $_FILES['cover_image']['tmp_name'];
				$targetPath = "data/courses/".$course_ID.".jpg";
				if(move_uploaded_file($sourcePath,$targetPath)) {
				}else{
					header('location: '.DOMAIN.'dashboard/classroom/view/?'.$course_ID.'&alert=error');
					exit();
				}
			}
			$data = $this->write("UPDATE `courses` SET `title`='".$title."' WHERE `course_id`='".$course_ID."'");
			if($data){
				header('location: '.DOMAIN.'dashboard/classroom/view/?'.$course_ID);
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/classroom/view/?'.$course_ID.'&alert=failed');
				exit();
			}
		}
	}
	
	public function forumCourse() {
		$course_ID = $_GET['cuid'];
		if($this->checkCourseAccess($course_ID)){
			if(isset($_GET['chid'])){	
				$chapter_ID = $_GET['chid'];
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
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
			$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('dashboard/course_forum', ['forum' => $chathead, 'course_ID' => $course_ID, 'chapter_ID' => $chapter_ID]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
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
	
	public function libraryCourse() {
		$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if($this->checkCourseAccess()){
			$books = $this->read("SELECT * FROM `ebooks` WHERE `course_ID`='".$course_ID."'");
			$this->render('include/header', []);
			$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('dashboard/course_library', ['books' => $books]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
		}
	}
	
	public function remvLibraryCourse() {
		if(isset($_GET['uuid'])){
			$book_ID = $_GET['uuid'];
			$data = $this->write("DELETE FROM `ebooks` WHERE `uuid`='".$book_ID."'");
			if($data){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
		}
	}
	
	public function saveLibraryCourse() {
		$course_ID = $_POST['course_ID'];
		if($this->checkCourseAccess($course_ID)){
			$image = $_FILES['book_image'];
			$title = $_POST['title'];
			$uuid = $this->generateRandomString();
			$upload_location = "data/books/";
			$path = $upload_location.$uuid.'.jpg';
			if(move_uploaded_file($_FILES['book_image']['tmp_name'],$path)){
				$query = $this->write("INSERT INTO `ebooks`(`uuid`, `course_ID`, `name`, `cover_image`) VALUES ('".$uuid."','".$course_ID."','".$title."','".$uuid.".jpg')");
				if($query){
					header('location: '.DOMAIN.'dashboard/classroom/view/?'.$course_ID);
					exit();
				}else{
					header('location: '.DOMAIN.'dashboard/classroom/library/?'.$course_ID);
					exit();
				}
			}else{
				header('location: '.DOMAIN.'dashboard/classroom/library/?'.$course_ID);
				exit();
			}
		}else{
			echo 'checkCourseAccess';
		}
	}
	
	public function filesCourse() {
		$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if($this->checkCourseAccess()){
			$path    = "data/uploads/";
			$tmpfiles = scandir($path);
			$files = $this->read("SELECT * FROM `files` WHERE `course_ID`='".$course_ID."'");
			$chapters = $this->read("SELECT * FROM `chapters` WHERE `course_ID`='".$course_ID."'");
			$this->render('include/header', []);
			$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('dashboard/course_files', ['course_ID' => $course_ID, 'files' => $files, 'chapters' => $chapters, 'tmpfile' => $tmpfiles]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
		}
	}
	
	public function filesSaveCourse() {
		if(isset($_POST['course_ID'])){
			$course_ID = $_POST['course_ID'];
			$chapter_ID = $_POST['chapter_ID'];
			$title = $_POST['title'];
			
			if($_POST['link_type'] == 'file'){
				$tmp_location = "data/uploads/";
				$upload_location = "data/files/";
				//$filename = $_FILES['files']['name'];
				$filename = $_POST['filename'];
				   
				$file_extension = pathinfo($filename, PATHINFO_EXTENSION);
				$file_extension = strtolower($file_extension);
			   
				$file_ID = $this->generateRandomString(24);
				$path = $upload_location.$file_ID.'.'.$file_extension;
				$location = DOMAIN.$path;
				rename($tmp_location.$filename,$path);
				
				$file_info = filesize($path);
			}else{
				$file_extension = 'FILE';
				$file_ID = $this->generateRandomString(24);
				$location = $_POST['filename'];
				$file_info = '0';
			}
			
			$date = date('Y/m/d');
			// Upload file
			//if(move_uploaded_file($_FILES['files']['tmp_name'],$path)){
				//$data = $this->write("DELETE FROM `chapters` WHERE `chapter_ID`='".$chapter_ID."'");
				$query = $this->write("INSERT INTO `files`(`file_ID`, `course_ID`, `chapter_ID`,`file_name`, `file_info`, `source_name`, `source_type`, `date_created`) VALUES ('".$file_ID."','".$course_ID."','".$chapter_ID."','".$title."','".$file_info."','".$location."','".$file_extension."','".$date."')");
				if($query){
					echo 'success';
				}else{
					echo 'error';
				}
			//}else{
			//	echo 'error';
			//}
		}
		
	}
	
	public function remvfilesCourse() {
		if(isset($_GET['fid'])){
			$file_ID = $_GET['fid'];
			$query = $this->write("SELECT `source_type` FROM `files` WHERE `file_ID`='".$file_ID."'");
			$upload_location = "data/files/";
			$filename = $_GET['fid'].'.'.$query['source_type'];
			if (file_exists($upload_location.$filename)) {   
                unlink($upload_location.$filename);
            }
			$data = $this->write("DELETE FROM `files` WHERE `file_ID`='".$file_ID."'");
			if($data){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
		}
	}
	
	public function previewCourse() {
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
			$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('dashboard/course_preview', ['chapters' => $chapters, 'rank' => $rank,'content' => $content, 'chapter_ID' => $chapter_ID, 'files' => $files, 'course_ID' => $course_ID]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
		}
	}
	
	public function courseAddChapter() {
		$course_ID = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if($this->checkCourseAccess()){
			$this->render('include/header', []);
			$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('dashboard/chapter', ['course_ID' => $course_ID]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
		}
	}
	
	public function courseEditChapter() {
		$course_ID = $_GET['cuid'];
		$chapter_ID = $_GET['chid'];
		if($this->checkCourseAccess($course_ID)){
			$chapter = $this->result("SELECT * FROM `chapters` WHERE `chapter_ID`='".$chapter_ID."'");
			
			$filename = 'data/chapters/'.$chapter_ID.'.jsp';
			$content = file_get_contents($filename);
			
			$this->render('include/header', []);
			$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
			$this->render('dashboard/chapter', ['name' => $chapter['name'], 'content' => $content,'chapter_ID' => $chapter_ID, 'course_ID' => $course_ID]);
			$this->render('include/footer', []);
		}else{
			if(empty($course_ID)){
				header('location: '.DOMAIN.'dashboard/classroom');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/page/notfound');
				exit();
			}
		}
	}
	
	public function courseRemoveChapter() {
		if(isset($_GET['chid']) && !empty($_GET['chid'])){
			$course_ID = $_GET['cuid'];
			$chapter_ID = $_GET['chid'];
		
			if($this->checkCourseAccess($course_ID)){
				$data = $this->write("DELETE FROM `chapters` WHERE `chapter_ID`='".$chapter_ID."'");
				$query = $this->write("UPDATE `courses` SET `total_chapters`=(`total_chapters` - 1) WHERE `course_ID`='".$course_ID."'");
					
				$filename = 'data/chapters/'.$chapter_ID.'.jsp';
				unlink($filename);
				if($data){
					header('location: '.DOMAIN.'dashboard/classroom/view/?'.$course_ID);
					exit();
				}else{
					header('location: '.DOMAIN.'dashboard/classroom/view/?'.$course_ID.'&alert=failed');
					exit();
				}				
			}else{
				if(empty($course_ID)){
					header('location: '.DOMAIN.'dashboard/classroom');
					exit();
				}else{
					header('location: '.DOMAIN.'dashboard/page/notfound');
					exit();
				}
			}
		}else{
			$course_ID = $_GET['cuid'];
			header('location: '.DOMAIN.'dashboard/classroom/view/?'.$course_ID);
			exit();
		}
		
	}
	
	public function courseSaveChapter() {
		if(isset($_POST['course_ID'])){
			$course_ID = $_POST['course_ID'];
			$chapter_ID = $_POST['chapter_ID'];
			$content = $_POST['content'];
			$name = $_POST['name'];
			if($this->checkCourseAccess($course_ID)){
				if($chapter_ID == 'none'){
					$chapter_ID = $this->generateRandomString();
					$fp = fopen('data/chapters/'.$chapter_ID.'.jsp', 'w');
					fwrite($fp, $content);
					fclose($fp);
					
					$total_rank = $this->result("SELECT COUNT(`rank_position`) as Total FROM `chapters` WHERE `course_ID`='".$course_ID."' ORDER BY `rank_position` ASC");
					$query = $this->write("UPDATE `courses` SET `total_chapters`=(`total_chapters` + 1) WHERE `course_ID`='".$course_ID."'");
					$date = date('Y/m/d');
					$data = $this->write("INSERT INTO `chapters`(`chapter_ID`, `course_ID`, `name`, `content`, `rank_position`, `created_date`) VALUES ('".$chapter_ID."','".$course_ID."','".$name."','".$chapter_ID.".jsp','".($total_rank['Total'] + 1)."','".$date."')");
				}else{
					$fp = fopen('data/chapters/'.$chapter_ID.'.jsp', 'w');
					fwrite($fp, $content);
					fclose($fp);
					$data = $this->write("UPDATE `chapters` SET `name`='".$name."' WHERE `chapter_ID`='".$chapter_ID."'");
				}		
				
				
				if($data){
					echo 'success';
				}else{
					echo 'failed';
				}
			}else{
				echo 'invalid';
			}
		}else{
			echo 'no entry';
		}
	}
	
	public function listMyStudents() {
		$students = [];
		$enroll_ID = array();
		$courses = $this->read("SELECT * FROM `courses` WHERE `teacher_ID`='".$this->account_ID."'");
		foreach($courses as $course){
			$course_ID = $course['course_ID'];
			$enrolled = $this->read("SELECT `account_ID` FROM `enrollment` WHERE `course_ID`='".$course_ID."' AND `position_as`='STUDENT'");
			foreach($enrolled as $enroll){
				$account_ID = $enroll['account_ID'];
				if(!in_array($account_ID,$enroll_ID)){
					$enrollment = $this->read("SELECT * FROM `students` WHERE `account_ID`='".$account_ID."'");
					array_push($enroll_ID, $account_ID);
					array_push($students, $enrollment);
				}			
			}
		}
		$this->render('include/header', []);
		$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
		$this->render('dashboard/students', ['students' => $students]);
		$this->render('include/footer', []);
	}
	
	public function viewSettings() {	
		$this->render('include/header', []);
		$this->render('dashboard/navbar', ['username' => $this->profile['fullname'], 'userimage' => $this->profile['profile_image']]);
		$this->render('dashboard/settings', []);
		$this->render('include/footer', []);
	}
	
	public function saveSettings() {	
		$password = $_POST['newpwd'];
		$repassword = $_POST['repwd'];
		if($password == $repassword){
			$hashedpass = hash("sha256", $password);
			$query = $this->write("UPDATE `accounts` SET `password`='".$hashedpass."', `on_forgot_pwd`='0',`forgot_pwd_token`='none' WHERE `account_ID`='".$this->account_ID."'");
			if($query){
				header('location: '.DOMAIN.'dashboard/settings?alert=success');
				exit();
			}else{
				header('location: '.DOMAIN.'dashboard/settings?alert=error');
			}
		}else{
			header('location: '.DOMAIN.'dashboard/settings?alert=mismatch');
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