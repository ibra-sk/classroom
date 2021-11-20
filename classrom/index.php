<?php
const DS = DIRECTORY_SEPARATOR;
define('DOMAIN', "http://127.0.0.1/classrom/");
define('TITLE', "TrainUp");
define('APP', dirname(__DIR__) . DS . "classrom_base" . DS);
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'classrombase');
define('DB_USER', 'root');
define('DB_PASS', '');

include 'route.php';

//Libraries
include APP . 'lib/Model.php';

//Hanlders
include APP . 'src/ErrorHandle.php';

//Controllers
include APP . 'src/Home.php';
include APP . 'src/Admin.php';
include APP . 'src/Portal.php';
include APP . 'src/Dashboard.php';

$route = new Route();
$route->add('/', 'Home', '');
$route->add('/home', 'Home', '');
$route->add('/login', 'Home', 'login');
$route->add('/login/forgotpwd', 'Home', 'ForgotPassword');
$route->add('/login/account/resetpwd', 'Home', 'ResetPassword');
$route->add('/logout', 'Home', 'logout');

//Portal
$route->add('/portal', 'Portal', 'home');
$route->add('/portal/home', 'Portal', 'home');
$route->add('/portal/courses', 'Portal', 'ListCourses');
$route->add('/portal/courses/view', 'Portal', 'ViewCourses');
$route->add('/portal/courses/forum', 'Portal', 'ViewForum');
$route->add('/portal/courses/attendee/?', 'Portal', 'ViewPeople');
//$route->add('/portal/courses/books/?', 'Portal', 'ViewBooks');
//$route->add('/portal/courses/files/?', 'Portal', 'ViewFiles');
$route->add('/portal/courses/complete', 'Portal', 'setCompletion');
$route->add('/portal/courses/sendpost', 'Portal', 'setForumPost');
$route->add('/portal/settings', 'Portal', 'viewSettings');
$route->add('/portal/settings/changepwd', 'Portal', 'saveSettings');

//Dashboard
$route->add('/dashboard/classroom', 'Dashboard', 'showClassRoom');
$route->add('/dashboard/classroom/view/?', 'Dashboard', 'viewCourse');
$route->add('/dashboard/classroom/delete/?', 'Dashboard', 'viewCourse');
$route->add('/dashboard/classroom/edit/?', 'Dashboard', 'editCourse');
$route->add('/dashboard/classroom/edit/save', 'Dashboard', 'saveCourse');
$route->add('/dashboard/classroom/preview', 'Dashboard', 'previewCourse');
$route->add('/dashboard/classroom/forum', 'Dashboard', 'forumCourse');
$route->add('/dashboard/classroom/forum/sendpost', 'Dashboard', 'setForumPost');
//$route->add('/dashboard/classroom/library/?', 'Dashboard', 'libraryCourse');
//$route->add('/dashboard/classroom/library/remove', 'Dashboard', 'remvLibraryCourse');
//$route->add('/dashboard/classroom/library/save', 'Dashboard', 'saveLibraryCourse');
$route->add('/dashboard/classroom/filemanager/?', 'Dashboard', 'filesCourse');
$route->add('/dashboard/classroom/filemanager/save', 'Dashboard', 'filesSaveCourse');
$route->add('/dashboard/classroom/filemanager/remove', 'Dashboard', 'remvfilesCourse');
$route->add('/dashboard/classroom/chapter/edit/?', 'Dashboard', 'courseEditChapter');
$route->add('/dashboard/classroom/chapter/add/?', 'Dashboard', 'courseAddChapter');
$route->add('/dashboard/classroom/chapter/remove/?', 'Dashboard', 'courseRemoveChapter');
$route->add('/dashboard/classroom/chapter/save', 'Dashboard', 'courseSaveChapter');
$route->add('/dashboard/students', 'Dashboard', 'listMyStudents');
$route->add('/dashboard/settings', 'Dashboard', 'viewSettings');
$route->add('/dashboard/settings/changepwd', 'Dashboard', 'saveSettings');

//Admin
$route->add('/admin/', 'Admin', 'home');
$route->add('/admin/home', 'Admin', 'home');
$route->add('/admin/classroom', 'Admin', 'showClassRoom');
$route->add('/admin/classroom/add', 'Admin', 'addClassRoom');
$route->add('/admin/classroom/view/?', 'Admin', 'viewClassRoom');
$route->add('/admin/classroom/view/save', 'Admin', 'editClassRoom');
$route->add('/admin/teachers', 'Admin', 'listTeachers');
$route->add('/admin/teachers/add', 'Admin', 'addTeachers');
$route->add('/admin/teachers/edit/?', 'Admin', 'editTeachers');
$route->add('/admin/teachers/edit/link', 'Admin', 'linkTeachers');
$route->add('/admin/teachers/remove', 'Admin', 'removeTeachers');
$route->add('/admin/students', 'Admin', 'listStudents');
$route->add('/admin/students/add', 'Admin', 'addStudents');
$route->add('/admin/students/edit/?', 'Admin', 'editStudents');
$route->add('/admin/students/edit/link', 'Admin', 'linkStudents');
$route->add('/admin/students/remove', 'Admin', 'removeStudents');
$route->add('/admin/payment/add', 'Admin', 'addPayment');
$route->add('/admin/payment/remove', 'Admin', 'removePayment');
$route->add('/admin/settings', 'Admin', 'viewSettings');
$route->add('/admin/settings/changepwd', 'Admin', 'saveSettings');


$route->submit();

?>